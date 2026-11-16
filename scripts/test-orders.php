<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../api/config.php';require __DIR__.'/../lib/orders.php';
function check(bool $value,string $message): void {if(!$value)throw new RuntimeException($message);}
$db=database();$userId=0;$productId=0;$orderIds=[];
try {
 $db->prepare("INSERT INTO users (name,email,password_hash,phone,zip,address,address_number,neighborhood,city) VALUES ('Teste pedidos',?,?,'','','','','','')")->execute(['order-test-'.bin2hex(random_bytes(6)).'@example.invalid',password_hash(bin2hex(random_bytes(20)),PASSWORD_DEFAULT)]);$userId=(int)$db->lastInsertId();
 $product=['sku'=>'TEST-ORDERS','name'=>'Produto de teste','price'=>100.01,'stock'=>5,'sizes'=>['M'],'colors'=>['Preto'],'images'=>['../assets/images/products/M1.jpeg'],'category'=>'Teste','badge'=>'','description'=>'Teste','featured'=>false,'rating'=>5,'reviews'=>0];
 $db->prepare('INSERT INTO products(data) VALUES(?)')->execute([json_encode($product)]);$productId=(int)$db->lastInsertId();
 $data=['requestKey'=>bin2hex(random_bytes(16)),'customer'=>['name'=>'Teste','phone'=>'11999999999','email'=>'test@example.invalid','address'=>'Rua teste','number'=>'1','neighborhood'=>'Centro','zip'=>'01001000','city'=>'São Paulo / SP','delivery'=>'Entrega','payment'=>'PIX'],'items'=>[['id'=>$productId,'quantity'=>2,'size'=>'M','color'=>'Preto','price'=>0.01]],'coupon'=>'SOLID10'];
 $order=createOrder($db,$userId,$data);$orderIds[]=(int)$order['id'];
 check((int)$order['total_cents']===18002,'Server price or coupon calculation failed');
 $repeat=createOrder($db,$userId,$data);check($repeat['id']===$order['id'],'Duplicate request created another order');
 $stock=function()use($db,$productId){$q=$db->prepare('SELECT data FROM products WHERE id=?');$q->execute([$productId]);return json_decode($q->fetchColumn(),true)['stock'];};
 check($stock()===3,'Stock reservation failed');
 $rejected=$data;$rejected['requestKey']=bin2hex(random_bytes(16));$rejected['coupon']='';$rejected['items'][0]['quantity']=4;
 try{createOrder($db,$userId,$rejected);throw new LogicException('Oversell accepted');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'Estoque insuficiente'),'Wrong stock error');}
 check($stock()===3,'Failed order changed stock');
 try{updateOrderStatus($db,(int)$order['id'],'completed',$userId);throw new LogicException('Invalid transition accepted');}catch(RuntimeException $e){check(str_contains($e->getMessage(),'não é permitida'),'Wrong transition error');}
 updateOrderStatus($db,(int)$order['id'],'paid',$userId);
 updateOrderStatus($db,(int)$order['id'],'cancelled',$userId);
 updateOrderStatus($db,(int)$order['id'],'cancelled',$userId);
 check($stock()===5,'Cancellation restored stock incorrectly');
 $second=$data;$second['requestKey']=bin2hex(random_bytes(16));$second['coupon']='';$second['items'][0]['quantity']=1;
 $order=createOrder($db,$userId,$second);$orderIds[]=(int)$order['id'];
 updateOrderStatus($db,(int)$order['id'],'paid',$userId);updateOrderStatus($db,(int)$order['id'],'shipped',$userId);updateOrderStatus($db,(int)$order['id'],'completed',$userId);
 echo "PASS: server prices, discount, idempotency, stock reservation, oversell rejection, status transitions and cancellation.\n";
}finally{
 if($userId){$db->prepare('DELETE FROM admin_events WHERE user_id=?')->execute([$userId]);$db->prepare('DELETE FROM orders WHERE user_id=?')->execute([$userId]);$db->prepare('DELETE FROM users WHERE id=?')->execute([$userId]);}
 if($productId)$db->prepare('DELETE FROM products WHERE id=?')->execute([$productId]);
}
