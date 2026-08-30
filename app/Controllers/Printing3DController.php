<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use Throwable;

final class Printing3DController extends Controller
{
    public function index(): void
    {
        $this->allowAdmin();
        $db=Database::connection();
        $suppliers=$db->query('select * from suppliers_3d order by active desc,name')->fetchAll();
        $categories=$db->query('select * from product_categories_3d order by active desc,name')->fetchAll();
        $filaments=$db->query('select f.*,s.name supplier_name,(f.purchase_price/nullif(f.spool_net_weight_g,0)) cost_per_g from filaments_3d f left join suppliers_3d s on s.id=f.supplier_id order by f.active desc,f.name')->fetchAll();
        $editingFilament=null;
        $editingFilamentId=(int)$this->input('edit_filament');
        if($editingFilamentId>0){$statement=$db->prepare('select * from filaments_3d where id=:id');$statement->execute(['id'=>$editingFilamentId]);$editingFilament=$statement->fetch()?:null;}
        $products=$db->query("select p.*,c.name category_name,coalesce(sum(pf.quantity_g*(f.purchase_price/nullif(f.spool_net_weight_g,0))),0) filament_cost from products_3d p left join product_categories_3d c on c.id=p.category_id left join product_filaments_3d pf on pf.product_id=p.id left join filaments_3d f on f.id=pf.filament_id group by p.id order by p.active desc,p.name")->fetchAll();
        foreach($products as &$product) $product['unit_cost']=$this->productCost($product,(float)$product['filament_cost']);
        $channels=$db->query('select * from sales_channels_3d order by active desc,name')->fetchAll();
        $orders=$db->query('select o.*,p.name product_name,p.sku from production_orders_3d o join products_3d p on p.id=o.product_id order by o.created_at desc limit 30')->fetchAll();
        $sales=$db->query('select s.*,p.name product_name,c.name channel_name from sales_3d s join products_3d p on p.id=s.product_id left join sales_channels_3d c on c.id=s.channel_id order by s.sold_at desc,s.id desc limit 30')->fetchAll();
        $summary=['products'=>count($products),'stock_cost'=>array_sum(array_map(fn($p)=>(float)$p['stock_quantity']*(float)$p['unit_cost'],$products)),'low_filaments'=>count(array_filter($filaments,fn($f)=>(float)$f['current_weight_g']<=(float)$f['minimum_stock_g'])),'profit'=>array_sum(array_column($sales,'net_profit'))];
        $allowedViews=['dashboard','pecas','categorias','filamentos','fornecedores','producao','vendas','canais'];
        $section=in_array((string)$this->input('view'),$allowedViews,true)?(string)$this->input('view'):'dashboard';
        $this->view('printing3d/index',compact('suppliers','categories','filaments','editingFilament','products','channels','orders','sales','summary','section')+['title'=>'Producao 3D']);
    }

    public function supplier(): void
    {
        $this->allowAdmin();
        $statement=Database::connection()->prepare('insert into suppliers_3d(name,document_number,contact_name,email,phone,lead_time_days,notes) values(:name,:document,:contact,:email,:phone,:lead,:notes)');
        $statement->execute(['name'=>trim((string)$this->input('name')),'document'=>$this->input('document_number')?:null,'contact'=>$this->input('contact_name')?:null,'email'=>$this->input('email')?:null,'phone'=>$this->input('phone')?:null,'lead'=>(int)$this->input('lead_time_days'),'notes'=>$this->input('notes')?:null]);
        $_SESSION['flash_success']='Fornecedor cadastrado.'; redirect('/impressao-3d?view=fornecedores');
    }

