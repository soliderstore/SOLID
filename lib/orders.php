<?php
declare(strict_types=1);
function createOrder(PDO $db, int $userId, array $data): array {
    $key = (string)($data['requestKey'] ?? '');
    if (!preg_match('/^[a-zA-Z0-9-]{16,64}$/D', $key)) throw new RuntimeException('Identificador de pedido inválido. Atualize a página.');
    $db->beginTransaction();
    try {
        $user = $db->prepare('SELECT id, coupon_used FROM users WHERE id=? FOR UPDATE');
        $user->execute([$userId]); $user = $user->fetch();
        if (!$user) throw new RuntimeException('Faça login para finalizar o pedido.');
        $existing = $db->prepare('SELECT * FROM orders WHERE user_id=? AND request_key=?');
        $existing->execute([$userId,$key]);
        if ($order=$existing->fetch()) { $db->commit(); return $order; }
        $customer = $data['customer'] ?? [];
        if (!is_array($customer)) throw new RuntimeException('Confira seus dados de entrega.');
        $snapshot = [];
        foreach (['name','phone','email','cpf','address','number','complement','neighborhood','zip','city','delivery','payment','paymentDetails','notes'] as $field) {
            $value = $customer[$field] ?? '';
            if (!is_string($value) || mb_strlen($value)>($field==='notes'?2000:250)) throw new RuntimeException('Confira os dados do cliente.');
            $snapshot[$field] = trim($value);
        }
        foreach (['name','phone','email','address','number','neighborhood','zip','city','delivery','payment'] as $field) if ($snapshot[$field]==='') throw new RuntimeException('Complete os dados de entrega e pagamento.');
        if (!filter_var($snapshot['email'],FILTER_VALIDATE_EMAIL) || strlen(preg_replace('/\D/','',$snapshot['zip']))!==8) throw new RuntimeException('Confira o e-mail e o CEP.');
        $requested = $data['items'] ?? [];
        if (!is_array($requested) || !$requested || count($requested)>100) throw new RuntimeException('O carrinho está vazio ou excede o limite de itens.');
        foreach ($requested as $item) if (!is_array($item)) throw new RuntimeException('Item inválido.');
        // Lock products in ID order so simultaneous checkouts do not oversell.
        usort($requested,fn($a,$b)=>(int)($a['id']??0)<=>(int)($b['id']??0));
        $items=[]; $subtotal=0; $stock=[]; $used=[];
        foreach ($requested as $item) {
            if (!is_array($item)) throw new RuntimeException('Item inválido.');
            $id=filter_var($item['id']??0,FILTER_VALIDATE_INT);
            $quantity=filter_var($item['quantity']??0,FILTER_VALIDATE_INT);
            if (!$id || !$quantity || $quantity<1 || $quantity>1000) throw new RuntimeException('Quantidade inválida.');
            if (!isset($stock[$id])) {
                $query=$db->prepare('SELECT data FROM products WHERE id=? AND active=1 FOR UPDATE');$query->execute([$id]);
                $json=$query->fetchColumn(); if (!$json) throw new RuntimeException('Um produto não está mais disponível. Atualize o carrinho.');
                $stock[$id]=json_decode($json,true,32,JSON_THROW_ON_ERROR);
            }
            if (!is_string($item['size'] ?? null) || !is_string($item['color'] ?? null)) throw new RuntimeException('Confira o tamanho e a cor dos produtos.');
            $product=$stock[$id];$size=$item['size'];$color=$item['color'];
            if (!in_array($size,$product['sizes'],true) || !in_array($color,$product['colors'],true)) throw new RuntimeException('Confira o tamanho e a cor dos produtos.');
            $used[$id]=($used[$id]??0)+$quantity;
            if ($used[$id]>(int)$product['stock']) throw new RuntimeException('Estoque insuficiente para '.$product['name'].'.');
            $price=(int)round($product['price']*100);
            $subtotal+=$price*$quantity;
            $items[]=['id'=>$id,'name'=>$product['name'],'sku'=>$product['sku'],'size'=>$size,'color'=>$color,'quantity'=>$quantity,'priceCents'=>$price,'description'=>$product['description']??'','image'=>$product['images'][0]??''];
        }
        $coupon=($data['coupon']??'')==='SOLID10';
        if (($data['coupon']??'')!=='' && !$coupon) throw new RuntimeException('Cupom inválido.');
        if ($coupon && $user['coupon_used']) throw new RuntimeException('Este cupom já foi utilizado. Remova-o para continuar.');
        $discount=$coupon?(int)round($subtotal*0.1):0;
        foreach ($used as $id=>$quantity) {
            $stock[$id]['stock']-=$quantity;
            $db->prepare('UPDATE products SET data=? WHERE id=?')->execute([json_encode($stock[$id],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$id]);
        }
        if ($coupon) $db->prepare('UPDATE users SET coupon_used=1 WHERE id=?')->execute([$userId]);
        $code='SOLID-'.strtoupper(bin2hex(random_bytes(6)));
        $db->prepare('INSERT INTO orders (code,user_id,request_key,customer,items,subtotal_cents,discount_cents,total_cents) VALUES (?,?,?,?,?,?,?,?)')->execute([$code,$userId,$key,json_encode($snapshot,JSON_THROW_ON_ERROR),json_encode($items,JSON_THROW_ON_ERROR),$subtotal,$discount,$subtotal-$discount]);
        $id=(int)$db->lastInsertId();
        $db->prepare("INSERT INTO order_history(order_id,message) VALUES(?,'Pedido recebido pelo site; pagamento pendente')")->execute([$id]);
        $db->prepare('INSERT INTO admin_events (user_id,message) VALUES (?,?)')->execute([$userId,'Novo pedido: '.$code]);
        $query=$db->prepare('SELECT * FROM orders WHERE id=?');$query->execute([$id]);$order=$query->fetch();
        $db->commit();return $order;
    } catch (Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
}
function updateOrderStatus(PDO $db,int $id,string $status,int $adminId): void {
 require_once __DIR__.'/admin-orders.php';
 adminUpdateOrder($db,$id,$adminId,['status'=>$status,'confirm_cancel'=>$status==='cancelled']);
}
