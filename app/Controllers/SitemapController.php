<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\ListingPresenter;

final class SitemapController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $base = $this->base();
        $body = $this->xml('sitemapindex', 'sitemap', [
            $base . '/sitemap-pages.xml',
            $base . '/sitemap-categories.xml',
            $base . '/sitemap-locations.xml',
            $base . '/sitemap-listings.xml',
        ]);

        return new Response(200, $body, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * @param array<string, string> $params
     */
    public function pages(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $base = $this->base();

        return $this->urlset([$base . '/', $base . '/terms', $base . '/privacy', $base . '/content-policy', $base . '/contact', $base . '/report', $base . '/remove-listing']);
    }

    /**
     * @param array<string, string> $params
     */
    public function categories(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $base = $this->base();
        $urls = [];
        foreach (Database::connection()->query("SELECT slug FROM categories WHERE status = 'active'")->fetchAll() as $row) {
            $urls[] = $base . '/' . $row['slug'];
        }

        return $this->urlset($urls);
    }

    /**
     * @param array<string, string> $params
     */
    public function locations(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $base = $this->base();
        $urls = [];
        $pdo = Database::connection();
        foreach ($pdo->query("SELECT slug FROM states WHERE status = 'active'")->fetchAll() as $row) {
            $urls[] = $base . '/' . $row['slug'];
        }
        foreach ($pdo->query("SELECT s.slug AS state_slug, ci.slug AS city_slug FROM cities ci INNER JOIN states s ON s.id = ci.state_id WHERE ci.status = 'active' AND s.status = 'active'")->fetchAll() as $row) {
            $urls[] = $base . '/' . $row['state_slug'] . '/' . $row['city_slug'];
        }
        foreach ($pdo->query(
            "SELECT s.slug AS state_slug, ci.slug AS city_slug, loc.slug AS locality_slug
             FROM locations loc
             INNER JOIN cities ci ON ci.id = loc.city_id
             INNER JOIN states s ON s.id = ci.state_id
             WHERE loc.status = 'active' AND ci.status = 'active' AND s.status = 'active'"
        )->fetchAll() as $row) {
            $urls[] = $base . '/' . $row['state_slug'] . '/' . $row['city_slug'] . '/' . $row['locality_slug'];
        }

        return $this->urlset($urls);
    }

    /**
     * @param array<string, string> $params
     */
    public function listings(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $base = $this->base();
        $rows = Database::connection()->query(
            "SELECT l.slug, s.slug AS state_slug, ci.slug AS city_slug, c.slug AS category_slug
             FROM listings l
             INNER JOIN states s ON s.id = l.state_id
             INNER JOIN cities ci ON ci.id = l.city_id
             INNER JOIN categories c ON c.id = l.category_id
             WHERE l.status = 'published' AND l.moderation_status = 'approved' AND l.is_indexable = 1"
        )->fetchAll();
        $urls = [];
        foreach ($rows as $row) {
            $urls[] = $base . ListingPresenter::url($row);
        }

        return $this->urlset($urls);
    }

    /**
     * @param array<string, string> $params
     */
    public function robots(Request $request, array $params = []): Response
    {
        unset($request, $params);
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /search\nSitemap: " . $this->base() . "/sitemap.xml\n";

        return new Response(200, $body, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * @param list<string> $urls
     */
    private function urlset(array $urls): Response
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $body .= '<url><loc>' . $this->escape($url) . '</loc></url>';
        }
        $body .= '</urlset>';

        return new Response(200, $body, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * @param list<string> $urls
     */
    private function xml(string $root, string $child, array $urls): string
    {
        $body = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<' . $root . ' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $body .= '<' . $child . '><loc>' . $this->escape($url) . '</loc></' . $child . '>';
        }
        $body .= '</' . $root . '>';

        return $body;
    }

    private function base(): string
    {
        return rtrim((string) \App\Core\Config::get('app.url', ''), '/');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
