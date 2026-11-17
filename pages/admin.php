<?php
declare(strict_types=1);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax']);
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../lib/orders.php';
require_once __DIR__ . '/../lib/review-permissions.php';
require_once __DIR__ . '/../lib/product-uploads.php';
header('Cache-Control: no-store');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' https: data: blob:; frame-ancestors 'none'; form-action 'self'");
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
$db = database();
$query = $db->prepare('SELECT * FROM users WHERE id = ? AND is_admin = 1');
$query->execute([(int)($_SESSION['user_id'] ?? 0)]);
$admin = $query->fetch();
if (!$admin) { header('Location: login.php?next=admin'); exit; }
$_SESSION['admin_csrf'] ??= bin2hex(random_bytes(32));
$tab = (string)($_GET['tab'] ?? 'overview');
if (!in_array($tab, ['overview','orders','products','content','notifications','customers','reviews','security'], true)) $tab = 'overview';
if ($admin['must_change_password']) $tab = 'security';
if (!$admin['must_change_password'] && in_array($tab,['overview','orders'],true) && !isset($_GET['export'])) { header('Location: ../admin/'); exit; }
if (isset($_GET['export']) && !$admin['must_change_password']) {
    $kind = $_GET['export'] === 'customers' ? 'customers' : 'orders';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="solid-'.$kind.'.csv"');
    $output=fopen('php://output','w'); fwrite($output,"\xEF\xBB\xBF");
    $safe=fn($v)=>preg_match('/^[=+@\-\t\r]/',(string)$v)?"'".$v:(string)$v;
    if($kind==='customers') {
        fputcsv($output,['ID','Nome','Email','WhatsApp','Novidades','Cadastro'],';');
        foreach($db->query('SELECT id,name,email,phone,newsletter,created_at FROM users ORDER BY id') as $row) fputcsv($output,array_map($safe,array_values($row)),';');
    } else {
        fputcsv($output,['Pedido','Cliente','Email','WhatsApp','Status','Subtotal','Desconto','Total produtos','Data','Itens'],';');
        foreach($db->query('SELECT * FROM orders ORDER BY id DESC') as $row) {
            $c=json_decode($row['customer'],true);$items=json_decode($row['items'],true);
            $values=[$row['code'],$c['name'],$c['email'],$c['phone'],$row['status'],number_format($row['subtotal_cents']/100,2,',',''),number_format($row['discount_cents']/100,2,',',''),number_format($row['total_cents']/100,2,',',''),$row['created_at'],implode(' | ',array_map(fn($i)=>$i['quantity'].'x '.$i['name'].' / '.$i['size'].' / '.$i['color'],$items))];
            fputcsv($output,array_map($safe,$values),';');
        }
    }
    fclose($output); exit;
}
$error = '';
$uploadedFiles = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) $error = 'O envio excedeu o limite do servidor. Envie menos imagens por vez.';
$notice = $_SESSION['admin_notice'] ?? '';
unset($_SESSION['admin_notice']);
function setting(PDO $db, string $key, $value): void {
    $query = $db->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
    $query->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE)]);
}
function safeUrl(string $value): bool {
    return !preg_match('/[\x00-\x20<>"\'`\\\\]/', $value) && (preg_match('~^https://[^/]+(?:/|$)~i', $value) || preg_match('~^(?:\.\./)?(?:assets/|pages/)[a-zA-Z0-9_./?=&%#-]+$~', $value));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    try {
        if (!hash_equals($_SESSION['admin_csrf'], (string)($_POST['csrf'] ?? ''))) throw new RuntimeException('Sua sessão expirou. Atualize a página.');
        $action = (string)($_POST['action'] ?? '');
        if ($admin['must_change_password'] && $action !== 'password') throw new RuntimeException('Defina sua senha antes de alterar a loja.');
        $event = '';
        if ($action === 'password') {
            if (!password_verify((string)($_POST['current_password'] ?? ''), $admin['password_hash'])) throw new RuntimeException('Senha atual incorreta.');
            $password = (string)($_POST['password'] ?? '');
            if (strlen($password) < 12 || strlen($password) > 72 || $password !== ($_POST['password_confirm'] ?? '') || password_verify($password, $admin['password_hash'])) throw new RuntimeException('Use uma nova senha de 12 a 72 caracteres e confirme corretamente.');
            $db->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $admin['id']]);
            session_regenerate_id(true);
            $event = 'Senha do administrador atualizada';
        } elseif ($action === 'review_permission') {
            authorizeProductReview($db, (string)($_POST['email'] ?? ''), (int)($_POST['product_id'] ?? 0), ($_POST['allowed'] ?? '') === '1', (int)$admin['id']);
            $event = '';
        } elseif ($action === 'order_status') {
            updateOrderStatus($db, (int)($_POST['order_id'] ?? 0), (string)($_POST['status'] ?? ''), (int)$admin['id']);
            $event = '';
        } elseif ($action === 'delete_product') {
            if (($_POST['confirm_delete'] ?? '') !== 'yes') throw new RuntimeException('Confirme que deseja apagar o produto.');
            $deleteId = (int)($_POST['id'] ?? 0);
            $db->beginTransaction();
            try {
                $query = $db->prepare('SELECT data FROM products WHERE id=? FOR UPDATE');
                $query->execute([$deleteId]);
                $productJson = $query->fetchColumn();
                if (!$productJson) throw new RuntimeException('Produto não encontrado.');
                $deletedProduct = json_decode($productJson, true);
                $db->prepare('DELETE FROM products WHERE id=?')->execute([$deleteId]);
                $db->prepare('INSERT INTO admin_events (user_id,message) VALUES (?,?)')->execute([$admin['id'], 'Produto apagado: '.mb_substr($deletedProduct['name'],0,100)]);
                $db->commit();
            } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
            $event = '';
        } elseif ($action === 'product') {
            $product = [];
            foreach (['sku','name','category','badge','description'] as $key) {
                $value = trim((string)($_POST[$key] ?? ''));
                if (mb_strlen($value) > ($key === 'description' ? 4000 : 150) || preg_match('/[<>"\'`]/', $value)) throw new RuntimeException('Use textos simples nos campos do produto, sem código HTML ou aspas.');
                $product[$key] = $value;
            }
            if ($product['name'] === '' || $product['sku'] === '' || $product['category'] === '') throw new RuntimeException('Informe nome, SKU e categoria.');
            foreach (['price','stock','rating','reviews'] as $key) {
                $value = filter_var($_POST[$key] ?? '', FILTER_VALIDATE_FLOAT);
                if ($value === false || !is_finite($value) || $value < 0 || $value > 10000000 || ($key === 'rating' && $value > 5) || (in_array($key, ['stock','reviews'], true) && floor($value) !== $value)) throw new RuntimeException('Confira preço, estoque, avaliação e número de avaliações.');
                $product[$key] = in_array($key, ['stock','reviews'], true) ? (int)$value : $value;
            }
            $product['images'] = [];
            $imageReferences = [];
            $existingId = (int)($_POST['id'] ?? 0);
            if ($existingId) {
                $existingQuery = $db->prepare('SELECT data FROM products WHERE id=?');
                $existingQuery->execute([$existingId]);
                $existingJson = $existingQuery->fetchColumn();
                if (!$existingJson) throw new RuntimeException('Produto não encontrado.');
                $existingProduct = json_decode($existingJson, true);
                $keep = $_POST['keep_images'] ?? [];
                if (!is_array($keep)) throw new RuntimeException('Seleção de imagens inválida.');
                foreach ($existingProduct['images'] as $index => $url) if (in_array((string)$index, $keep, true)) { $product['images'][] = $url; $imageReferences['existing:'.$index] = $url; }
            }
            foreach (['sizes','colors'] as $key) {
                $value = (string)($_POST[$key] ?? '');
                if (strlen($value) > 500 || preg_match('/[<>"\'`]/', $value)) throw new RuntimeException('Confira tamanhos e cores.');
                $product[$key] = array_values(array_filter(array_map('trim', explode(',', $value))));
            }
            $product['featured'] = isset($_POST['featured']);
            $id = (int)($_POST['id'] ?? 0);
            $active = isset($_POST['active']) ? 1 : 0;
            $upload = $_FILES['product_images'] ?? [];
            $incoming = count(array_filter($upload['error'] ?? [], fn($error) => $error !== UPLOAD_ERR_NO_FILE));
            if (count($product['images']) + $incoming > 20) throw new RuntimeException('Cada produto pode ter até 20 imagens.');
            if (!$product['images'] && !$incoming) throw new RuntimeException('Selecione pelo menos uma imagem do produto.');
            $uploadedFiles = productImageUploads($upload, __DIR__.'/../assets/images/uploads');
            foreach ($uploadedFiles as $index => $file) {
                $url = '../assets/images/uploads/'.basename($file);
                $product['images'][] = $url;
                $imageReferences['new:'.$index] = $url;
            }
            if (isset($_POST['image_order']) && $_POST['image_order'] !== '') $product['images'] = orderedProductImages($imageReferences, (string)$_POST['image_order']);
            $json = json_encode($product, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if ($id) $db->prepare('UPDATE products SET data = ?, active = ? WHERE id = ?')->execute([$json, $active, $id]);
            else $db->prepare('INSERT INTO products (data, active) VALUES (?, ?)')->execute([$json, $active]);
            $uploadedFiles = []; // As imagens agora pertencem ao produto salvo.
            $event = 'Produto salvo: ' . mb_substr($product['name'], 0, 100);
        } elseif ($action === 'announcement') {
            $value = trim((string)($_POST['announcement'] ?? ''));
            if (mb_strlen($value) > 500) throw new RuntimeException('O aviso pode ter até 500 caracteres.');
            setting($db, 'announcement', $value);
            $event = 'Aviso da loja atualizado';
        } elseif ($action === 'content') {
            $content = array_values(array_filter($_POST['content'] ?? [], fn($item) => is_array($item) && empty($item['remove']) && trim((string)($item['value'] ?? '')) !== ''));
            foreach ($content as &$item) unset($item['remove']);
            unset($item);
            if (!is_array($content) || !array_is_list($content) || count($content) > 200) throw new RuntimeException('Informe uma lista com até 200 alterações.');
            foreach ($content as $item) {
                if (!is_array($item) || !isset($item['page'],$item['selector'],$item['type'],$item['value']) || !is_string($item['page']) || !is_string($item['selector']) || !is_string($item['value']) || !in_array($item['type'], ['text','image','link'], true) || !preg_match('/^(?:\*|[a-z-]+\.php)$/', $item['page']) || strlen($item['selector']) > 200 || strlen($item['value']) > 10000) throw new RuntimeException('Confira página, seletor, tipo e valor de cada alteração.');
                if ($item['type'] !== 'text' && !safeUrl($item['value'])) throw new RuntimeException('Use links HTTPS ou caminhos em assets/ e pages/.');
            }
            setting($db, 'content', $content);
            $event = 'Conteúdo do site atualizado';
        } else throw new RuntimeException('Ação inválida.');
        if ($event !== '') $db->prepare('INSERT INTO admin_events (user_id, message) VALUES (?, ?)')->execute([$admin['id'], $event]);
        $_SESSION['admin_notice'] = 'Alterações salvas.';
        $nextTab = ['review_permission'=>'reviews','product'=>'products','delete_product'=>'products','order_status'=>'orders','content'=>'content','announcement'=>'notifications','password'=>'security'][$action] ?? 'overview';
        header('Location: admin.php?tab='.$nextTab); exit;
    } catch (JsonException $e) { $error = 'O JSON do conteúdo é inválido.'; }
    catch (RuntimeException $e) { $error = $e instanceof PDOException ? 'Não foi possível salvar. Confira o banco de dados.' : $e->getMessage(); }
}
if ($error !== '') foreach ($uploadedFiles as $file) if (is_file($file)) unlink($file);
$products = $db->query('SELECT * FROM products ORDER BY id')->fetchAll();
$settings = [];
foreach ($db->query('SELECT * FROM site_settings') as $row) $settings[$row['setting_key']] = json_decode($row['setting_value'], true);
$edit = ['id'=>0,'active'=>1,'sku'=>'','name'=>'','category'=>'','badge'=>'NOVO','description'=>'','price'=>0,'stock'=>0,'rating'=>5,'reviews'=>0,'images'=>[],'sizes'=>['P','M','G','GG'],'colors'=>[],'featured'=>false];
foreach ($products as $row) if ((int)$row['id'] === (int)($_GET['edit'] ?? 0)) $edit = array_merge($edit, json_decode($row['data'], true), ['id'=>(int)$row['id'],'active'=>(bool)$row['active']]);
$customers = $db->query('SELECT id, name, email, phone, newsletter, created_at FROM users ORDER BY id DESC LIMIT 100')->fetchAll();
$events = $db->query('SELECT message, created_at FROM admin_events ORDER BY id DESC LIMIT 30')->fetchAll();
function token(): void { echo '<input type="hidden" name="csrf" value="'.h($_SESSION['admin_csrf']).'">'; }
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Administração | SØLID</title><link rel="stylesheet" href="../assets/css/admin.css?v=reviews-33"></head><body data-tab="<?=h($tab)?>">
<header><a href="../index.php">SØLID <small>ADMINISTRAÇÃO</small></a><nav><?php foreach(['overview'=>'Visão geral','orders'=>'Vendas e pedidos','products'=>'Produtos','content'=>'Conteúdo','notifications'=>'Notificações','customers'=>'Clientes','reviews'=>'Avaliações','security'=>'Segurança'] as $value=>$label):?><a href="?tab=<?=$value?>" <?=$tab===$value?'aria-current="page"':''?>><?=$label?></a><?php endforeach?><a href="profile.php?view=personal">Minha conta</a></nav></header>
<main><div class="intro"><span>CONTROLE DA LOJA</span><h1>Administração da SØLID.</h1><p>Conectado como <?=h($admin['email'])?> · <a href="../index.php">Ver loja ↗</a></p></div>
<?php if ($notice): ?><p class="success" role="status"><?=h($notice)?></p><?php endif ?>
<?php if ($error): ?><p class="error" role="alert"><?=h($error)?></p><?php endif ?>
<?php if (!$admin['must_change_password']) require __DIR__.'/../lib/admin-sales.php'; ?>
<section id="security"><h2>Segurança da conta</h2><p><?=$admin['must_change_password'] ? 'Defina uma senha pessoal para liberar o gerenciamento da loja.' : 'Você pode trocar sua senha a qualquer momento.'?></p><form method="post"><?php token() ?><input type="hidden" name="action" value="password"><div class="grid"><label>Senha atual<input type="password" name="current_password" required autocomplete="current-password"></label><label>Nova senha<input type="password" name="password" required minlength="12" maxlength="72" autocomplete="new-password"></label><label>Confirmar nova senha<input type="password" name="password_confirm" required minlength="12" maxlength="72" autocomplete="new-password"></label></div><button>Atualizar senha</button></form></section>
<?php if (!$admin['must_change_password']): ?>
<?php require __DIR__.'/../lib/admin-reviews.php'; ?>
<section id="products"><div class="section-heading"><h2>Produtos</h2><a class="button" href="admin.php?tab=products#product-form">Novo produto</a></div><div class="table-wrap"><table><thead><tr><th>Produto</th><th>Preço</th><th>Estoque</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($products as $row): $p=json_decode($row['data'],true); ?><tr><td><?=h($p['name'])?><small><?=h($p['sku'])?></small></td><td>R$ <?=number_format($p['price'],2,',','.')?></td><td><?=(int)$p['stock']?></td><td><?=$row['active']?'Publicado':'Oculto'?></td><td><a href="?tab=products&amp;edit=<?=(int)$row['id']?>#product-form">Editar</a></td></tr><?php endforeach ?></tbody></table></div>
<form method="post" id="product-form" enctype="multipart/form-data"><?php token() ?><input type="hidden" name="action" value="product"><input type="hidden" name="id" value="<?=$edit['id']?>"><h3><?=$edit['id']?'Editar produto':'Adicionar produto'?></h3><div class="grid"><?php foreach(['name'=>'Nome','sku'=>'SKU','category'=>'Categoria','badge'=>'Etiqueta','price'=>'Preço (R$)','stock'=>'Estoque','rating'=>'Avaliação (0 a 5)','reviews'=>'Quantidade de avaliações'] as $key=>$label): ?><label><?=h($label)?><input name="<?=$key?>" value="<?=h($edit[$key])?>" <?=in_array($key,['price','stock','rating','reviews'])?'type="number" min="0" step="'.(in_array($key,['price','rating'])?'0.01':'1').'"':'maxlength="150"'?> <?=$key==='badge'?'':'required'?>></label><?php endforeach ?></div><label>Descrição<textarea name="description" rows="3" maxlength="4000"><?=h($edit['description'])?></textarea></label><div class="product-upload"><h3>Fotos do produto</h3><label class="upload-zone">Selecionar fotos do computador<input type="file" name="product_images[]" data-file-limit="<?=h(ini_get('upload_max_filesize'))?>" data-total-limit="<?=h(ini_get('post_max_size'))?>" accept="image/jpeg,image/png,image/webp,image/gif" multiple <?=$edit['images']?'':'required'?>><span>Você pode selecionar várias fotos. JPG, PNG, WebP ou GIF. Até 20 fotos por produto.</span></label><p id="selected-image-status" role="status" aria-live="polite"></p><input type="hidden" name="image_order" id="product-image-order" value=""><p>Limite por arquivo do servidor: <?=h(ini_get('upload_max_filesize'))?>. Limite total por envio: <?=h(ini_get('post_max_size'))?>. Use os botões para organizar as fotos. A primeira foto será a capa.</p><div id="selected-image-preview" class="product-image-list" aria-label="Ordem e prévia das fotos"><?php foreach($edit['images'] as $index=>$image):?><div class="product-image-option" data-existing-image="<?=$index?>"><img src="<?=h($image)?>" alt="Foto <?=($index+1)?> do produto"><label class="check"><input type="checkbox" name="keep_images[]" value="<?=$index?>" checked> Manter foto <?=($index+1)?></label></div><?php endforeach?></div><p>Desmarque uma foto para removê-la do produto ao salvar. A ordem só é alterada na loja depois de salvar o produto.</p></div><div class="grid"><label>Tamanhos, separados por vírgula<input name="sizes" value="<?=h(implode(', ',$edit['sizes']))?>"></label><label>Cores, separadas por vírgula<input name="colors" value="<?=h(implode(', ',$edit['colors']))?>"></label></div><label class="check"><input type="checkbox" name="featured" <?=$edit['featured']?'checked':''?>> Destacar na página inicial</label><label class="check"><input type="checkbox" name="active" <?=$edit['active']?'checked':''?>> Publicado na loja (desmarque para ocultar)</label><button>Salvar produto</button></form><?php if($edit['id']):?><details class="delete-product"><summary>Apagar produto</summary><p>O produto será removido da loja e da lista de produtos. O histórico dos pedidos já registrados será preservado. Esta ação não pode ser desfeita.</p><form method="post"><?php token()?><input type="hidden" name="action" value="delete_product"><input type="hidden" name="id" value="<?=$edit['id']?>"><label class="check"><input type="checkbox" name="confirm_delete" value="yes" required> Confirmo que quero apagar <?=h($edit['name'])?>.</label><button class="button-danger">Apagar produto definitivamente</button></form></details><?php endif?></section>
<section id="content"><h2>Conteúdo do site</h2><p>Escolha a página e a área que deseja atualizar. Preencha uma das linhas vazias para adicionar uma alteração. Marque “Restaurar original” para remover uma personalização.</p><form method="post"><?php token() ?><input type="hidden" name="action" value="content">
<?php
$areas = ['.hero-left .subtitle'=>'Início · subtítulo do banner','.hero-left h1'=>'Início · título do banner','.hero-left p'=>'Início · descrição do banner','.hero-right img'=>'Início · imagem do banner','.hero-buttons .btn-primary'=>'Início · botão de compra','.hero-buttons .btn-secondary'=>'Início · botão sobre a marca','.about-hero h1'=>'Sobre · título','.about-story h2'=>'Sobre · título da história','.about-story p'=>'Sobre · história','.about-mission h2'=>'Sobre · título da missão','.about-mission p'=>'Sobre · missão','.about-verse blockquote'=>'Sobre · inspiração','.about-cta h2'=>'Sobre · convite final','.section-title h1'=>'Título da página','.section-title p'=>'Descrição da página','.contact-intro h2'=>'Contato · título de atendimento','.contact-intro p'=>'Contato · descrição de atendimento','.contact-intro a'=>'Contato · WhatsApp','.footer-brand p'=>'Rodapé · descrição','.copyright'=>'Rodapé · direitos autorais'];
$areas = array_merge($areas, json_decode(file_get_contents(__DIR__.'/../database/data/content-areas.json'), true));
$changes = $settings['content'] ?? [];
$changes = array_merge($changes, array_fill(0,3,['page'=>'index.php','selector'=>'.hero-left .subtitle','type'=>'text','value'=>'']));
foreach($changes as $i=>$change): ?><fieldset><legend>Alteração <?=$i+1?></legend><div class="grid"><label>Página<select name="content[<?=$i?>][page]"><?php foreach(['index.php'=>'Início','about.php'=>'Sobre','contact.php'=>'Contato','shop.php'=>'Loja','collections.php'=>'Coleções','login.php'=>'Login','register.php'=>'Cadastro','profile.php'=>'Minha conta','cart.php'=>'Carrinho','checkout.php'=>'Finalização','wishlist.php'=>'Favoritos','product.php'=>'Produto','*'=>'Todas as páginas'] as $value=>$label): ?><option value="<?=h($value)?>" <?=$change['page']===$value?'selected':''?>><?=h($label)?></option><?php endforeach ?></select></label><label>Área<select name="content[<?=$i?>][selector]"><?php foreach($areas as $value=>$label): ?><option value="<?=h($value)?>" <?=$change['selector']===$value?'selected':''?>><?=h($label)?></option><?php endforeach ?></select></label><label>O que alterar<select name="content[<?=$i?>][type]"><?php foreach(['text'=>'Texto','image'=>'Imagem (URL HTTPS)','link'=>'Destino do botão ou link'] as $value=>$label): ?><option value="<?=$value?>" <?=$change['type']===$value?'selected':''?>><?=$label?></option><?php endforeach ?></select></label></div><label>Novo conteúdo<textarea rows="2" name="content[<?=$i?>][value]"><?=h($change['value'])?></textarea></label><label class="check"><input type="checkbox" name="content[<?=$i?>][remove]"> Restaurar original</label></fieldset><?php endforeach ?><button>Salvar conteúdo</button></form></section>
<section id="notifications"><h2>Avisos e notificações</h2><p>Publique um aviso em todas as páginas da loja. Deixe vazio para remover.</p><form method="post"><?php token() ?><input type="hidden" name="action" value="announcement"><label>Mensagem<textarea name="announcement" maxlength="500" rows="3"><?=h($settings['announcement']??'')?></textarea></label><button>Salvar aviso</button></form><h3>Atividade recente</h3><ul class="events"><?php foreach($events as $event): ?><li><time><?=h($event['created_at'])?></time> <?=h($event['message'])?></li><?php endforeach ?></ul></section>
<section id="customers"><div class="section-heading"><h2>Clientes recentes</h2><a class="button" href="?export=customers">Exportar todos (CSV)</a></div><p>Até 100 contas mais recentes. A permissão de administrador só pode ser concedida pelo servidor.</p><div class="table-wrap"><table><thead><tr><th>Nome</th><th>E-mail</th><th>WhatsApp</th><th>Novidades</th><th>Cadastro</th></tr></thead><tbody><?php foreach($customers as $customer): ?><tr><td><?=h($customer['name'])?></td><td><?=h($customer['email'])?></td><td><?=h($customer['phone'])?></td><td><?=$customer['newsletter']?'Sim':'Não'?></td><td><?=h($customer['created_at'])?></td></tr><?php endforeach ?></tbody></table></div></section>
<?php endif ?></main><script src="../assets/js/admin-products.js?v=preview-delete-31" defer></script></body></html>
