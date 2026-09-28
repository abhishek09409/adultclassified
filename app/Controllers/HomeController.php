<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Repositories\ListingRepository;
use App\Repositories\LocationRepository;
use App\Services\SeoService;

final class HomeController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $listings = new ListingRepository();
        $locations = new LocationRepository();
        $seoService = new SeoService();
        $latest = $listings->search([], true, 1, 8);
        $recent = $listings->search([], true, 1, 4);
        $featured = $listings->search(['featured' => true, 'sort' => 'featured'], true, 1, 4);
        $seo = $seoService->page(
            'Adult classified directory',
            $seoService->copy('home', 'Home'),
            '/'
        );

        return Response::html(View::render('home', [
            'title' => 'Adult classified directory',
            'seo' => $seo,
            'states' => array_values(array_filter($locations->states(), static fn (array $row): bool => $row['status'] === 'active')),
            'categories' => $locations->categories(),
            'latest' => $latest['rows'],
            'recent' => $recent['rows'],
            'featured' => $featured['rows'],
            'popularCities' => $listings->popularCities(8),
            'categoryCounts' => $listings->categoryCounts(),
            'intro' => $seoService->copy('home', 'Home'),
        ], 'layouts/public'));
    }
}
