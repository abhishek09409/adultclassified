<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Str;
use App\Repositories\LocationRepository;
use App\Services\AuditService;
use App\Services\AuthService;

final class TaxonomyController extends AdminController
{
    /**
     * @param array<string, string> $params
     */
    public function categories(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'categories')) {
            return $denied;
        }
        if ($request->method() === 'POST') {
            $this->saveCategory($request);
        }

        return $this->render('admin/categories', [
            'title' => 'Categories',
            'rows' => Database::connection()->query('SELECT * FROM categories ORDER BY id ASC')->fetchAll(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function states(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'locations')) {
            return $denied;
        }
        if ($request->method() === 'POST') {
            $this->toggle('states', $request);
        }

        return $this->render('admin/states', [
            'title' => 'States',
            'rows' => Database::connection()->query('SELECT * FROM states ORDER BY name ASC')->fetchAll(),
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function cities(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'locations')) {
            return $denied;
        }
        if ($request->method() === 'POST') {
            $this->saveCity($request);
        }
        $stateId = $request->integer('state_id');

        return $this->render('admin/cities', [
            'title' => 'Cities',
            'states' => (new LocationRepository())->states(),
            'rows' => $stateId === null
                ? Database::connection()->query('SELECT ci.*, s.name AS state_name FROM cities ci INNER JOIN states s ON s.id = ci.state_id ORDER BY s.name, ci.name LIMIT 200')->fetchAll()
                : (new LocationRepository())->citiesByState($stateId, false),
            'stateId' => $stateId,
        ]);
    }

    /**
     * @param array<string, string> $params
     */
    public function locations(Request $request, array $params = []): Response
    {
        unset($params);
        if ($denied = $this->guard($request, 'locations')) {
            return $denied;
        }
        if ($request->method() === 'POST') {
            $this->saveLocation($request);
        }
        $cityId = $request->integer('city_id');

        return $this->render('admin/locations', [
            'title' => 'Locations',
            'states' => (new LocationRepository())->states(),
            'rows' => $cityId === null
                ? Database::connection()->query('SELECT loc.*, ci.name AS city_name FROM locations loc INNER JOIN cities ci ON ci.id = loc.city_id ORDER BY ci.name, loc.name LIMIT 200')->fetchAll()
                : (new LocationRepository())->localitiesByCity($cityId, false),
            'cityId' => $cityId,
        ]);
    }

    private function saveCategory(Request $request): void
    {
        $id = $request->integer('id');
        $name = mb_substr((string) $request->input('name', ''), 0, 80);
        $description = trim((string) $request->input('description', ''));
        $status = $request->input('status') === 'inactive' ? 'inactive' : 'active';
        if ($name === '' || $description === '') {
            Session::flash('error', 'Name and description are required.');

            return;
        }
        $slug = Str::slug($name);
        if ($id === null) {
            Database::connection()->prepare(
                'INSERT INTO categories (name, slug, description, status) VALUES (:name, :slug, :description, :status)'
            )->execute(['name' => $name, 'slug' => $slug, 'description' => $description, 'status' => $status]);
        } else {
            Database::connection()->prepare(
                'UPDATE categories SET name = :name, slug = :slug, description = :description, status = :status WHERE id = :id'
            )->execute(['name' => $name, 'slug' => $slug, 'description' => $description, 'status' => $status, 'id' => $id]);
        }
        LocationRepository::forgetReferenceData();
        $this->audit($request, 'Settings Changed', 'category', $id);
        Session::flash('success', 'Category saved.');
    }

    private function saveCity(Request $request): void
    {
        if ($request->input('toggle_id') !== null) {
            $this->toggle('cities', $request);

            return;
        }
        $stateId = $request->integer('state_id');
        $name = mb_substr((string) $request->input('name', ''), 0, 120);
        if ($stateId === null || $name === '') {
            Session::flash('error', 'Choose a state and city name.');

            return;
        }
        Database::connection()->prepare(
            'INSERT INTO cities (state_id, name, slug, status) VALUES (:state_id, :name, :slug, :status)
             ON DUPLICATE KEY UPDATE name = VALUES(name)'
        )->execute(['state_id' => $stateId, 'name' => $name, 'slug' => Str::slug($name), 'status' => 'active']);
        LocationRepository::forgetReferenceData();
        Session::flash('success', 'City saved.');
    }

    private function saveLocation(Request $request): void
    {
        if ($request->input('toggle_id') !== null) {
            $this->toggle('locations', $request);

            return;
        }
        $cityId = $request->integer('city_id');
        $name = mb_substr((string) $request->input('name', ''), 0, 140);
        if ($cityId === null || $name === '') {
            Session::flash('error', 'Choose a city and locality name.');

            return;
        }
        Database::connection()->prepare(
            'INSERT INTO locations (city_id, name, slug, status) VALUES (:city_id, :name, :slug, :status)
             ON DUPLICATE KEY UPDATE name = VALUES(name)'
        )->execute(['city_id' => $cityId, 'name' => $name, 'slug' => Str::slug($name), 'status' => 'active']);
        LocationRepository::forgetReferenceData();
        Session::flash('success', 'Locality saved.');
    }

    private function toggle(string $table, Request $request): void
    {
        $allowed = ['states', 'cities', 'locations'];
        if (!in_array($table, $allowed, true)) {
            return;
        }
        $id = $request->integer('toggle_id') ?? $request->integer('id');
        $status = $request->input('status') === 'inactive' ? 'inactive' : 'active';
        if ($id === null) {
            return;
        }
        $statement = Database::connection()->prepare('UPDATE ' . $table . ' SET status = :status WHERE id = :id');
        $statement->execute(['status' => $status, 'id' => $id]);
        LocationRepository::forgetReferenceData();
        $this->audit($request, 'Settings Changed', $table, $id);
        Session::flash('success', 'Status updated.');
    }

    private function audit(Request $request, string $action, string $type, ?int $id): void
    {
        $admin = (new AuthService())->user();
        (new AuditService())->log($admin ? (int) $admin['id'] : null, $action, $type, $id, null, null, $request->ip());
    }
}