    public function filament(): void
    {
        $this->allowAdmin();
        $id=(int)$this->input('id');
        $weight=(float)$this->input('spool_net_weight_g');
        $currentWeight=(float)$this->input('current_weight_g');
        $name=trim((string)$this->input('name'));
        $price=(float)$this->input('purchase_price');
        if($name===''||$weight<=0||$price<0){$_SESSION['flash']='Informe identificacao, peso e custo validos.';redirect('/impressao-3d?view=filamentos'.($id?'&edit_filament='.$id:''));}
        if($id===0&&$currentWeight<=0)$currentWeight=$weight;
        if($currentWeight<0)$currentWeight=0;
        $db=Database::connection();
        $data=['supplier'=>(int)$this->input('supplier_id')?:null,'name'=>$name,'material'=>$this->input('material'),'brand'=>$this->input('brand')?:null,'color'=>$this->input('color')?:null,'diameter'=>(float)$this->input('diameter_mm',1.75),'weight'=>$weight,'current'=>$currentWeight,'price'=>$price,'batch'=>$this->input('batch_code')?:null,'purchase_date'=>$this->input('purchase_date')?:null,'minimum'=>max(0,(float)$this->input('minimum_stock_g'))];
        if($id>0){
            $statement=$db->prepare('update filaments_3d set supplier_id=:supplier,name=:name,material=:material,brand=:brand,color=:color,diameter_mm=:diameter,spool_net_weight_g=:weight,current_weight_g=:current,purchase_price=:price,batch_code=:batch,purchase_date=:purchase_date,minimum_stock_g=:minimum where id=:id');
            $statement->execute($data+['id'=>$id]);
            if($statement->rowCount()===0){$check=$db->prepare('select id from filaments_3d where id=:id');$check->execute(['id'=>$id]);if(!$check->fetchColumn()){$_SESSION['flash']='Filamento nao encontrado.';redirect('/impressao-3d?view=filamentos');}}
            $_SESSION['flash_success']='Filamento atualizado.';
        }else{
            $statement=$db->prepare('insert into filaments_3d(supplier_id,name,material,brand,color,diameter_mm,spool_net_weight_g,current_weight_g,purchase_price,batch_code,purchase_date,minimum_stock_g) values(:supplier,:name,:material,:brand,:color,:diameter,:weight,:current,:price,:batch,:purchase_date,:minimum)');
            $statement->execute($data);$id=(int)$db->lastInsertId();$db->prepare('update filaments_3d set inventory_code=:code where id=:id')->execute(['code'=>'FIL-'.str_pad((string)$id,6,'0',STR_PAD_LEFT),'id'=>$id]);$_SESSION['flash_success']='Filamento cadastrado.';
        }
        redirect('/impressao-3d?view=filamentos');
    }

