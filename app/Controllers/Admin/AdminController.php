<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\ErrorController;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Services\AuthService;
use App\Services\Gate;

abstract class AdminController
{
    protected function guard(Request $request, string $ability): ?Response
    {
        $admin = (new AuthService())->user();
        if ($admin === null) {
            return Response::redirect('/admin/login');
        }
        if (!Gate::allows($admin, $ability)) {
            return (new ErrorController())->forbidden();
        }
        if ($request->method() === 'POST' && !Csrf::verify($request->input('_token'))) {
            Session::flash('error', 'The form expired. Please try again.');

            return Response::redirect($request->path());
        }

        return null;
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(string $template, array $data): Response
    {
        $data['admin'] = (new AuthService())->user();
        $data['success'] = Session::pullFlash('success');
        $data['error'] = Session::pullFlash('error');

        return Response::html(View::render($template, $data, 'layouts/admin'));
    }
}
