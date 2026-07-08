<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Vendor;

final class VendorController extends Controller
{
    public function index(): void
    {
        $vendors = (new Vendor())->all('name asc');
        $this->view('vendors/index', compact('vendors') + ['title' => 'Vendedores']);
    }

    public function store(): void
    {
        (new Vendor())->create([
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'commission_default_percent' => $this->input('commission_default_percent', 0),
            'active' => (int) ($this->input('active', 0) === '1'),
        ]);
        redirect('/vendedores');
    }
}
