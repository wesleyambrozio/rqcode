<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

final class AuthController extends Controller
{
    public function login(): void
    {
        $this->view('auth/login', ['title' => 'Entrar'], 'auth');
    }

    public function authenticate(): void
    {
        if (Auth::attempt((string) $this->input('email'), (string) $this->input('password'))) {
            redirect('/dashboard');
        }

        $_SESSION['flash'] = 'E-mail ou senha invalidos.';
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }
}
