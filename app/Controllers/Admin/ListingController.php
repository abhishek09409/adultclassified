<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Str;
use App\Repositories\ListingRepository;
use App\Repositories\LocationRepository;
use App\Services\AuditService;
use App\Services\AuthService;
use App\Services\Gate;
use App\Services\ImageService;

final class ListingController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'listings')) {
            return $denied;
        }
        $filters = ['status' => (string) $request->query('status', '')];
        $page = max(1, (int) ($request->query('page', '1') ?? '1'));
        $result = (new ListingRepository())->search($filters, false, $page, 20);

        return $this->render('admin/listings', [
            'title' => 'Listings',
            'listings' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / 20)),
            'status' => $filters['status'],
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function create(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'listings')) {
            return $denied;
        }

        return $this->form($request, null);
    }

    /**
     * @param array<string, string> $params
     */
    public function edit(Request $request, array $params = []): Response
    {
        if ($denied = $this->guard($request, 'listings')) {
            return $denied;
        }
        $listing = $this->find((int) ($params['id'] ?? 0));
        if ($listing === null) {
            return (new \App\Controllers\ErrorController())->notFound();
        }

        return $this->form($request, $listing);
    }

    /**
     * @param array<string, string> $params
     */
    public function save(Request $request, array $params = []): Response
    {
        if ($denied = $this->guard($request, 'listings')) {
            return $denied;
        }
        $id = isset($params['id']) ? (int) $params['id'] : 0;
        $existing = $id > 0 ? $this->find($id) : null;
        if ($id > 0 && $existing === null) {
            return (new \App\Controllers\ErrorController())->notFound();
        }

        $title = mb_substr((string) $request->input('title', ''), 0, 180);
        $description = trim((string) $request->input('description', ''));
        $age = $request->integer('age');
        $categoryId = $request->integer('category_id');
        $stateId = $request->integer('state_id');
        $cityId = $request->integer('city_id');
        $locationId = $request->integer('location_id');
        $availability = mb_substr((string) $request->input('availability_note', ''), 0, 255);
        if ($title === '' || mb_strlen($description) < 20 || $age === null || $age < 21 || $age > 99 || $categoryId === null || $stateId === null || $cityId === null || $locationId === null) {
            Session::flash('error', 'Check the title, description, age (21–99), and location.');

            return Response::redirect($id > 0 ? '/admin/listings/' . $id . '/edit' : '/admin/listings/create');
        }
        if (!$this->placeMatches($stateId, $cityId, $locationId, $categoryId)) {
            Session::flash('error', 'The city or locality does not belong to the selected place.');

            return Response::redirect($id > 0 ? '/admin/listings/' . $id . '/edit' : '/admin/listings/create');
        }

        $admin = (new AuthService())->user();
        $canPublish = Gate::allows($admin, 'listings.publish');
        $status = (string) $request->input('status', 'draft');
        $allowed = $canPublish ? ['draft', 'pending', 'published', 'suspended'] : ['draft', 'pending'];
        if (!in_array($status, $allowed, true)) {
            $status = 'draft';
        }
        $featured = $canPublish && $request->input('is_featured') === '1' ? 1 : 0;
        $verified = Gate::allows($admin, 'listings.verify') && $request->input('is_verified') === '1' ? 1 : 0;
        $indexable = $status === 'published' ? 1 : 0;
        $moderation = match ($status) {
            'published' => 'approved',
            'suspended' => 'suspended',
            default => 'pending',
        };
        $pdo = Database::connection();
        if ($existing === null) {
            $pdo->prepare(
                'INSERT INTO listings (category_id, state_id, city_id, location_id, title, slug, description, availability_note, age, age_display, status, moderation_status, is_featured, is_verified, is_indexable, published_at)
                 VALUES (:category_id, :state_id, :city_id, :location_id, :title, :slug, :description, :availability_note, :age, :age_display, :status, :moderation_status, :is_featured, :is_verified, :is_indexable, :published_at)'
            )->execute([
                'category_id' => $categoryId,
                'state_id' => $stateId,
                'city_id' => $cityId,
                'location_id' => $locationId,
                'title' => $title,
                'slug' => 'draft-' . bin2hex(random_bytes(4)),
                'description' => $description,
                'availability_note' => $availability !== '' ? $availability : null,
                'age' => $age,
                'age_display' => 'exact',
                'status' => $status,
                'moderation_status' => $moderation,
                'is_featured' => $featured,
                'is_verified' => $verified,
                'is_indexable' => $indexable,
                'published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            ]);
            $id = (int) $pdo->lastInsertId();
            $action = 'Listing Created';
            $previous = null;
        } else {
            $previous = (string) $existing['status'];
            $publishedAt = $existing['published_at'];
            if ($status === 'published' && $publishedAt === null) {
                $publishedAt = date('Y-m-d H:i:s');
            }
            $pdo->prepare(
                'UPDATE listings SET category_id = :category_id, state_id = :state_id, city_id = :city_id, location_id = :location_id,
                    title = :title, description = :description, availability_note = :availability_note, age = :age, status = :status,
                    moderation_status = :moderation_status, is_featured = :is_featured, is_verified = :is_verified, is_indexable = :is_indexable,
                    published_at = :published_at WHERE id = :id'
            )->execute([
                'category_id' => $categoryId,
                'state_id' => $stateId,
                'city_id' => $cityId,
                'location_id' => $locationId,
                'title' => $title,
                'description' => $description,
                'availability_note' => $availability !== '' ? $availability : null,
                'age' => $age,
                'status' => $status,
                'moderation_status' => $moderation,
                'is_featured' => $featured,
                'is_verified' => $verified,
                'is_indexable' => $indexable,
                'published_at' => $publishedAt,
                'id' => $id,
            ]);
            $action = $status === 'published' ? 'Listing Published' : 'Listing Updated';
        }
        $slug = mb_substr(Str::slug($title), 0, 160) . '-' . $id;
        $pdo->prepare('UPDATE listings SET slug = :slug WHERE id = :id')->execute(['slug' => $slug, 'id' => $id]);
        $imageError = null;
        $file = $request->file('image');
        if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $imageError = (new ImageService())->storeForListing($id, $file, $title);
        }
        (new AuditService())->log(isset($admin['id']) ? (int) $admin['id'] : null, $action, 'listing', $id, $previous, $status, $request->ip());
        Session::flash($imageError === null ? 'success' : 'error', $imageError ?? 'Listing saved.');

        return Response::redirect('/admin/listings/' . $id . '/edit');
    }

    /**
     * @param array<string, string> $params
     */
    public function action(Request $request, array $params = []): Response
    {
        if ($denied = $this->guard($request, 'listings')) {
            return $denied;
        }
        $id = (int) ($params['id'] ?? 0);
        $listing = $this->find($id);
        if ($listing === null) {
            return (new \App\Controllers\ErrorController())->notFound();
        }
        $admin = (new AuthService())->user();
        $action = (string) $request->input('listing_action', '');
        $previous = (string) $listing['status'];
        $map = [
            'publish' => ['published', 'approved', 1, 'Listing Published', 'listings.publish'],
            'unpublish' => ['draft', 'pending', 0, 'Listing Updated', 'listings.publish'],
            'suspend' => ['suspended', 'suspended', 0, 'Listing Suspended', 'listings.publish'],
            'restore' => ['draft', 'pending', 0, 'Listing Restored', 'listings.publish'],
            'feature' => null,
            'unfeature' => null,
            'verify' => null,
            'unverify' => null,
            'delete' => ['deleted', 'rejected', 0, 'Listing Deleted', 'listings.delete'],
        ];
        if (!isset($map[$action])) {
            Session::flash('error', 'Unknown action.');

            return Response::redirect('/admin/listings');
        }
        if (in_array($action, ['feature', 'unfeature'], true)) {
            if (!Gate::allows($admin, 'listings.publish')) {
                return (new \App\Controllers\ErrorController())->forbidden();
            }
            Database::connection()->prepare('UPDATE listings SET is_featured = :value WHERE id = :id')->execute([
                'value' => $action === 'feature' ? 1 : 0,
                'id' => $id,
            ]);
            (new AuditService())->log((int) $admin['id'], 'Listing Updated', 'listing', $id, $previous, $previous, $request->ip());
            Session::flash('success', 'Listing updated.');

            return Response::redirect('/admin/listings/' . $id . '/edit');
        }
        if (in_array($action, ['verify', 'unverify'], true)) {
            if (!Gate::allows($admin, 'listings.verify')) {
                return (new \App\Controllers\ErrorController())->forbidden();
            }
            Database::connection()->prepare('UPDATE listings SET is_verified = :value WHERE id = :id')->execute([
                'value' => $action === 'verify' ? 1 : 0,
                'id' => $id,
            ]);
            (new AuditService())->log((int) $admin['id'], 'Listing Updated', 'listing', $id, $previous, $previous, $request->ip());
            Session::flash('success', $action === 'verify' ? 'Listing marked verified.' : 'Verification removed.');

            return Response::redirect('/admin/listings/' . $id . '/edit');
        }

        [$status, $moderation, $indexable, $audit, $ability] = $map[$action];
        if (!Gate::allows($admin, $ability)) {
            return (new \App\Controllers\ErrorController())->forbidden();
        }
        Database::connection()->prepare(
            'UPDATE listings SET status = :status, moderation_status = :moderation, is_indexable = :indexable,
                published_at = CASE WHEN :status_published = \'published\' AND published_at IS NULL THEN NOW() ELSE published_at END
             WHERE id = :id'
        )->execute([
            'status' => $status,
            'moderation' => $moderation,
            'indexable' => $indexable,
            'status_published' => $status,
            'id' => $id,
        ]);
        (new AuditService())->log((int) $admin['id'], $audit, 'listing', $id, $previous, $status, $request->ip());
        Session::flash('success', 'Listing updated.');

        return Response::redirect($status === 'deleted' ? '/admin/listings' : '/admin/listings/' . $id . '/edit');
    }

    /**
     * @param array<string, mixed>|null $listing
     */
    private function form(Request $request, ?array $listing): Response
    {
        unset($request);
        $locations = new LocationRepository();

        return $this->render('admin/listing-form', [
            'title' => $listing === null ? 'New listing' : 'Edit listing',
            'listing' => $listing,
            'categories' => $locations->categories(),
            'states' => $locations->states(),
            'images' => $listing === null ? [] : (new ListingRepository())->images((int) $listing['id']),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function find(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }
        $statement = Database::connection()->prepare('SELECT * FROM listings WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    private function placeMatches(int $stateId, int $cityId, int $locationId, int $categoryId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT loc.id FROM locations loc
             INNER JOIN cities ci ON ci.id = loc.city_id
             INNER JOIN categories c ON c.id = :category_id
             WHERE loc.id = :location_id AND ci.id = :city_id AND ci.state_id = :state_id
             LIMIT 1'
        );
        $statement->execute([
            'category_id' => $categoryId,
            'location_id' => $locationId,
            'city_id' => $cityId,
            'state_id' => $stateId,
        ]);

        return $statement->fetch() !== false;
    }
}
