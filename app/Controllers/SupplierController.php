<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

final class SupplierController extends Controller
{
    public function index():void
    {
        $db=Database::connection();$suppliers=$db->query('select * from suppliers_3d order by active desc,name')->fetchAll();$editingSupplier=null;$id=(int)$this->input('edit');
        if($id>0){$statement=$db->prepare('select * from suppliers_3d where id=:id');$statement->execute(['id'=>$id]);$editingSupplier=$statement->fetch()?:null;}
        $this->view('suppliers/index',compact('suppliers','editingSupplier')+['title'=>'Fornecedores']);
    }

    public function store():void
    {
        $id=(int)$this->input('id');$name=trim((string)$this->input('name'));
        if($name===''){$_SESSION['flash']='Informe o nome do fornecedor.';redirect('/fornecedores'.($id?'?edit='.$id:''));}
        $data=['name'=>$name,'document'=>$this->input('document_number')?:null,'contact'=>$this->input('contact_name')?:null,'email'=>$this->input('email')?:null,'phone'=>$this->input('phone')?:null,'lead'=>max(0,(int)$this->input('lead_time_days')),'notes'=>$this->input('notes')?:null,'active'=>$this->input('active','1')==='1'?1:0];$db=Database::connection();
        if($id>0){$statement=$db->prepare('update suppliers_3d set name=:name,document_number=:document,contact_name=:contact,email=:email,phone=:phone,lead_time_days=:lead,notes=:notes,active=:active where id=:id');$statement->execute($data+['id'=>$id]);$_SESSION['flash_success']='Fornecedor atualizado.';}
        else{$statement=$db->prepare('insert into suppliers_3d(name,document_number,contact_name,email,phone,lead_time_days,notes,active) values(:name,:document,:contact,:email,:phone,:lead,:notes,:active)');$statement->execute($data);$_SESSION['flash_success']='Fornecedor cadastrado.';}
        redirect('/fornecedores');
    }
}
