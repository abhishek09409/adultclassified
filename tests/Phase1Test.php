<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Database;
use App\Core\ErrorHandler;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Router;
use App\Core\Seeder;
use App\Helpers\Html;
use App\Helpers\Str;
use Database\Seeders\CategorySeeder;
use Database\Seeders\StateSeeder;

require dirname(__DIR__) . '/includes/bootstrap.php';

$failures = [];

function check(bool $condition, string $message): void
{
    global $failures;
    if ($condition) {
        fwrite(STDOUT, "PASS {$message}\n");
        return;
    }

    $failures[] = $message;
    fwrite(STDOUT, "FAIL {$message}\n");
}

try {
    $pdo = Database::connection();
    check(true, 'database connection');
} catch (Throwable $exception) {
    check(false, 'database connection: ' . $exception->getMessage());
    fwrite(STDOUT, count($failures) . " failed\n");
    exit(1);
}

$migrator = new Migrator($pdo, BASE_PATH . '/database/migrations');
$pending = $migrator->migrate();
check($pending === [] || $pending === ['001_create_schema.sql'], 'migration applies pending files only');
check($migrator->migrate() === [], 'migration is idempotent');
check(in_array('001_create_schema.sql', $migrator->applied(), true), 'schema migration is recorded');

(new Seeder($pdo))->run();
(new Seeder($pdo))->run();

$stateRows = $pdo->query('SELECT name, slug FROM states ORDER BY name ASC')->fetchAll();
$expectedStates = StateSeeder::definitions();
usort($expectedStates, static fn (array $a, array $b): int => $a['name'] <=> $b['name']);
check(count($stateRows) === 29, '29 states seeded');
check($stateRows === $expectedStates, 'state names and slugs match');

$categoryRows = $pdo->query('SELECT name, slug, description FROM categories ORDER BY id ASC')->fetchAll();
check($categoryRows === CategorySeeder::definitions(), '4 categories seeded without duplicates');

$expectedTables = [
    'states',
    'cities',
    'locations',
    'categories',
    'listings',
    'listing_images',
    'listing_variations',
    'listing_variation_usage',
    'automation_runs',
    'automation_logs',
    'admins',
    'admin_sessions',
    'reports',
    'seo_metadata',
    'audit_logs',
    'settings',
];
$placeholders = implode(',', array_fill(0, count($expectedTables), '?'));
$tableStatement = $pdo->prepare(
    "SELECT table_name FROM information_schema.tables
     WHERE table_schema = DATABASE() AND table_name IN ($placeholders)"
);
$tableStatement->execute($expectedTables);
$present = $tableStatement->fetchAll(PDO::FETCH_COLUMN);
sort($present);
$expectedSorted = $expectedTables;
sort($expectedSorted);
check($present === $expectedSorted, 'required tables exist');

$indexStatement = $pdo->prepare(
    'SELECT DISTINCT index_name FROM information_schema.statistics
     WHERE table_schema = DATABASE() AND table_name = :table AND index_name = :index'
);
$requiredIndexes = [
    ['listings', 'idx_listings_state_id'],
    ['listings', 'idx_listings_city_id'],
    ['listings', 'idx_listings_location_id'],
    ['listings', 'idx_listings_category_id'],
    ['listings', 'idx_listings_status'],
    ['listings', 'idx_listings_published_at'],
    ['listings', 'idx_listings_slug'],
    ['listings', 'idx_listings_is_featured'],
    ['listings', 'idx_listings_is_indexable'],
];
$missingIndexes = [];
foreach ($requiredIndexes as [$table, $index]) {
    $indexStatement->execute(['table' => $table, 'index' => $index]);
    if ($indexStatement->fetch() === false) {
        $missingIndexes[] = $index;
    }
}
check($missingIndexes === [], 'listing indexes exist');

$delhi = $pdo->prepare('SELECT id FROM states WHERE slug = :slug');
$delhi->execute(['slug' => "' OR 1=1 --"]);
check($delhi->fetch() === false, 'prepared statement rejects injected slug');
$delhi->execute(['slug' => 'delhi']);
check($delhi->fetch() !== false, 'delhi state can be selected by slug');

$stateId = (int) $pdo->query("SELECT id FROM states WHERE slug = 'delhi'")->fetchColumn();
$categoryId = (int) $pdo->query("SELECT id FROM categories WHERE slug = 'escorts'")->fetchColumn();
$pdo->prepare('INSERT INTO cities (state_id, name, slug, status) VALUES (:state_id, :name, :slug, :status)')
    ->execute(['state_id' => $stateId, 'name' => 'Phase One Probe', 'slug' => 'phase-one-probe', 'status' => 'active']);
