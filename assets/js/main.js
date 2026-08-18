document.addEventListener("DOMContentLoaded", () => {
    let header = document.querySelector(".header");

    if (!header) {
        const isPageInsidePagesFolder = window.location.pathname.includes("/pages/");
        const rootPath = isPageInsidePagesFolder ? "../" : "";
        const pagesPath = isPageInsidePagesFolder ? "" : "pages/";

        if (!document.querySelector('link[href*="font-awesome"]')) {
            const icons = document.createElement("link");
            icons.rel = "stylesheet";
            icons.href = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css";
            document.head.append(icons);
        }

        header = document.createElement("header");
        header.className = "header";
        header.innerHTML = `
            <div class="container">
                <a href="${rootPath}index.html" class="logo"><span>SØLID</span></a>
                <nav class="menu">
                    <a href="${rootPath}index.html">Home</a>
                    <a href="${pagesPath}shop.html">Shop</a>
                    <a href="${pagesPath}collections.html">Coleções</a>
                    <a href="${pagesPath}about.html">Sobre</a>
                    <a href="${pagesPath}contact.html">Contato</a>
                </nav>
                <div class="actions">
                    <a href="${pagesPath}wishlist.html" aria-label="Favoritos"><i class="fa-regular fa-heart"></i></a>
                    <a href="${pagesPath}cart.html" aria-label="Carrinho"><i class="fa-solid fa-bag-shopping"></i></a>
                    <a href="${pagesPath}login.html" aria-label="Entrar"><i class="fa-regular fa-user"></i></a>
                </div>
            </div>`;
        document.body.prepend(header);
    }

    const menu = header.querySelector(".menu");
    const actions = header.querySelector(".actions");

    try {
        const account = JSON.parse(localStorage.getItem("solid-account"));
        const isLoggedIn = localStorage.getItem("solid-session") === "active" && account;
        const accountLink = actions?.children[2];

        if (isLoggedIn && accountLink) {
            const isPageInsidePagesFolder = window.location.pathname.includes("/pages/");
            accountLink.href = isPageInsidePagesFolder ? "profile.html" : "pages/profile.html";
            accountLink.setAttribute("aria-label", "Minha conta");
        }
    } catch {
        // Mantém o link de entrada caso os dados locais estejam indisponíveis.
    }

    if (!menu || !actions || header.querySelector(".menu-toggle")) return;

    const toggle = document.createElement("button");
    toggle.className = "menu-toggle";
    toggle.type = "button";
    toggle.setAttribute("aria-label", "Abrir menu");
    toggle.setAttribute("aria-expanded", "false");
    toggle.innerHTML = "<span></span><span></span><span></span>";
    actions.before(toggle);

    const mobileActions = document.createElement("div");
    mobileActions.className = "mobile-menu-actions";
    mobileActions.innerHTML = `
        <a href="${actions.children[0]?.getAttribute("href") || "wishlist.html"}"><i class="fa-regular fa-heart"></i> Favoritos</a>
        <a href="${actions.children[1]?.getAttribute("href") || "cart.html"}"><i class="fa-solid fa-bag-shopping"></i> Carrinho</a>
        <a href="${actions.children[2]?.getAttribute("href") || "login.html"}"><i class="fa-regular fa-user"></i> Minha conta</a>`;
    menu.append(mobileActions);

    function closeMenu() {
        header.classList.remove("menu-open");
        toggle.setAttribute("aria-expanded", "false");
        toggle.setAttribute("aria-label", "Abrir menu");
    }

    toggle.addEventListener("click", () => {
        const isOpen = header.classList.toggle("menu-open");
        toggle.setAttribute("aria-expanded", String(isOpen));
        toggle.setAttribute("aria-label", isOpen ? "Fechar menu" : "Abrir menu");
    });

    menu.addEventListener("click", event => {
        if (event.target.closest("a")) closeMenu();
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape") closeMenu();
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 1100) closeMenu();
    });
});
