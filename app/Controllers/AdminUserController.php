<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use PDO;
use Throwable;

final class AdminUserController extends Controller
{
    public function index():void
    {
        $this->allowOwner();$db=Database::connection();
        $users=$db->query('select id,name,email,role,active,created_at,updated_at from admin_users order by active desc,name')->fetchAll();
        $editing=null;$id=(int)$this->input('edit');
        if($id>0){$statement=$db->prepare('select id,name,email,role,active from admin_users where id=:id');$statement->execute(['id'=>$id]);$editing=$statement->fetch(PDO::FETCH_ASSOC)?:null;}
        $this->view('admin-users/index',compact('users','editing')+['title'=>'Usuarios administrativos']);
    }

    public function store():void
    {
        $this->allowOwner();$name=mb_substr(trim((string)$this->input('name')),0,120);$email=mb_strtolower(trim((string)$this->input('email')));$password=(string)$this->input('password');$role=$this->role((string)$this->input('role'));
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($password)<12){$this->fail('Informe nome, e-mail valido e senha com pelo menos 12 caracteres.');}
        $db=Database::connection();try{$db->beginTransaction();$statement=$db->prepare('insert into admin_users(name,email,password_hash,role,active) values(:name,:email,:password,:role,1)');$statement->execute(['name'=>$name,'email'=>$email,'password'=>password_hash($password,PASSWORD_DEFAULT),'role'=>$role]);$id=(int)$db->lastInsertId();$this->audit('create_admin_user',$id);$db->commit();$_SESSION['flash_success']='Usuario criado com sucesso.';}catch(Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='O e-mail ja esta cadastrado ou os dados sao invalidos.';}
        redirect('/usuarios-administrativos');
    }

    public function update():void
    {
        $this->allowOwner();$id=(int)$this->input('id');$user=$this->find($id);if(!$user)$this->fail('Usuario nao encontrado.');
        $name=mb_substr(trim((string)$this->input('name')),0,120);$email=mb_strtolower(trim((string)$this->input('email')));$password=(string)$this->input('password');$role=$this->role((string)$this->input('role'));$active=$this->input('active')==='1'?1:0;
        if($id===(int)Auth::user()['id']){$role='owner';$active=1;}
        if($name===''||!filter_var($email,FILTER_VALIDATE_EMAIL)||($password!==''&&strlen($password)<12))$this->fail('Revise nome, e-mail e senha. Senhas novas exigem 12 caracteres.',$id);
        if($user['role']==='owner'&&($role!=='owner'||$active===0)&&$this->activeOwners()<=1)$this->fail('Nao e permitido remover ou desativar o ultimo proprietario.',$id);
        $db=Database::connection();try{$db->beginTransaction();$sql='update admin_users set name=:name,email=:email,role=:role,active=:active,updated_at=current_timestamp'.($password!==''?',password_hash=:password':'').' where id=:id';$data=['name'=>$name,'email'=>$email,'role'=>$role,'active'=>$active,'id'=>$id];if($password!=='')$data['password']=password_hash($password,PASSWORD_DEFAULT);$db->prepare($sql)->execute($data);$this->audit('update_admin_user',$id);$db->commit();$_SESSION['flash_success']='Usuario atualizado.';}catch(Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Nao foi possivel atualizar. Verifique se o e-mail ja esta em uso.';}
        redirect('/usuarios-administrativos');
    }

    public function delete():void
    {
        $this->allowOwner();$id=(int)$this->input('id');$user=$this->find($id);if(!$user)$this->fail('Usuario nao encontrado.');
        if($id===(int)Auth::user()['id'])$this->fail('Voce nao pode excluir a propria conta.');
        if($user['role']==='owner'&&$this->activeOwners()<=1)$this->fail('Nao e permitido excluir o ultimo proprietario.');
        $db=Database::connection();try{$db->beginTransaction();$db->prepare('update admin_users set active=0,updated_at=current_timestamp where id=:id')->execute(['id'=>$id]);$this->audit('deactivate_admin_user',$id);$db->commit();$_SESSION['flash_success']='Usuario desativado. O historico foi preservado.';}catch(Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Nao foi possivel desativar o usuario.';}redirect('/usuarios-administrativos');
    }

    private function find(int $id):?array{$statement=Database::connection()->prepare('select id,name,email,role,active from admin_users where id=:id');$statement->execute(['id'=>$id]);return $statement->fetch(PDO::FETCH_ASSOC)?:null;}
    private function activeOwners():int{return(int)Database::connection()->query("select count(*) from admin_users where role='owner' and active=1")->fetchColumn();}
    private function role(string $role):string{return in_array($role,['owner','admin','accountant'],true)?$role:'admin';}
    private function audit(string $action,int $id):void{$statement=Database::connection()->prepare('insert into audit_logs(admin_user_id,action,entity,entity_id,ip_address) values(:user,:action,\'admin_user\',:id,:ip)');$statement->execute(['user'=>Auth::user()['id'],'action'=>$action,'id'=>(string)$id,'ip'=>$_SERVER['REMOTE_ADDR']??null]);}
    private function fail(string $message,?int $edit=null):never{$_SESSION['flash']=$message;redirect('/usuarios-administrativos'.($edit?'?edit='.$edit:''));}
    private function allowOwner():void{if((Auth::user()['role']??'')!=='owner'){http_response_code(403);exit('Acesso exclusivo do proprietario.');}}
}
