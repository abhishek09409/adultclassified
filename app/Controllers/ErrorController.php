<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class ErrorController
{
    /**
     * @param array<string, string> $params
     */
    public function notFound(?Request $request = null, array $params = []): Response
    {
        unset($request, $params);

        return Response::html(View::render('errors/404', [
            'title' => 'Page not found',
        ], 'layouts/simple'), 404);
    }

    /**
     * @param array<string, string> $params
     */
    public function forbidden(?Request $request = null, array $params = []): Response
    {
        unset($request, $params);

        return Response::html(View::render('errors/403', [
            'title' => 'Forbidden',
        ], 'layouts/simple'), 403);
    }

    public function serverError(): Response
    {
        return Response::html(View::render('errors/500', [
            'title' => 'Something went wrong',
            'detail' => null,
        ], 'layouts/simple'), 500);
    }
}
