<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Helpers\Csrf;
use App\Services\RateLimiter;
use App\Services\SeoService;

final class ReportController
{
    /** @var array<string, string> */
    public const REASONS = [
        'illegal_content' => 'Illegal content',
        'minor_age_concern' => 'Minor/age concern',
        'fraud' => 'Fraud/misrepresentation',
        'harassment' => 'Harassment',
        'copyright' => 'Copyright/image complaint',
        'other' => 'Other',
    ];

    /**
     * @param array<string, string> $params
     */
    public function form(Request $request, array $params = []): Response
    {
        unset($params);
        $listingId = $request->integer('listing_id');
        $seo = (new SeoService())->page('Report a listing', 'Report a directory listing for review.', '/report', 'noindex,follow');

        return Response::html($this->view($seo, $listingId, 'report', null));
    }

    /**
     * @param array<string, string> $params
     */
    public function removal(Request $request, array $params = []): Response
    {
        unset($params);
        $listingId = $request->integer('listing_id');
        $seo = (new SeoService())->page('Request removal', 'Ask the directory to review or remove a listing.', '/remove-listing', 'noindex,follow');

        return Response::html($this->view($seo, $listingId, 'remove', null));
    }

    /**
     * @param array<string, string> $params
     */
    public function store(Request $request, array $params = []): Response
    {
        unset($params);
        $kind = $request->input('kind') === 'takedown' ? 'takedown' : 'report';
        $path = $kind === 'takedown' ? '/remove-listing' : '/report';
        $seo = (new SeoService())->page($kind === 'takedown' ? 'Request removal' : 'Report a listing', 'Report a directory listing for review.', $path, 'noindex,follow');
        if (!Csrf::verify($request->input('_token'))) {
            return Response::html($this->view($seo, $request->integer('listing_id'), $kind === 'takedown' ? 'remove' : 'report', 'The form expired. Please try again.'), 422);
        }
        $limiter = new RateLimiter();
        if ($limiter->tooMany('report', $request->ip(), 8, 3600)) {
            return Response::html($this->view($seo, $request->integer('listing_id'), $kind === 'takedown' ? 'remove' : 'report', 'Please wait before submitting another report.'), 429);
        }
        $listingId = $request->integer('listing_id');
        $reason = (string) $request->input('reason', '');
        $email = (string) $request->input('email', '');
        $message = trim((string) $request->input('message', ''));
        if ($listingId === null || !isset(self::REASONS[$reason]) || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10) {
            return Response::html($this->view($seo, $listingId, $kind === 'takedown' ? 'remove' : 'report', 'Check the listing, reason, email, and message.'), 422);
        }
        $exists = Database::connection()->prepare('SELECT id FROM listings WHERE id = :id LIMIT 1');
        $exists->execute(['id' => $listingId]);
        if ($exists->fetch() === false) {
            return Response::html($this->view($seo, $listingId, $kind === 'takedown' ? 'remove' : 'report', 'That listing was not found.'), 404);
        }
        Database::connection()->prepare(
            'INSERT INTO reports (listing_id, reason, email, message, status, kind) VALUES (:listing_id, :reason, :email, :message, :status, :kind)'
        )->execute([
            'listing_id' => $listingId,
            'reason' => $reason,
            'email' => $email,
            'message' => mb_substr($message, 0, 5000),
            'status' => 'open',
            'kind' => $kind,
        ]);
        $limiter->hit('report', $request->ip());

        return Response::html($this->view($seo, $listingId, $kind === 'takedown' ? 'remove' : 'report', 'Thank you. A moderator can review this report.'), 201);
    }

    /**
     * @param array<string, mixed> $seo
     */
    private function view(array $seo, ?int $listingId, string $template, ?string $notice): string
    {
        return View::render($template === 'remove' ? 'remove' : 'report', [
            'title' => (string) $seo['title'],
            'seo' => $seo,
            'listingId' => $listingId,
            'reasons' => self::REASONS,
            'notice' => $notice,
            'kind' => $template === 'remove' ? 'takedown' : 'report',
        ], 'layouts/public');
    }
}
