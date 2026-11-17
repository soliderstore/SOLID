<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Shop | SØLID</title>

    <link rel="preconnect"
          href="https://fonts.googleapis.com">

    <link rel="preconnect"
          href="https://fonts.gstatic.com"
          crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap"
          rel="stylesheet">

    <link rel="stylesheet"
          href="../assets/css/style.css?v=storefront-34">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

</head>

<body>

<header class="header">

    <div class="container">

        <a href="../index.php" class="logo" data-site-edit="shop-1">

            <span data-site-edit="shop-2">SØLID</span>

        </a>

        <nav class="menu">

            <a href="../index.php" data-site-edit="shop-3">Home</a>

            <a href="shop.php" data-site-edit="shop-4">Shop</a>

            <a href="collections.php" data-site-edit="shop-5">Coleções</a>

            <a href="about.php" data-site-edit="shop-6">Sobre</a>

            <a href="contact.php" data-site-edit="shop-7">Contato</a>

        </nav>

        <div class="actions">

            <a href="wishlist.php">
                <i class="fa-regular fa-heart"></i>
            </a>

            <a href="cart.php">
                <i class="fa-solid fa-bag-shopping"></i>
            </a>

            <a href="login.php">
                <i class="fa-regular fa-user"></i>
            </a>

        </div>

    </div>

</header>

<main>

<section class="shop-hero">

    <div class="container">

        <span data-site-edit="shop-11">SHOP</span>

        <h1 data-site-edit="shop-12">

            Nossa Coleção

        </h1>

        <p data-site-edit="shop-13">

            Descubra peças desenvolvidas para quem busca
            autenticidade e qualidade.

        </p>

    </div>

</section>

<section class="shop-toolbar">

    <div class="container">

        <input
        type="text"
        placeholder="Pesquisar produtos...">

        <select>

            <option>Todas Categorias</option>

            <option>Moletom</option>

            <option>Camiseta</option>

            <option>Boné</option>

        </select>

        <select>

            <option>Ordenar</option>

            <option>Menor preço</option>

            <option>Maior preço</option>

            <option>Mais recentes</option>

        </select>

    </div>

</section>

<section class="shop-products">

    <div class="container">

        <div id="products" class="products-grid">

        </div>

    </div>

</section>

<section class="newsletter">

    <div class="newsletter-content">

        <span data-site-edit="shop-14">NOVIDADES</span>

        <h2 data-site-edit="shop-15">

            Receba nossos lançamentos.

        </h2>

        <p data-site-edit="shop-16">

            Seja o primeiro a conhecer novas coleções.

        </p>

        <form>

            <input
            type="email"
            placeholder="Digite seu e-mail"
            aria-label="Seu e-mail"
            required>

            <button>

                Inscrever-se

            </button>

        </form>

    </div>

</section>

</main>

<footer class="footer">

    <div class="copyright">

        © 2026 SØLID.

    </div>

</footer>

<script src="../api/catalog.php"></script><script src="../assets/js/products.js?v=all-changes-27"></script>
<script src="../assets/js/shop.js?v=catalog-images-2"></script>
<script src="../assets/js/newsletter.js"></script>
<script src="../assets/js/main.js?v=storefront-34"></script>
<script src="../assets/js/footer.js"></script>

<script src="../api/site-content.php"></script></body>

</html>
