<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use PDO;

final class AuthService
{
    public function __construct(
        private readonly AuditService $audit = new AuditService(),
        private readonly RateLimiter $limiter = new RateLimiter()
    ) {
    }

    public function attempt(string $email, string $password, string $ip): string
    {
        $email = strtolower(trim($email));
        if ($this->limiter->tooMany('login', $ip, 8, 900)) {
            return 'Too many sign-in attempts. Please wait and try again.';
        }

        $statement = Database::connection()->prepare(
            'SELECT id, name, email, password_hash, role, status, failed_login_count, locked_until
             FROM admins WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $admin = $statement->fetch();
        $this->recordAttempt($email, $ip);

        if (!is_array($admin) || $admin['status'] !== 'active' || !password_verify($password, (string) $admin['password_hash'])) {
            if (is_array($admin)) {
                $this->registerFailure((int) $admin['id'], (int) $admin['failed_login_count']);
            }
            $this->limiter->hit('login', $ip);

            return 'The email or password is not correct.';
        }

        if ($admin['locked_until'] !== null && strtotime((string) $admin['locked_until']) > time()) {
            return 'This account is temporarily locked. Please try again later.';
        }

        Session::regenerate();
        Session::put('admin_id', (int) $admin['id']);
        $token = bin2hex(random_bytes(32));
        Session::put('admin_session', hash('sha256', $token));
        $insert = Database::connection()->prepare(
            'INSERT INTO admin_sessions (admin_id, token_hash, ip_address, user_agent, expires_at)
             VALUES (:admin_id, :token_hash, :ip_address, :user_agent, DATE_ADD(NOW(), INTERVAL 12 HOUR))'
        );
        $agent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $insert->execute([
            'admin_id' => (int) $admin['id'],
            'token_hash' => hash('sha256', $token),
            'ip_address' => $ip,
            'user_agent' => $agent,
        ]);
        Session::put('admin_session', hash('sha256', $token));
        Database::connection()->prepare(
            'UPDATE admins SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id'
        )->execute(['id' => (int) $admin['id']]);
        $this->audit->log((int) $admin['id'], 'Admin Login', 'admin', (int) $admin['id'], null, 'active', $ip);

        return '';
    }

    public function logout(string $ip): void
    {
        $admin = $this->user();
        $token = Session::get('admin_session');
        if (is_string($token)) {
            Database::connection()->prepare('DELETE FROM admin_sessions WHERE token_hash = :token')->execute(['token' => $token]);
        }
        if ($admin !== null) {
            $this->audit->log((int) $admin['id'], 'Admin Logout', 'admin', (int) $admin['id'], null, null, $ip);
        }
        Session::destroy();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function user(): ?array
    {
        $id = Session::get('admin_id');
        $token = Session::get('admin_session');
        if (!is_int($id) && !(is_string($id) && ctype_digit($id))) {
            return null;
        }
        if (!is_string($token) || $token === '') {
            return null;
        }

        $statement = Database::connection()->prepare(
            'SELECT a.id, a.name, a.email, a.role, a.status
             FROM admins a
             INNER JOIN admin_sessions s ON s.admin_id = a.id
             WHERE a.id = :id AND s.token_hash = :token AND s.expires_at > NOW() AND a.status = :status
             LIMIT 1'
        );
        $statement->execute(['id' => (int) $id, 'token' => $token, 'status' => 'active']);
        $admin = $statement->fetch();

        return is_array($admin) ? $admin : null;
    }

    public static function createAdmin(PDO $pdo, string $name, string $email, string $password, string $role): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO admins (name, email, password_hash, role, status)
             VALUES (:name, :email, :password_hash, :role, :status)'
        );
        $statement->execute([
            'name' => $name,
            'email' => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function recordAttempt(string $email, string $ip): void
    {
        Database::connection()->prepare(
            'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)'
        )->execute(['email' => $email, 'ip' => $ip]);
    }

    private function registerFailure(int $id, int $current): void
    {
        $count = $current + 1;
        $locked = $count >= 5 ? date('Y-m-d H:i:s', time() + 900) : null;
        Database::connection()->prepare(
            'UPDATE admins SET failed_login_count = :count, locked_until = :locked WHERE id = :id'
        )->execute(['count' => $count, 'locked' => $locked, 'id' => $id]);
    }
}
