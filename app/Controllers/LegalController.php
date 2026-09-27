<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Services\RateLimiter;
use App\Services\SeoService;

final class LegalController
{
    /**
     * @param array<string, string> $params
     */
    public function terms(Request $request, array $params = []): Response
    {
        return $this->page('Terms', 'terms', 'How this adult directory may be used. This page is a template and is flagged for legal review.');
    }

    /**
     * @param array<string, string> $params
     */
    public function privacy(Request $request, array $params = []): Response
    {
        return $this->page('Privacy', 'privacy', 'What information the directory stores, and what it does not publish.');
    }

    /**
     * @param array<string, string> $params
     */
    public function contentPolicy(Request $request, array $params = []): Response
    {
        return $this->page('Content policy', 'content-policy', 'Rules for listings, including the 21+ age rule and prohibited content.');
    }

    /**
     * @param array<string, string> $params
     */
    public function contact(Request $request, array $params = []): Response
    {
        unset($params);
        $notice = null;
        if ($request->method() === 'POST') {
            $notice = $this->storeContact($request);
        }
        $seo = (new SeoService())->page('Contact', 'Contact the directory operators.', '/contact', 'noindex,follow');

        return Response::html(View::render('contact', [
            'title' => 'Contact',
            'seo' => $seo,
            'notice' => $notice,
        ], 'layouts/public'));
    }

    private function page(string $title, string $template, string $description): Response
    {
        $seo = (new SeoService())->page($title, $description, '/' . $template);

        return Response::html(View::render('legal/' . $template, [
            'title' => $title,
            'seo' => $seo,
        ], 'layouts/public'));
    }

    private function storeContact(Request $request): string
    {
        if (!Csrf::verify($request->input('_token'))) {
            return 'The form expired. Please try again.';
        }
        $limiter = new RateLimiter();
        if ($limiter->tooMany('contact', $request->ip(), 5, 3600)) {
            return 'Please wait before sending another message.';
        }
        $email = (string) $request->input('email', '');
        $message = (string) $request->input('message', '');
        $name = mb_substr((string) $request->input('name', ''), 0, 120);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($message) < 10) {
            return 'Enter a valid email and a message of at least 10 characters.';
        }
        Database::connection()->prepare(
            'INSERT INTO contact_messages (name, email, message) VALUES (:name, :email, :message)'
        )->execute([
            'name' => $name !== '' ? $name : null,
            'email' => $email,
            'message' => mb_substr($message, 0, 5000),
        ]);
        $limiter->hit('contact', $request->ip());
        Session::flash('success', 'Your message has been received.');

        return 'Your message has been received.';
    }
}
