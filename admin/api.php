<?php
declare(strict_types=1);
session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax']);
header('Cache-Control: no-store, private');header('Content-Type: application/json; charset=utf-8');header('X-Content-Type-Options: nosniff');
require __DIR__.'/../api/config.php';require __DIR__.'/../lib/admin-orders.php';
function respond(array $v,int $status=200): never {http_response_code($status);echo json_encode($v,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);exit;}
try{
 $db=database();$actor=(int)($_SESSION['user_id']??0);
 try{adminActor($db,$actor);}catch(RuntimeException $e){respond(['ok'=>false,'message'=>'Entre com uma conta administrativa e conclua a troca de senha.'],403);}
 $_SESSION['admin_csrf']??=bin2hex(random_bytes(32));$token=$_SESSION['admin_csrf'];session_write_close();
 if($_SERVER['REQUEST_METHOD']==='POST'){
  $data=json_decode(file_get_contents('php://input'),true);if(!is_array($data)||!hash_equals($token,(string)($data['token']??'')))respond(['ok'=>false,'message'=>'Sessão expirada. Atualize a página.'],403);
  if(($data['action']??'')==='manual')$o=saveManualOrder($db,$actor,$data);
  elseif(($data['action']??'')==='status')adminUpdateOrder($db,(int)($data['id']??0),$actor,$data);
  else throw new RuntimeException('Ação inválida.');
  respond(['ok'=>true,'id'=>$o['id']??($data['id']??0)]);
 }
 if($_SERVER['REQUEST_METHOD']!=='GET')respond(['ok'=>false,'message'=>'Método inválido.'],405);
 $where=[];$params=[];
 foreach(['status'=>ADMIN_STATUSES,'origin'=>['site'=>'Site','whatsapp'=>'WhatsApp']] as $key=>$allowed){$v=(string)($_GET[$key]??'');if($v!==''){if(!isset($allowed[$v]))throw new RuntimeException('Filtro inválido.');$where[]="$key=?";$params[]=$v;}}
 foreach(['from','to'] as $key){$v=(string)($_GET[$key]??'');if($v!==''){if(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$v)||date('Y-m-d',strtotime($v))!==$v)throw new RuntimeException('Data inválida.');$where[]='created_at'.($key==='from'?'>=':'<=').'?';$params[]=$v.($key==='from'?' 00:00:00':' 23:59:59');}}
 $search=trim((string)($_GET['q']??''));if(strlen($search)>150)throw new RuntimeException('Busca muito longa.');if($search!==''){$where[]="(code LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(customer,'$.name')) LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(customer,'$.phone')) LIKE ?)";array_push($params,...array_fill(0,3,'%'.$search.'%'));}
 $sql=$where?' WHERE '.implode(' AND ',$where):'';
 $q=$db->prepare("SELECT * FROM orders$sql ORDER BY id DESC LIMIT 500");$q->execute($params);$orders=$q->fetchAll();
 foreach($orders as &$o){$o['customer']=json_decode($o['customer'],true);$o['items']=json_decode($o['items'],true);}unset($o);
 $q=$db->prepare("SELECT COUNT(*) total,COALESCE(SUM(status='pending'),0) pending,COALESCE(SUM(status='preparing'),0) preparing,COALESCE(SUM(status='delivered'),0) delivered,COALESCE(SUM(status='cancelled'),0) cancelled,COALESCE(SUM(origin='site'),0) site,COALESCE(SUM(origin='whatsapp'),0) whatsapp,COALESCE(SUM(CASE WHEN payment_status='paid' AND status<>'cancelled' THEN total_cents ELSE 0 END),0) revenue,COALESCE(SUM(CASE WHEN payment_status='paid' AND status<>'cancelled' AND DATE(created_at)=CURRENT_DATE THEN total_cents ELSE 0 END),0) today,COALESCE(SUM(CASE WHEN payment_status='paid' AND status<>'cancelled' AND DATE_FORMAT(created_at,'%Y-%m')=DATE_FORMAT(CURRENT_DATE,'%Y-%m') THEN total_cents ELSE 0 END),0) month FROM orders$sql");$q->execute($params);$metrics=$q->fetch();
 if(isset($_GET['id'])){$q=$db->prepare('SELECT h.*,u.name AS admin_name FROM order_history h LEFT JOIN users u ON u.id=h.admin_id WHERE order_id=? ORDER BY h.id');$q->execute([(int)$_GET['id']]);$history=$q->fetchAll();}else $history=[];
 $products=[];foreach($db->query('SELECT id,data FROM products WHERE active=1 ORDER BY id') as $row){$p=json_decode($row['data'],true);$p['id']=(int)$row['id'];$products[]=$p;}
 $latest=(int)$db->query('SELECT COALESCE(MAX(id),0) FROM orders')->fetchColumn();
 $since=max(0,(int)($_GET['since']??$latest));$q=$db->prepare('SELECT id,code,customer,total_cents,origin FROM orders WHERE id>? ORDER BY id DESC LIMIT 20');$q->execute([$since]);$new=[];foreach($q as $r){$new[]=['id'=>$r['id'],'code'=>$r['code'],'name'=>json_decode($r['customer'],true)['name'],'total'=>$r['total_cents'],'origin'=>$r['origin']];}
 respond(['ok'=>true,'token'=>$token,'orders'=>$orders,'metrics'=>$metrics,'products'=>$products,'statuses'=>ADMIN_STATUSES,'payments'=>PAYMENT_STATUSES,'history'=>$history,'latest'=>$latest,'new'=>$new]);
}catch(PDOException $e){error_log($e->getMessage());respond(['ok'=>false,'message'=>'Banco indisponível. Verifique a conexão e a migração admin-pwa.sql.'],503);}
catch(RuntimeException|JsonException $e){respond(['ok'=>false,'message'=>$e->getMessage()],422);}
