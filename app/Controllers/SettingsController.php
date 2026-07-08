<?php

namespace App\Controllers;

use App\Core\Controller;

final class SettingsController extends Controller
{
    public function index(): void
    {
        $this->view('settings/index', ['title' => 'Configurações']);
    }
}
