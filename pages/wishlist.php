<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Desejos | SØLID</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=storefront-34">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="../index.php" class="logo" data-site-edit="wishlist-1"><span data-site-edit="wishlist-2">SØLID</span></a>
            <nav class="menu">
                <a href="../index.php" data-site-edit="wishlist-3">Home</a>
                <a href="shop.php" data-site-edit="wishlist-4">Shop</a>
                <a href="collections.php" data-site-edit="wishlist-5">Coleções</a>
                <a href="about.php" data-site-edit="wishlist-6">Sobre</a>
                <a href="contact.php" data-site-edit="wishlist-7">Contato</a>
            </nav>
            <div class="actions">
                <a href="wishlist.php" aria-label="Lista de desejos"><i class="fa-solid fa-heart"></i></a>
                <a href="cart.php" aria-label="Carrinho"><i class="fa-solid fa-bag-shopping"></i></a>
                <a href="login.php" aria-label="Entrar"><i class="fa-regular fa-user"></i></a>
            </div>
        </div>
    </header>
    <main class="wishlist-page">
        <section class="container">
            <div class="section-title">
                <span data-site-edit="wishlist-11">FAVORITOS</span>
                <h1 data-site-edit="wishlist-12">Lista de Desejos</h1>
                <p data-site-edit="wishlist-13">Os produtos que você salvou.</p>
            </div>
            <div id="wishlist-products" class="products-grid"></div>
        </section>
    </main>
    <footer class="footer"><div class="copyright">© 2026 SØLID. Todos os direitos reservados.</div></footer>
    <script src="../api/catalog.php"></script><script src="../assets/js/products.js?v=all-changes-27"></script>
<script src="../assets/js/wishlist.js"></script>
    <script src="../assets/js/main.js?v=storefront-34"></script>
    <script src="../assets/js/footer.js"></script>
<script src="../api/site-content.php"></script></body>
</html>
