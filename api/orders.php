<?php
declare(strict_types=1);
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax']);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
require_once __DIR__.'/config.php';require_once __DIR__.'/../lib/orders.php';
function orderResponse(array $body,int $status=200): never {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE);exit;}
if(empty($_SESSION['user_id']))orderResponse(['ok'=>false,'message'=>'Faça login para finalizar o pedido.'],401);
$_SESSION['order_csrf']??=bin2hex(random_bytes(32));
if($_SERVER['REQUEST_METHOD']==='GET')orderResponse(['ok'=>true,'token'=>$_SESSION['order_csrf']]);
if($_SERVER['REQUEST_METHOD']!=='POST')orderResponse(['ok'=>false,'message'=>'Método inválido.'],405);
$data=json_decode(file_get_contents('php://input'),true);
if(!is_array($data)||!hash_equals($_SESSION['order_csrf'],(string)($data['token']??'')))orderResponse(['ok'=>false,'message'=>'Sua sessão expirou. Atualize a página.'],403);
try {
 $order=createOrder(database(),(int)$_SESSION['user_id'],$data);
 orderResponse(['ok'=>true,'order'=>['id'=>(int)$order['id'],'code'=>$order['code'],'items'=>json_decode($order['items'],true),'subtotal'=>(int)$order['subtotal_cents']/100,'discount'=>(int)$order['discount_cents']/100,'total'=>(int)$order['total_cents']/100]]);
}catch(PDOException $e){error_log($e->getMessage());orderResponse(['ok'=>false,'message'=>'Não foi possível registrar o pedido. Tente novamente.'],503);}
catch(RuntimeException $e){orderResponse(['ok'=>false,'message'=>$e->getMessage()],422);}
