<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Helpers\Csrf;
use App\Services\AuthService;

final class AuthController
{
    /**
     * @param array<string, string> $params
     */
    public function login(Request $request, array $params = []): Response
    {
        unset($params);
        if ((new AuthService())->user() !== null) {
            return Response::redirect('/admin');
        }
        $error = Session::pullFlash('error');
        if ($request->method() === 'POST') {
            if (!Csrf::verify($request->input('_token'))) {
                $error = 'The form expired. Please try again.';
            } else {
                $message = (new AuthService())->attempt((string) $request->input('email', ''), (string) $request->input('password', ''), $request->ip());
                if ($message === '') {
                    return Response::redirect('/admin');
                }
                $error = $message;
            }
        }

        return Response::html(View::render('admin/login', [
            'title' => 'Admin sign in',
            'error' => $error,
        ], 'layouts/simple'));
    }

    /**
     * @param array<string, string> $params
     */
    public function logout(Request $request, array $params = []): Response
    {
        unset($params);
        if (!Csrf::verify($request->input('_token'))) {
            return Response::redirect('/admin');
        }
        (new AuthService())->logout($request->ip());

        return Response::redirect('/admin/login');
    }
}
