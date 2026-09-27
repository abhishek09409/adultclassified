<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

final class SeoService
{
    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    public function page(string $title, string $description, string $path, string $robots = 'index,follow', array $overrides = []): array
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        $canonical = $base . ($path === '' ? '/' : $path);
        $description = mb_substr(trim($description), 0, 300);

        return $overrides + [
            'title' => mb_substr($title, 0, 180),
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og_title' => mb_substr($title, 0, 180),
            'og_description' => $description,
            'path' => $path === '' ? '/' : $path,
        ];
    }

    /**
     * @param array<string, mixed> $listing
     */
    public function storeListing(array $listing): void
    {
        $path = ListingPresenter::url($listing);
        $title = mb_substr((string) $listing['title'] . ' in ' . $listing['city_name'], 0, 180);
        $description = mb_substr(ListingPresenter::excerpt($listing, 180), 0, 300);
        $seo = $this->page($title, $description, $path);
        $statement = Database::connection()->prepare(
            'INSERT INTO seo_metadata (page_type, target_type, target_id, path, title, meta_description, canonical_url, robots, og_title, og_description)
             VALUES (:page_type, :target_type, :target_id, :path, :title, :meta_description, :canonical_url, :robots, :og_title, :og_description)
             ON DUPLICATE KEY UPDATE title = VALUES(title), meta_description = VALUES(meta_description), canonical_url = VALUES(canonical_url), robots = VALUES(robots), og_title = VALUES(og_title), og_description = VALUES(og_description), target_id = VALUES(target_id)'
        );
        $statement->execute([
            'page_type' => 'listing',
            'target_type' => 'listing',
            'target_id' => (int) $listing['id'],
            'path' => $path,
            'title' => $seo['title'],
            'meta_description' => $seo['description'],
            'canonical_url' => $seo['canonical'],
            'robots' => 'index,follow',
            'og_title' => $seo['og_title'],
            'og_description' => $seo['og_description'],
        ]);
    }

    /**
     * @param list<array{name: string, url: string}> $crumbs
     * @return array<string, mixed>
     */
    public function copy(string $kind, string $name, string $context = ''): string
    {
        $pools = [
            'state' => [
                '{name} is organized in this directory by city and locality. Choose a city to see published adult listings for that place, or pick a category if you already know which section you need.',
                'The {name} pages list cities that have been added to the directory. A city page shows localities and any published listings that passed review.',
                'Use {name} as the starting point for browsing. Listings stay in their own category, and empty cities simply show the localities that are available.',
            ],
            'city' => [
                '{name} listings are grouped by locality and category. Published records show a place, an age label, and a short non-explicit description.',
                'This {name} page is a city index. Open a locality to narrow the results, or use a category link to stay inside one section.',
                'Records in {name} appear here only after they are published and approved. Verification badges are shown only when a moderator has recorded a real check.',
            ],
            'locality' => [
                '{name} is a locality page. It lists published records saved with this place name and links back to the wider city.',
                'Browse {name} to see classifieds for this locality. Related cities and categories remain available from the navigation.',
                'The {name} page avoids repeating the same paragraph used on other localities. Listings, when they exist, are the useful part of the page.',
            ],
            'category' => [
                'The {name} section collects adult classifieds aged 21 and over. Filter by state and city rather than reading one national list.',
                '{name} pages are directory listings. They do not add reviews, licenses, or identity claims that were not supplied by a moderator.',
                'Use the {name} category together with a location filter. That keeps the results specific to a place.',
            ],
            'home' => [
                'This directory lists adult classifieds for people aged 21 and over. Start with a category and a state, then narrow the results to a city and locality.',
                'Published listings show a place, a category, and a short description. A verified badge is used only after a person has actually reviewed that profile.',
                'You can report a listing, request removal, or read the content policy from the footer. Automated posts are limited and checked before they go live.',
            ],
        ];
        $pool = $pools[$kind] ?? $pools['home'];
        $text = $pool[abs(crc32($kind . '|' . $name . '|' . $context)) % count($pool)];

        return str_replace('{name}', $name, $text);
    }

    public function breadcrumbSchema(array $crumbs): array
    {
        $base = rtrim((string) Config::get('app.url', ''), '/');
        $items = [];
        foreach ($crumbs as $index => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['name'],
                'item' => $base . $crumb['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }
}
