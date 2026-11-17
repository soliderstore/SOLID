<?php
if (!isset($admin) || !$admin['is_admin']) { http_response_code(403); exit; }
require_once __DIR__.'/admin-orders.php';
$statuses=ADMIN_STATUSES;
function money(int $cents): string {return 'R$ '.number_format($cents/100,2,',','.');}
$filter=(string)($_GET['status']??'');if(!isset($statuses[$filter]))$filter='';
$from=(string)($_GET['from']??'');$to=(string)($_GET['to']??'');
foreach(['from','to'] as $dateField){$date=$$dateField;if($date!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)||date('Y-m-d',strtotime($date))!==$date))$$dateField='';}
$conditions=[];$params=[];
if($filter!==''){$conditions[]='status=?';$params[]=$filter;}
if($from!==''){$conditions[]='created_at>=?';$params[]=$from.' 00:00:00';}
if($to!==''){$conditions[]='created_at<=?';$params[]=$to.' 23:59:59';}
$where=$conditions?' WHERE '.implode(' AND ',$conditions):'';
$count=$db->prepare('SELECT COUNT(*) FROM orders'.$where);$count->execute($params);$orderCount=(int)$count->fetchColumn();
$page=max(1,min((int)($_GET['page']??1),max(1,(int)ceil($orderCount/30))));
$ordersQuery=$db->prepare('SELECT * FROM orders'.$where.' ORDER BY id DESC LIMIT 30 OFFSET '.(($page-1)*30));$ordersQuery->execute($params);$orders=$ordersQuery->fetchAll();
$totals=$db->query("SELECT COUNT(*) AS orders_count, COALESCE(SUM(CASE WHEN payment_status='paid' AND status<>'cancelled' THEN total_cents ELSE 0 END),0) AS revenue, SUM(status='pending') AS pending, SUM(payment_status='paid' AND status<>'cancelled') AS paid_count FROM orders")->fetch();
$customersCount=(int)$db->query('SELECT COUNT(*) FROM users WHERE is_admin=0')->fetchColumn();
$stockCount=0;foreach($products as $row){$product=json_decode($row['data'],true);if($row['active']&&$product['stock']<=5)$stockCount++;}
?>
<section id="overview"><div class="section-heading"><h2>Visão geral</h2><a href="?tab=orders" class="button">Ver pedidos</a></div><div class="metrics">
<article><span>Vendas confirmadas</span><strong><?=money((int)$totals['revenue'])?></strong><small>Produtos de pedidos pagos, enviados e concluídos</small></article>
<article><span>Pedidos recebidos</span><strong><?=(int)$totals['orders_count']?></strong><small>Inclui pendentes e cancelados</small></article>
<article><span>Aguardando pagamento</span><strong><?=(int)$totals['pending']?></strong><small>Confirme o pagamento antes de marcar como pago</small></article>
<article><span>Ticket médio das vendas</span><strong><?=money($totals['paid_count']?(int)round($totals['revenue']/$totals['paid_count']):0)?></strong><small>Somente pedidos confirmados</small></article>
<article><span>Clientes cadastrados</span><strong><?=$customersCount?></strong><small>Contas de clientes da loja</small></article>
<article><span>Estoque baixo</span><strong><?=$stockCount?></strong><small>Produtos publicados com até 5 unidades</small></article></div>
<p>Os pedidos são registrados ao finalizar o checkout. O pagamento é confirmado manualmente pelo administrador. O total exibido corresponde aos produtos; o frete continua a combinar. Não há importação automática de conversas anteriores do WhatsApp.</p>
<h3>Últimas atividades</h3><ul class="events"><?php foreach(array_slice($events,0,8) as $event):?><li><time><?=h($event['created_at'])?></time><?=h($event['message'])?></li><?php endforeach?></ul></section>
<section id="orders"><div class="section-heading"><h2>Pedidos e vendas</h2><a class="button" href="?export=orders">Exportar pedidos (CSV)</a></div>
<form method="get" class="filters"><input type="hidden" name="tab" value="orders"><div class="grid"><label>Status<select name="status"><option value="">Todos</option><?php foreach($statuses as $value=>$label):?><option value="<?=$value?>" <?=$filter===$value?'selected':''?>><?=$label?></option><?php endforeach?></select></label><label>Desde<input type="date" name="from" value="<?=h($from)?>"></label><label>Até<input type="date" name="to" value="<?=h($to)?>"></label></div><button>Filtrar pedidos</button></form>
<p><?=$orderCount?> pedido(s) encontrado(s). Estoque reservado ao registrar o pedido e devolvido ao cancelar. Cancelar não realiza reembolso bancário.</p>
<?php if(!$orders):?><div class="empty"><h3>Nenhum pedido neste período</h3><p>Os novos pedidos finalizados no site aparecerão aqui automaticamente.</p></div><?php endif?>
<?php foreach($orders as $order):$customer=json_decode($order['customer'],true);$items=json_decode($order['items'],true);?>
<details class="order"><summary><strong><?=h($order['code'])?></strong><span><?=h($customer['name'])?></span><span class="status <?=h($order['status'])?>"><?=$statuses[$order['status']]??h($order['status'])?></span><strong><?=money((int)$order['total_cents'])?></strong><time><?=h($order['created_at'])?></time></summary>
<div class="order-body"><div class="grid"><div><h3>Cliente</h3><p><?=h($customer['name'])?><br><?=h($customer['email'])?><br>WhatsApp: <?=h($customer['phone'])?><br>CPF: <?=h($customer['cpf'])?></p></div><div><h3>Entrega</h3><p><?=h($customer['address'])?>, <?=h($customer['number'])?><br><?=h($customer['complement'])?><br><?=h($customer['neighborhood'])?> · <?=h($customer['city'])?><br>CEP <?=h($customer['zip'])?><br><?=h($customer['delivery'])?></p></div><div><h3>Pagamento</h3><p><?=h($customer['payment'])?><br><?=h($customer['paymentDetails'])?></p><p>Subtotal: <?=money((int)$order['subtotal_cents'])?><br>Desconto: <?=money((int)$order['discount_cents'])?><br>Total dos produtos: <?=money((int)$order['total_cents'])?></p></div></div>
<div class="table-wrap"><table><thead><tr><th>Produto</th><th>Tamanho / cor</th><th>Quantidade</th><th>Valor unitário</th><th>Total</th></tr></thead><tbody><?php foreach($items as $item):?><tr><td><?=h($item['name'])?><small><?=h($item['sku'])?></small></td><td><?=h($item['size'])?> / <?=h($item['color'])?></td><td><?=(int)$item['quantity']?></td><td><?=money((int)$item['priceCents'])?></td><td><?=money($item['priceCents']*$item['quantity'])?></td></tr><?php endforeach?></tbody></table></div>
<?php if($customer['notes']):?><p>Observações: <?=h($customer['notes'])?></p><?php endif?>
<?php $next=['pending'=>['confirmed','cancelled'],'confirmed'=>['preparing','cancelled'],'preparing'=>['ready','cancelled'],'ready'=>['shipped','cancelled'],'shipped'=>['delivered']][$order['status']]??[];if($next):?><form method="post"><?php token()?><input type="hidden" name="action" value="order_status"><input type="hidden" name="order_id" value="<?=(int)$order['id']?>"><label>Atualizar status<select name="status" required><option value="">Escolha uma ação</option><?php foreach($next as $value):?><option value="<?=$value?>"><?=$statuses[$value]?></option><?php endforeach?></select></label><button>Confirmar atualização</button></form><?php endif?></div></details>
<?php endforeach?><div class="pagination"><?php if($page>1):?><a href="?<?=h(http_build_query(['tab'=>'orders','page'=>$page-1,'status'=>$filter,'from'=>$from,'to'=>$to]))?>">← Anterior</a><?php endif?><span>Página <?=$page?> de <?=max(1,(int)ceil($orderCount/30))?></span><?php if($page*30<$orderCount):?><a href="?<?=h(http_build_query(['tab'=>'orders','page'=>$page+1,'status'=>$filter,'from'=>$from,'to'=>$to]))?>">Próxima →</a><?php endif?></div></section>
