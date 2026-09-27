<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Config;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

final class HomeController
{
    /**
     * @param array<string, string> $params
     */
    public function index(Request $request, array $params = []): Response
    {
        unset($request, $params);

        $html = View::render('home', [
            'title' => (string) Config::get('app.name', 'Adult Directory'),
            'minimumAge' => (int) Config::get('app.minimum_age', 21),
        ]);

        return Response::html($html);
    }
}
