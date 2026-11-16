<?php
declare(strict_types=1);
function completedProductPurchase(PDO $db,int $userId,int $productId): bool {
    $query=$db->prepare("SELECT items FROM orders WHERE user_id=? AND status='completed'");
    $query->execute([$userId]);
    while($row=$query->fetch())foreach(json_decode($row['items'],true)??[] as $item)if((int)$item['id']===$productId)return true;
    return false;
}
function canReviewProduct(PDO $db,int $userId,int $productId,bool $lock=false): bool {
    $query=$db->prepare('SELECT can_review FROM product_purchases WHERE user_id=? AND product_id=?'.($lock?' FOR UPDATE':''));
    $query->execute([$userId,$productId]);
    return (bool)$query->fetchColumn()&&completedProductPurchase($db,$userId,$productId);
}
function authorizeProductReview(PDO $db,string $email,int $productId,bool $allowed,int $adminId): void {
    $db->beginTransaction();
    try {
        $query=$db->prepare('SELECT id FROM users WHERE email=?');$query->execute([strtolower(trim($email))]);$userId=(int)$query->fetchColumn();
        if(!$userId)throw new RuntimeException('Não existe uma conta com esse e-mail.');
        $query=$db->prepare('SELECT id FROM products WHERE id=?');$query->execute([$productId]);
        if(!$query->fetchColumn())throw new RuntimeException('Produto não encontrado.');
        if($allowed&&!completedProductPurchase($db,$userId,$productId))throw new RuntimeException('Esta conta ainda não possui uma compra concluída deste produto. Conclua o pedido na aba Vendas e pedidos antes de liberar o feedback.');
        $db->prepare('INSERT INTO product_purchases(user_id,product_id,can_review) VALUES(?,?,?) ON DUPLICATE KEY UPDATE can_review=VALUES(can_review)')->execute([$userId,$productId,$allowed?1:0]);
        $db->prepare('INSERT INTO admin_events(user_id,message) VALUES(?,?)')->execute([$adminId,($allowed?'Feedback liberado':'Feedback bloqueado').' para conta #'.$userId.' / produto #'.$productId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
