<?php

namespace App\Controllers;

use App\Core\Controller;

final class PublicController extends Controller
{
    public function soon(): void
    {
        $this->view('public/soon', ['title' => 'Em breve'], 'public');
    }
}
