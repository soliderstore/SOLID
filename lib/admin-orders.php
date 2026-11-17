<?php
declare(strict_types=1);
const ADMIN_STATUSES=['pending'=>'Pendente','confirmed'=>'Confirmado','preparing'=>'Em preparação','ready'=>'Pronto para envio','shipped'=>'Enviado','delivered'=>'Entregue','cancelled'=>'Cancelado'];
const PAYMENT_STATUSES=['pending'=>'Pendente','paid'=>'Pago','failed'=>'Falhou','refunded'=>'Reembolsado'];
function adminActor(PDO $db,int $id): array {
 $q=$db->prepare('SELECT id,name,must_change_password FROM users WHERE id=? AND is_admin=1');$q->execute([$id]);$a=$q->fetch();
 if(!$a || $a['must_change_password'])throw new RuntimeException('Acesso administrativo não autorizado.');return $a;
}
function orderAudit(PDO $db,int $id,int $actor,string $message): void {
 $db->prepare('INSERT INTO order_history(order_id,admin_id,message) VALUES(?,?,?)')->execute([$id,$actor,$message]);
 $db->prepare('INSERT INTO admin_events(user_id,message) VALUES(?,?)')->execute([$actor,'Pedido #'.$id.': '.$message]);
}
function orderMoney($value): int {
 if(!is_scalar($value)||!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D',(string)$value))throw new RuntimeException('Valor monetário inválido.');
 return (int)round((float)$value*100);
}
function saveManualOrder(PDO $db,int $actor,array $data): array {
 adminActor($db,$actor);$key=(string)($data['requestKey']??'');
 if(!preg_match('/^[a-zA-Z0-9-]{16,64}$/D',$key))throw new RuntimeException('Identificador inválido.');
 $id=filter_var($data['id']??0,FILTER_VALIDATE_INT);$customer=[];
 if(isset($data['customer'])&&!is_array($data['customer']))throw new RuntimeException('Dados do cliente inválidos.');
 foreach(['name','phone','email','address','number','complement','neighborhood','zip','city','state','payment','notes'] as $field){$v=$data['customer'][$field]??'';if(!is_string($v)||mb_strlen($v)>($field==='notes'?2000:250))throw new RuntimeException('Confira os dados do cliente.');$customer[$field]=trim($v);}
 foreach(['name','phone','address','zip','city','state','payment'] as $field)if($customer[$field]==='')throw new RuntimeException('Preencha cliente, endereço e pagamento.');
 if(!preg_match('/^\d{10,13}$/D',preg_replace('/\D/','',$customer['phone']))||strlen(preg_replace('/\D/','',$customer['zip']))!==8||!preg_match('/^[A-Za-z]{2}$/D',$customer['state'])||($customer['email']!==''&&!filter_var($customer['email'],FILTER_VALIDATE_EMAIL)))throw new RuntimeException('Confira telefone, CEP, UF e e-mail.');
 $customer+=['cpf'=>'','delivery'=>'Entrega','paymentDetails'=>''];
 $requested=$data['items']??[];if(!is_array($requested)||!$requested||count($requested)>50)throw new RuntimeException('Adicione de 1 a 50 produtos.');
 $payment=(string)($data['payment_status']??'pending');$status=(string)($data['status']??'pending');
 if(!isset(PAYMENT_STATUSES[$payment])||!isset(ADMIN_STATUSES[$status])||$status==='cancelled')throw new RuntimeException('Status inválido.');
 $db->beginTransaction();try{
  // Serialize writes per administrator and make repeated submissions idempotent.
  $db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE')->execute([$actor]);
  $q=$db->prepare('SELECT * FROM orders WHERE user_id=? AND request_key=?');$q->execute([$actor,$key]);if(!$id&&($existing=$q->fetch())){$db->commit();return $existing;}
  $old=null;if($id){$q=$db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$q->execute([$id]);$old=$q->fetch();if(!$old||$old['origin']!=='whatsapp'||in_array($old['status'],['shipped','delivered','cancelled'],true))throw new RuntimeException('Este pedido não pode ser editado.');if((int)($data['revision']??0)!==(int)$old['revision'])throw new RuntimeException('Pedido alterado por outro administrador. Atualize antes de salvar.');}
  $previous=$old?json_decode($old['items'],true):[];$used=[];$stock=[];$items=[];$subtotal=0;
  foreach($previous as $item)$used[(int)$item['id']]=-(int)$item['quantity'];
  foreach($requested as $item){if(!is_array($item))throw new RuntimeException('Produto inválido.');$pid=filter_var($item['id']??0,FILTER_VALIDATE_INT);$qty=filter_var($item['quantity']??0,FILTER_VALIDATE_INT);if(!$pid||!$qty||$qty<1||$qty>1000)throw new RuntimeException('Produto ou quantidade inválida.');$used[$pid]=($used[$pid]??0)+$qty;}
  ksort($used);foreach($used as $pid=>$delta){$q=$db->prepare('SELECT data,active FROM products WHERE id=? FOR UPDATE');$q->execute([$pid]);$row=$q->fetch();if(!$row)throw new RuntimeException('Produto indisponível.');$p=json_decode($row['data'],true);if($delta>0&&(!$row['active']||$p['stock']<$delta))throw new RuntimeException('Estoque insuficiente: '.$p['name']);$stock[$pid]=$p;}
  foreach($requested as $item){$pid=(int)$item['id'];$p=$stock[$pid];if(!in_array($item['size']??'',$p['sizes'],true)||!in_array($item['color']??'',$p['colors'],true))throw new RuntimeException('Tamanho ou cor inválidos.');$price=orderMoney($item['price']??$p['price']);$qty=(int)$item['quantity'];$subtotal+=$price*$qty;$items[]=['id'=>$pid,'name'=>$p['name'],'sku'=>$p['sku'],'description'=>$p['description']??'','image'=>$p['images'][0]??'','size'=>$item['size'],'color'=>$item['color'],'quantity'=>$qty,'priceCents'=>$price];}
  $discount=orderMoney($data['discount']??0);$shipping=orderMoney($data['shipping']??0);if($discount>$subtotal)throw new RuntimeException('Desconto maior que o subtotal.');$total=$subtotal-$discount+$shipping;
  foreach($used as $pid=>$delta){$stock[$pid]['stock']-=$delta;$db->prepare('UPDATE products SET data=? WHERE id=?')->execute([json_encode($stock[$pid],JSON_THROW_ON_ERROR),$pid]);}
  $values=[json_encode($customer,JSON_THROW_ON_ERROR),json_encode($items,JSON_THROW_ON_ERROR),$subtotal,$discount,$shipping,$total,$status,$payment];
  if($old){$db->prepare('UPDATE orders SET customer=?,items=?,subtotal_cents=?,discount_cents=?,shipping_cents=?,total_cents=?,status=?,payment_status=?,revision=revision+1 WHERE id=?')->execute([...$values,$id]);orderAudit($db,$id,$actor,'Pedido manual editado; status '.$old['status'].' → '.$status.'; pagamento '.$old['payment_status'].' → '.$payment);}
  else{$code='SOLID-'.strtoupper(bin2hex(random_bytes(6)));$db->prepare("INSERT INTO orders(customer,items,subtotal_cents,discount_cents,shipping_cents,total_cents,status,payment_status,code,user_id,request_key,origin) VALUES(?,?,?,?,?,?,?,?,?,?,?,'whatsapp')")->execute([...$values,$code,$actor,$key]);$id=(int)$db->lastInsertId();orderAudit($db,$id,$actor,'Pedido WhatsApp criado; status '.$status.'; pagamento '.$payment);}
  $q=$db->prepare('SELECT * FROM orders WHERE id=?');$q->execute([$id]);$result=$q->fetch();$db->commit();return $result;
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function adminUpdateOrder(PDO $db,int $id,int $actor,array $data): void {
 adminActor($db,$actor);$db->beginTransaction();try{
 $q=$db->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE');$q->execute([$id]);$o=$q->fetch();if(!$o)throw new RuntimeException('Pedido não encontrado.');
 if(isset($data['revision'])&&(int)$data['revision']!==(int)$o['revision'])throw new RuntimeException('Pedido alterado. Atualize e tente novamente.');
 $status=(string)($data['status']??$o['status']);$payment=(string)($data['payment_status']??$o['payment_status']);
 if(!isset(ADMIN_STATUSES[$status])||!isset(PAYMENT_STATUSES[$payment]))throw new RuntimeException('Status inválido.');
 $next=['pending'=>['confirmed','cancelled'],'confirmed'=>['preparing','cancelled'],'preparing'=>['ready','cancelled'],'ready'=>['shipped','cancelled'],'shipped'=>['delivered'],'delivered'=>[],'cancelled'=>[]];
 if($status!==$o['status']&&!in_array($status,$next[$o['status']]??[],true))throw new RuntimeException('Essa mudança de status não é permitida.');
 if($status==='cancelled'&&$o['status']!=='cancelled'){
 if(empty($data['confirm_cancel']))throw new RuntimeException('Confirme o cancelamento.');
 $items=json_decode($o['items'],true);usort($items,fn($a,$b)=>$a['id']<=>$b['id']);foreach($items as $i){$q=$db->prepare('SELECT data FROM products WHERE id=? FOR UPDATE');$q->execute([$i['id']]);if($json=$q->fetchColumn()){$p=json_decode($json,true);$p['stock']+=$i['quantity'];$db->prepare('UPDATE products SET data=? WHERE id=?')->execute([json_encode($p),$i['id']]);}}
 }
 if($status!==$o['status']||$payment!==$o['payment_status']){$db->prepare('UPDATE orders SET status=?,payment_status=?,revision=revision+1 WHERE id=?')->execute([$status,$payment,$id]);orderAudit($db,$id,$actor,'Status '.$o['status'].' → '.$status.'; pagamento '.$o['payment_status'].' → '.$payment.' (conferência manual)');}
 $db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
