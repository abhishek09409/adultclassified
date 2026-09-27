<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\ListingRepository;
use App\Repositories\LocationRepository;
use App\Services\ListingPresenter;
use App\Services\SeoService;

final class DirectoryController
{
    public function __construct(
        private readonly LocationRepository $locations = new LocationRepository(),
        private readonly ListingRepository $listings = new ListingRepository(),
        private readonly SeoService $seo = new SeoService()
    ) {
    }

    /**
     * @param array<string, string> $params
     */
    public function resolve(Request $request, array $params = []): Response
    {
        unset($params);
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/')), static fn (string $part): bool => $part !== ''));
        $count = count($segments);
        if ($count < 1 || $count > 4) {
            return (new ErrorController())->notFound();
        }

        $category = $this->locations->categoryBySlug($segments[0]);
        $state = $this->locations->stateBySlug($segments[0]);
        if ($count === 1 && $category !== null) {
            return $this->browse($request, 'category', $category['name'] . ' listings', $this->seo->copy('category', (string) $category['name']), [
                'category_id' => (int) $category['id'],
            ], [
                ['name' => 'Home', 'url' => '/'],
                ['name' => (string) $category['name'], 'url' => '/' . $category['slug']],
            ], '/' . $category['slug']);
        }
        if ($state === null) {
            return (new ErrorController())->notFound();
        }
        if ($count === 1) {
            return $this->browse($request, 'state', (string) $state['name'], $this->seo->copy('state', (string) $state['name']), [
                'state_id' => (int) $state['id'],
            ], [
                ['name' => 'Home', 'url' => '/'],
                ['name' => (string) $state['name'], 'url' => '/' . $state['slug']],
            ], '/' . $state['slug']);
        }

        $secondCategory = $this->locations->categoryBySlug($segments[1]);
        if ($count === 2 && $secondCategory !== null) {
            $path = '/' . $state['slug'] . '/' . $secondCategory['slug'];

            return $this->browse($request, 'state-category', $secondCategory['name'] . ' in ' . $state['name'], $this->seo->copy('category', (string) $secondCategory['name'], (string) $state['name']), [
                'state_id' => (int) $state['id'],
                'category_id' => (int) $secondCategory['id'],
            ], [
                ['name' => 'Home', 'url' => '/'],
                ['name' => (string) $state['name'], 'url' => '/' . $state['slug']],
                ['name' => (string) $secondCategory['name'], 'url' => $path],
            ], $path);
        }

        $city = $this->locations->cityBySlug((int) $state['id'], $segments[1]);
        if ($city === null) {
            return (new ErrorController())->notFound();
        }
        if ($count === 2) {
            $path = '/' . $state['slug'] . '/' . $city['slug'];

            return $this->browse($request, 'city', (string) $city['name'], $this->seo->copy('city', (string) $city['name'], (string) $state['name']), [
                'city_id' => (int) $city['id'],
            ], [
                ['name' => 'Home', 'url' => '/'],
                ['name' => (string) $state['name'], 'url' => '/' . $state['slug']],
                ['name' => (string) $city['name'], 'url' => $path],
            ], $path);
        }

        $thirdCategory = $this->locations->categoryBySlug($segments[2]);
        if ($count === 3 && $thirdCategory !== null) {
            $path = '/' . $state['slug'] . '/' . $city['slug'] . '/' . $thirdCategory['slug'];

            return $this->browse($request, 'city-category', $thirdCategory['name'] . ' in ' . $city['name'], $this->seo->copy('category', (string) $thirdCategory['name'], (string) $city['name']), [
                'city_id' => (int) $city['id'],
                'category_id' => (int) $thirdCategory['id'],
            ], [
                ['name' => 'Home', 'url' => '/'],
                ['name' => (string) $state['name'], 'url' => '/' . $state['slug']],
                ['name' => (string) $city['name'], 'url' => '/' . $state['slug'] . '/' . $city['slug']],
                ['name' => (string) $thirdCategory['name'], 'url' => $path],
            ], $path);
        }

        $locality = $this->locations->localityBySlug((int) $city['id'], $segments[2]);
        if ($count === 3 && $locality !== null) {
            $path = '/' . $state['slug'] . '/' . $city['slug'] . '/' . $locality['slug'];

            return $this->browse($request, 'locality', (string) $locality['name'], $this->seo->copy('locality', (string) $locality['name'], (string) $city['name']), [
                'location_id' => (int) $locality['id'],
            ], [
                ['name' => 'Home', 'url' => '/'],
                ['name' => (string) $state['name'], 'url' => '/' . $state['slug']],
                ['name' => (string) $city['name'], 'url' => '/' . $state['slug'] . '/' . $city['slug']],
                ['name' => (string) $locality['name'], 'url' => $path],
            ], $path);
        }

        if ($count === 4 && $thirdCategory !== null && preg_match('/-(\d+)$/', $segments[3], $matches) === 1) {
            return $this->listing((int) $matches[1], $state, $city, $thirdCategory, $segments[3]);
        }

        return (new ErrorController())->notFound();
    }

