<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class SupplierController extends Controller
{
    public function index():void
    {
        $suppliers=Database::connection()->query('select * from suppliers_3d order by active desc,name')->fetchAll();
        $this->view('suppliers/index',compact('suppliers')+['title'=>'Fornecedores']);
    }

    public function store():void
    {
        $name=trim((string)$this->input('name'));
        if($name===''){$_SESSION['flash']='Informe o nome do fornecedor.';redirect('/fornecedores');}
        $statement=Database::connection()->prepare('insert into suppliers_3d(name,document_number,contact_name,email,phone,lead_time_days,notes) values(:name,:document,:contact,:email,:phone,:lead,:notes)');
        $statement->execute(['name'=>$name,'document'=>$this->input('document_number')?:null,'contact'=>$this->input('contact_name')?:null,'email'=>$this->input('email')?:null,'phone'=>$this->input('phone')?:null,'lead'=>max(0,(int)$this->input('lead_time_days')),'notes'=>$this->input('notes')?:null]);
        $_SESSION['flash_success']='Fornecedor cadastrado.';redirect('/fornecedores');
    }
}
