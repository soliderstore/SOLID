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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <a href="../index.php" class="logo"><span>SØLID</span></a>
            <nav class="menu">
                <a href="../index.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="collections.php">Coleções</a>
                <a href="about.php">Sobre</a>
                <a href="contact.php">Contato</a>
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
                <span>FAVORITOS</span>
                <h1>Lista de Desejos</h1>
                <p>Os produtos que você salvou.</p>
            </div>
            <div id="wishlist-products" class="products-grid"></div>
        </section>
    </main>
    <footer class="footer"><div class="copyright">© 2026 SØLID. Todos os direitos reservados.</div></footer>
    <script src="../assets/js/wishlist.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/footer.js"></script>
</body>
</html>