    public function product(): void
    {
        $this->allowAdmin();
        $db=Database::connection(); $image=null;
        $sku=trim((string)$this->input('sku')); $name=trim((string)$this->input('name'));
        if($sku===''||$name===''){$_SESSION['flash']='Informe SKU e nome da peca.';redirect('/impressao-3d?view=pecas');}
        $allowedImages=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
        if(isset($_FILES['image']) && $_FILES['image']['error']===UPLOAD_ERR_OK && $_FILES['image']['size']<=5*1024*1024 && is_uploaded_file($_FILES['image']['tmp_name']) && isset($allowedImages[(string)mime_content_type($_FILES['image']['tmp_name'])])) {
            $ext=$allowedImages[(string)mime_content_type($_FILES['image']['tmp_name'])]; $image=bin2hex(random_bytes(16)).'.'.$ext;
            move_uploaded_file($_FILES['image']['tmp_name'],dirname(__DIR__,2).'/public/assets/uploads/products-3d/'.$image);
        }
        $db->beginTransaction();
        try {
            $statement=$db->prepare('insert into products_3d(sku,name,description,technical_notes,category_id,license_type,license_source,license_notes,print_time_minutes,energy_cost,labor_cost,packaging_cost,other_cost,waste_percent,sale_price,stock_quantity,minimum_stock,image_path) values(:sku,:name,:description,:technical_notes,:category_id,:license_type,:license_source,:license_notes,:print_time,:energy,:labor,:packaging,:other,:waste,:sale_price,:stock,:minimum,:image)');
            $statement->execute(['sku'=>$sku,'name'=>$name,'description'=>$this->input('description')?:null,'technical_notes'=>$this->input('technical_notes')?:null,'category_id'=>(int)$this->input('category_id')?:null,'license_type'=>$this->input('license_type')?:null,'license_source'=>$this->input('license_source')?:null,'license_notes'=>$this->input('license_notes')?:null,'print_time'=>max(0,(int)$this->input('print_time_minutes')),'energy'=>max(0,(float)$this->input('energy_cost')),'labor'=>max(0,(float)$this->input('labor_cost')),'packaging'=>max(0,(float)$this->input('packaging_cost')),'other'=>max(0,(float)$this->input('other_cost')),'waste'=>max(0,(float)$this->input('waste_percent')),'sale_price'=>max(0,(float)$this->input('sale_price')),'stock'=>max(0,(float)$this->input('stock_quantity')),'minimum'=>max(0,(float)$this->input('minimum_stock')),'image'=>$image]);
            $productId=(int)$db->lastInsertId();
            foreach((array)($_POST['filament_id']??[]) as $index=>$filamentId) { $grams=(float)($_POST['filament_g'][$index]??0); if((int)$filamentId>0&&$grams>0){$m=$db->prepare('insert into product_filaments_3d(product_id,filament_id,quantity_g) values(:product,:filament,:grams)');$m->execute(['product'=>$productId,'filament'=>(int)$filamentId,'grams'=>$grams]);}}
            $db->commit(); $_SESSION['flash_success']='Peca e ficha de custo cadastradas.';
        } catch(Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Nao foi possivel cadastrar a peca.';}
        redirect('/impressao-3d?view=pecas');
    }

    public function category(): void
    {
        $this->allowAdmin();
        $name=trim((string)$this->input('name'));
        if($name===''){$_SESSION['flash']='Informe o nome da categoria.';redirect('/impressao-3d?view=categorias');}
        try{$statement=Database::connection()->prepare('insert into product_categories_3d(name,description) values(:name,:description)');$statement->execute(['name'=>$name,'description'=>trim((string)$this->input('description'))?:null]);$_SESSION['flash_success']='Categoria cadastrada.';}catch(Throwable){$_SESSION['flash']='Essa categoria ja existe.';}
        redirect('/impressao-3d?view=categorias');
    }

    public function channel(): void
    {
        $this->allowAdmin();
        $statement=Database::connection()->prepare('insert into sales_channels_3d(name,channel_type,contact_name,fee_percent,fixed_fee,commission_percent) values(:name,:type,:contact,:fee,:fixed,:commission)');
        $statement->execute(['name'=>trim((string)$this->input('name')),'type'=>$this->input('channel_type'),'contact'=>$this->input('contact_name')?:null,'fee'=>(float)$this->input('fee_percent'),'fixed'=>(float)$this->input('fixed_fee'),'commission'=>(float)$this->input('commission_percent')]);
        $_SESSION['flash_success']='Canal de venda cadastrado.'; redirect('/impressao-3d?view=canais');
    }

    public function production(): void
    {
        $this->allowAdmin();
        $db=Database::connection();$productId=(int)$this->input('product_id');$quantity=max(1,(float)$this->input('quantity'));
        $product=$this->loadProduct($productId); if(!$product){redirect('/impressao-3d?view=producao');}
        $cost=$this->productCost($product,(float)$product['filament_cost']);
        $db->beginTransaction();
        try{
            $order=$db->prepare("insert into production_orders_3d(product_id,quantity,status,started_at,completed_at,unit_cost_snapshot,total_cost,notes) values(:product,:quantity,'completed',current_timestamp,current_timestamp,:unit_cost,:total,:notes)");
            $order->execute(['product'=>$productId,'quantity'=>$quantity,'unit_cost'=>$cost,'total'=>$cost*$quantity,'notes'=>$this->input('notes')?:null]);
            $materials=$db->prepare('select pf.filament_id,pf.quantity_g,f.name,f.current_weight_g from product_filaments_3d pf join filaments_3d f on f.id=pf.filament_id where pf.product_id=:id');$materials->execute(['id'=>$productId]);
            foreach($materials->fetchAll() as $material){
                $required=(float)$material['quantity_g']*$quantity;
                if ($required>(float)$material['current_weight_g']) throw new \RuntimeException('Filamento insuficiente: '.$material['name']);
                $deduct=$db->prepare('update filaments_3d set current_weight_g=current_weight_g-:grams where id=:id');$deduct->execute(['grams'=>$required,'id'=>$material['filament_id']]);
            }
            $stock=$db->prepare('update products_3d set stock_quantity=stock_quantity+:quantity where id=:id');$stock->execute(['quantity'=>$quantity,'id'=>$productId]);
            $db->commit();$_SESSION['flash_success']='Producao concluida, materiais baixados e estoque atualizado.';
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();$_SESSION['flash']=$e instanceof \RuntimeException?$e->getMessage():'Falha ao concluir producao.';}
        redirect('/impressao-3d?view=producao');
    }

    public function sale(): void
    {
        $this->allowAdmin();
        $db=Database::connection();$product=$this->loadProduct((int)$this->input('product_id'));$quantity=max(1,(float)$this->input('quantity'));$channelId=(int)$this->input('channel_id')?:null;$unit=(float)$this->input('unit_price');
        if(!$product||$unit<=0||$quantity>(float)$product['stock_quantity']){$_SESSION['flash']='Revise produto, preco e estoque.';redirect('/impressao-3d?view=vendas');}
        $channel=['fee_percent'=>0,'fixed_fee'=>0,'commission_percent'=>0];if($channelId){$s=$db->prepare('select * from sales_channels_3d where id=:id');$s->execute(['id'=>$channelId]);$channel=$s->fetch()?:$channel;}
        $gross=$unit*$quantity;$fees=$gross*((float)$channel['fee_percent']+(float)$channel['commission_percent'])/100+(float)$channel['fixed_fee'];$cost=$this->productCost($product,(float)$product['filament_cost'])*$quantity;$profit=$gross-$fees-$cost;
        $db->beginTransaction();try{$sale=$db->prepare('insert into sales_3d(product_id,channel_id,quantity,unit_price,gross_amount,fees_amount,cost_amount,net_profit,sold_at,external_order_id) values(:product,:channel,:quantity,:unit,:gross,:fees,:cost,:profit,:sold_at,:external)');$sale->execute(['product'=>$product['id'],'channel'=>$channelId,'quantity'=>$quantity,'unit'=>$unit,'gross'=>$gross,'fees'=>$fees,'cost'=>$cost,'profit'=>$profit,'sold_at'=>$this->input('sold_at')?:date('Y-m-d'),'external'=>$this->input('external_order_id')?:null]);$stock=$db->prepare('update products_3d set stock_quantity=stock_quantity-:quantity where id=:id');$stock->execute(['quantity'=>$quantity,'id'=>$product['id']]);$db->commit();$_SESSION['flash_success']='Venda registrada com lucro liquido calculado.';}catch(Throwable){if($db->inTransaction())$db->rollBack();$_SESSION['flash']='Falha ao registrar venda.';}
        redirect('/impressao-3d?view=vendas');
    }

    private function loadProduct(int $id): ?array{$s=Database::connection()->prepare('select p.*,coalesce(sum(pf.quantity_g*(f.purchase_price/nullif(f.spool_net_weight_g,0))),0) filament_cost from products_3d p left join product_filaments_3d pf on pf.product_id=p.id left join filaments_3d f on f.id=pf.filament_id where p.id=:id group by p.id');$s->execute(['id'=>$id]);return $s->fetch()?:null;}
    private function productCost(array $p,float $filament): float{$subtotal=$filament+(float)$p['energy_cost']+(float)$p['labor_cost']+(float)$p['packaging_cost']+(float)$p['other_cost'];return round($subtotal*(1+(float)$p['waste_percent']/100),2);}
    private function allowAdmin(): void { if (!in_array(Auth::user()['role'] ?? '', ['owner','admin'], true)) { http_response_code(403); exit('Acesso restrito.'); } }
}
