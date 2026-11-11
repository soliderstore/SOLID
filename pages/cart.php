<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carrinho | SØLID</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css?v=cart-footer-stop-27">
</head>
<body class="cart-screen">
<main><section class="cart-page"><div class="container">
<div class="cart-page-heading"><div><span>SEU CARRINHO</span><h1>Pronto para ser seu.</h1></div><a href="shop.php">Continuar comprando <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></div>
<a href="profile.php" class="cart-delivery"><i class="fa-solid fa-location-dot" aria-hidden="true"></i><span id="cart-delivery-label">Escolha o endereço de entrega ao continuar</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a>
<div class="cart-content">
<div class="cart-products" aria-label="Produtos do carrinho"></div>
<aside class="cart-summary"><div class="summary-card">
<h3>Resumo do pedido</h3>
<div class="summary-row"><span>Produtos selecionados</span><strong id="subtotal">R$ 0,00</strong></div>
<div class="coupon-box"><div class="coupon-heading"><i class="fa-solid fa-ticket" aria-hidden="true"></i><label for="coupon-code">Inserir código do cupom</label></div>
<div class="coupon-form"><input id="coupon-code" type="text" placeholder="Digite seu cupom" autocomplete="off"><button id="apply-coupon" type="button">Aplicar</button></div>
<small id="coupon-message" role="status">Use SOLID10 na primeira compra e ganhe 10% de desconto.</small></div>
<div class="summary-row"><span>Frete</span><strong>A combinar</strong></div>
<div class="summary-row"><span>Desconto</span><strong id="discount">R$ 0,00</strong></div>
<div class="summary-total"><span>Total dos produtos</span><strong id="total">R$ 0,00</strong></div>
<p class="cart-shipping-note">Entrega e pagamento são combinados com a SØLID pelo WhatsApp.</p>
</div></aside></div></div></section></main>
<div class="cart-checkout-bar" aria-label="Finalizar pedido"><div><small>Total dos produtos</small><strong id="cart-bar-total">R$ 0,00</strong><span id="cart-bar-savings"></span></div><a href="checkout.php" class="btn-primary" id="cart-continue">Continuar (0)</a></div>
<script src="../assets/js/products.js"></script><script src="../assets/js/purchase-auth.js"></script><script src="../assets/js/cart.js?v=cart-footer-stop-27"></script><script src="../assets/js/main.js?v=page-mist-20"></script><script src="../assets/js/footer.js"></script>
</body></html>