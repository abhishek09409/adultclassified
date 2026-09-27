<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\ListingRepository;
use App\Repositories\LocationRepository;
use App\Services\SeoService;

final class SearchController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($params);
        $filters = [
            'q' => (string) $request->query('q', ''),
            'sort' => $request->query('sort') === 'featured' ? 'featured' : 'newest',
        ];
        foreach (['category_id', 'state_id', 'city_id', 'location_id'] as $field) {
            $value = $request->query($field);
            if ($value !== null && ctype_digit($value)) {
                $filters[$field] = $value;
            }
        }
        if ($request->query('featured') === '1') {
            $filters['featured'] = true;
        }
        $page = max(1, (int) ($request->query('page', '1') ?? '1'));
        $result = (new ListingRepository())->search($filters, true, $page, 12);
        $pages = max(1, (int) ceil($result['total'] / 12));
        $seo = (new SeoService())->page('Search listings', 'Search published adult directory listings by keyword, category, and place.', '/search', 'noindex,follow');

        return Response::html(View::render('search', [
            'title' => 'Search',
            'seo' => $seo,
            'filters' => $filters,
            'listings' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => $pages,
            'states' => (new LocationRepository())->states(),
            'categories' => (new LocationRepository())->categories(),
        ], 'layouts/public'));
    }
}
