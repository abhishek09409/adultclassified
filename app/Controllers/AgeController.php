<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Helpers\Csrf;
use App\Services\AgeGate;

final class AgeController
{
    /**
     * @param array<string, string> $params
     */
    public function prompt(Request $request, array $params = []): Response
    {
        unset($params);
        if ($request->method() === 'POST') {
            return $this->confirm($request);
        }

        return Response::html(View::render('age', [
            'title' => 'Adults 21 and over',
            'next' => $request->path() === '/age' ? '/' : $request->path(),
        ], 'layouts/simple'));
    }

    /**
     * @param array<string, string> $params
     */
    public function confirm(Request $request, array $params = []): Response
    {
        unset($params);
        if (!Csrf::verify($request->input('_token'))) {
            return Response::redirect('/age');
        }

        $choice = $request->input('choice');
        if ($choice !== 'enter') {
            return Response::html(View::render('age-declined', [
                'title' => 'Adults only',
            ], 'layouts/simple'), 403);
        }

        setcookie('directory_age', AgeGate::token(), [
            'expires' => time() + 60 * 60 * 24 * 30,
            'path' => '/',
            'secure' => $request->isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $next = (string) $request->input('next', '/');

        return Response::redirect(Response::isSafePath($next) ? $next : '/');
    }
}