$cityId = (int) $pdo->lastInsertId();
$pdo->prepare('INSERT INTO locations (city_id, name, slug, status) VALUES (:city_id, :name, :slug, :status)')
    ->execute(['city_id' => $cityId, 'name' => 'Connaught Place', 'slug' => 'connaught-place', 'status' => 'active']);
$locationId = (int) $pdo->lastInsertId();

$insertListing = $pdo->prepare(
    'INSERT INTO listings (category_id, state_id, city_id, location_id, title, slug, description, age)
     VALUES (:category_id, :state_id, :city_id, :location_id, :title, :slug, :description, :age)'
);
$underAgeRejected = false;
try {
    $insertListing->execute([
        'category_id' => $categoryId,
        'state_id' => $stateId,
        'city_id' => $cityId,
        'location_id' => $locationId,
        'title' => 'Under age candidate',
        'slug' => 'under-age-candidate',
        'description' => 'Directory description.',
        'age' => 18,
    ]);
} catch (PDOException) {
    $underAgeRejected = true;
}
check($underAgeRejected, 'age below 21 is rejected');

$insertListing->execute([
    'category_id' => $categoryId,
    'state_id' => $stateId,
    'city_id' => $cityId,
    'location_id' => $locationId,
    'title' => 'Allowed adult profile',
    'slug' => 'allowed-adult-profile',
    'description' => 'Non-explicit directory description.',
    'age' => 21,
]);
$listingId = (int) $pdo->lastInsertId();
$verified = $pdo->prepare('SELECT is_verified, status FROM listings WHERE id = :id');
$verified->execute(['id' => $listingId]);
$row = $verified->fetch();
check(is_array($row) && (int) $row['is_verified'] === 0 && $row['status'] === 'draft', 'new listing is an unverified draft');
$pdo->prepare('DELETE FROM listings WHERE id = :id')->execute(['id' => $listingId]);
$pdo->prepare('DELETE FROM locations WHERE id = :id')->execute(['id' => $locationId]);
$pdo->prepare('DELETE FROM cities WHERE id = :id')->execute(['id' => $cityId]);

check(Html::escape('<script>"alert"</script>') === '&lt;script&gt;&quot;alert&quot;&lt;/script&gt;', 'html output is escaped');
check(Str::slug('New Delhi') === 'new-delhi', 'slug helper normalizes names');
check(Request::normalizePath('/health/') === '/health', 'trailing slash is normalized');
check(Request::normalizePath('/../.env') === '/', 'path traversal is discarded');

$router = new Router();
$router->get('/health', static fn (): \App\Core\Response => \App\Core\Response::json(['ok' => true]));
$router->get('/locations/{id}', static function (Request $request, array $params): \App\Core\Response {
    unset($request);
    if (!ctype_digit($params['id'] ?? '')) {
        return \App\Core\Response::json(['error' => 'invalid id'], 422);
    }

    return \App\Core\Response::json(['id' => (int) $params['id']]);
});

$health = $router->dispatch(Request::capture('GET', '/health'));
check($health->status() === 200 && str_contains($health->body(), '"ok":true'), 'router serves health');

$missing = $router->dispatch(Request::capture('GET', '/missing-page'));
check($missing->status() === 404 && str_contains($missing->body(), 'Page not found'), 'unknown route returns 404');

$validId = $router->dispatch(Request::capture('GET', '/locations/15'));
check($validId->status() === 200 && str_contains($validId->body(), '"id":15'), 'numeric route id is accepted');

$invalidId = $router->dispatch(Request::capture('GET', '/locations/1%20OR%201'));
check($invalidId->status() === 422, 'non-numeric route id is rejected');

$debug = Config::get('app.debug');
$reflection = new ReflectionClass(Config::class);
$property = $reflection->getProperty('items');
$property->setAccessible(true);
$items = $property->getValue();
$items['app']['debug'] = false;
$property->setValue(null, $items);
$body = ErrorHandler::publicBody(new RuntimeException('SQLSTATE[42000] near /var/www/.env secret'));
check(!str_contains($body, 'SQLSTATE') && !str_contains($body, '.env') && !str_contains($body, '/var/www'), 'production errors hide internals');
$items['app']['debug'] = $debug;
$property->setValue(null, $items);

if ($failures !== []) {
    fwrite(STDOUT, count($failures) . " failed\n");
    exit(1);
}

fwrite(STDOUT, "All Phase 1 checks passed\n");
