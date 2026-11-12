<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\View;

final class AuthController
{
    public function showLogin(Request $request, array $params = []): Response
    {
        if (Auth::check()) {
            return Response::redirect(url('/admin'));
        }

        return Response::html(View::render('admin/login', [
            'title' => 'Admin Sign In',
            'email' => '',
            'errors' => [],
        ]));
    }

    public function login(Request $request, array $params = []): Response
    {
        if (Auth::check()) {
            return Response::redirect(url('/admin'));
        }

        $email = trim((string) $request->post('email', ''));
        $password = (string) $request->post('password', '');
        $token = $request->post('_token');

        if (!is_string($token) || !Csrf::validate($token)) {
            return $this->renderLogin(
                $email,
                ['_token' => 'Your session expired. Please try again.'],
                419
            );
        }

        $errors = Validator::make(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email|max:190', 'password' => 'required|max:255']
        );

        if ($errors !== []) {
            return $this->renderLogin($email, $errors, 422);
        }

        if (!Auth::attempt($email, $password)) {
            return $this->renderLogin($email, ['email' => 'Invalid email or password.'], 422);
        }

        flash('success', 'Welcome back, ' . (Auth::user()['name'] ?? 'Admin') . '.');

        return Response::redirect(url('/admin'));
    }

    public function logout(Request $request, array $params = []): Response
    {
        $token = $request->post('_token');

        if (!is_string($token) || !Csrf::validate($token)) {
            return Response::html('Invalid or expired security token.', 419);
        }

        Auth::logout();
        flash('success', 'You have been signed out.');

        return Response::redirect(url('/login'));
    }

    private function renderLogin(string $email, array $errors, int $status): Response
    {
        return Response::html(View::render('admin/login', [
            'title' => 'Admin Sign In',
            'email' => $email,
            'errors' => $errors,
        ]), $status);
    }
}