    /**
     * @param array<string, mixed> $filters
     * @param list<array{name: string, url: string}> $crumbs
     */
    private function browse(Request $request, string $kind, string $heading, string $intro, array $filters, array $crumbs, string $path): Response
    {
        $page = max(1, (int) ($request->query('page', '1') ?? '1'));
        if ($request->query('sort') === 'featured') {
            $filters['sort'] = 'featured';
        }
        $result = $this->listings->search($filters, true, $page, 12);
        $pages = max(1, (int) ceil($result['total'] / 12));
        $robots = $result['total'] > 0 || in_array($kind, ['state', 'category', 'city', 'locality'], true) ? 'index,follow' : 'noindex,follow';
        $seo = $this->seo->page($heading, $intro, $path, $robots);

        return Response::html(View::render('browse', [
            'title' => $heading,
            'seo' => $seo,
            'heading' => $heading,
            'intro' => $intro,
            'listings' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => $pages,
            'path' => $path,
            'crumbs' => $crumbs,
            'schema' => $this->seo->breadcrumbSchema($crumbs),
            'states' => $this->locations->states(),
            'categories' => $this->locations->categories(),
            'sort' => (string) ($filters['sort'] ?? 'newest'),
        ], 'layouts/public'));
    }

    /**
     * @param array<string, mixed> $state
     * @param array<string, mixed> $city
     * @param array<string, mixed> $category
     */
    private function listing(int $id, array $state, array $city, array $category, string $slug): Response
    {
        $listing = $this->listings->find($id, true);
        if ($listing === null || (string) $listing['slug'] !== $slug || (int) $listing['state_id'] !== (int) $state['id'] || (int) $listing['city_id'] !== (int) $city['id'] || (int) $listing['category_id'] !== (int) $category['id']) {
            return (new ErrorController())->notFound();
        }
        $path = ListingPresenter::url($listing);
        $crumbs = [
            ['name' => 'Home', 'url' => '/'],
            ['name' => (string) $listing['state_name'], 'url' => '/' . $listing['state_slug']],
            ['name' => (string) $listing['city_name'], 'url' => '/' . $listing['state_slug'] . '/' . $listing['city_slug']],
            ['name' => (string) $listing['category_name'], 'url' => '/' . $listing['state_slug'] . '/' . $listing['city_slug'] . '/' . $listing['category_slug']],
            ['name' => (string) $listing['title'], 'url' => $path],
        ];
        $robots = (int) $listing['is_indexable'] === 1 ? 'index,follow' : 'noindex,follow';
        $seo = $this->seo->page((string) $listing['title'] . ' in ' . $listing['city_name'], ListingPresenter::excerpt($listing, 180), $path, $robots);

        return Response::html(View::render('listing', [
            'title' => (string) $listing['title'],
            'seo' => $seo,
            'listing' => $listing,
            'images' => $this->listings->images($id),
            'related' => $this->listings->related($listing),
            'crumbs' => $crumbs,
            'schema' => $this->seo->breadcrumbSchema($crumbs),
        ], 'layouts/public'));
    }
}
