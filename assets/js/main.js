document.addEventListener("DOMContentLoaded", () => {
    // Em XAMPP o projeto abre em /SOLID/. No InfinityFree ele abre na raiz.
    // Nunca usamos C:\\xampp\\... como link: isso é um caminho do computador, não uma URL.
    const isLocalServer = ["localhost", "127.0.0.1"].includes(window.location.hostname);
    const projectRoot = isLocalServer ? "/SOLID/" : "/";
    const pageUrl = page => page === "index" ? projectRoot : `${projectRoot}pages/${page}.php`;
    let header = document.querySelector(".header");

    if (!header) {
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
                <a href="${pageUrl("index")}" class="logo"><span>SØLID</span></a>
                <nav class="menu">
                    <a href="${pageUrl("index")}">Home</a>
                    <a href="${pageUrl("shop")}">Shop</a>
                    <a href="${pageUrl("collections")}">Coleções</a>
                    <a href="${pageUrl("about")}">Sobre</a>
                    <a href="${pageUrl("contact")}">Contato</a>
                </nav>
                <div class="actions">
                    <a href="${pageUrl("wishlist")}" aria-label="Favoritos"><i class="fa-regular fa-heart"></i></a>
                    <a href="${pageUrl("cart")}" aria-label="Carrinho"><i class="fa-solid fa-bag-shopping"></i></a>
                    <a href="${pageUrl("login")}" aria-label="Entrar"><i class="fa-regular fa-user"></i></a>
                </div>
            </div>`;
        document.body.prepend(header);
    }

    const menu = header.querySelector(".menu");
    const actions = header.querySelector(".actions");

    const internalPages = new Set(["index", "shop", "collections", "about", "contact", "wishlist", "cart", "login", "register", "profile", "checkout"]);
    header.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute("href");
        if (!href || /^(https?:|mailto:|tel:|#)/i.test(href)) return;
        const filename = href.split("/").pop().split(/[?#]/)[0];
        const page = filename.replace(/\.(?:html|php)$/i, "");
        if (internalPages.has(page)) link.setAttribute("href", pageUrl(page));
    });

    // Protege também links criados dinamicamente por carrinho, favoritos e checkout.
    document.addEventListener("click", event => {
        const link = event.target.closest("a[href]");
        if (!link || link.target === "_blank") return;
        const href = link.getAttribute("href");
        if (!href || /^(https?:|mailto:|tel:|#)/i.test(href)) return;
        const [path, suffix = ""] = href.split(/(?=[?#])/);
        const filename = path.split("/").pop();
        const page = filename.replace(/\.(?:html|php)$/i, "");
        if (!internalPages.has(page)) return;
        const correctUrl = `${pageUrl(page)}${suffix}`;
        if (link.href !== new URL(correctUrl, window.location.origin).href) {
            event.preventDefault();
            window.location.assign(correctUrl);
        }
    });

    try {
        const account = JSON.parse(localStorage.getItem("solid-account"));
        const isLoggedIn = localStorage.getItem("solid-session") === "active" && account;
        const accountLink = actions?.children[2];

        if (isLoggedIn && accountLink) {
            accountLink.href = pageUrl("profile");
            accountLink.setAttribute("aria-label", "Minha conta");

            if (typeof account.profileImage === "string" && account.profileImage.startsWith("data:image/")) {
                accountLink.classList.add("account-avatar-link");
                accountLink.innerHTML = `<img src="${account.profileImage}" alt="Foto de perfil">`;
            }
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
    const avatarImage = actions.children[2]?.querySelector("img");
    const mobileAccount = avatarImage
        ? `<img class="mobile-account-avatar" src="${avatarImage.src}" alt=""> Minha conta`
        : '<i class="fa-regular fa-user"></i> Minha conta';
    mobileActions.innerHTML = `
        <a href="${actions.children[0]?.getAttribute("href") || "wishlist.php"}"><i class="fa-regular fa-heart"></i> Favoritos</a>
        <a href="${actions.children[1]?.getAttribute("href") || "cart.php"}"><i class="fa-solid fa-bag-shopping"></i> Carrinho</a>
        <a href="${actions.children[2]?.getAttribute("href") || "login.php"}">${mobileAccount}</a>`;
    menu.append(mobileActions);

    // A navegação móvel usa os mesmos destinos do menu principal.
    const mobileNav = document.createElement('nav');
    mobileNav.className = 'mobile-dock';
    mobileNav.id = 'mobile-navigation';
    mobileNav.setAttribute('aria-label', 'Navegação no celular');
    const dockItems = [
        { page: 'index', label: 'Início', icon: 'fa-house' },
        { page: 'shop', label: 'Loja', icon: 'fa-bag-shopping' },
        { page: 'login', label: 'Minha conta', icon: 'fa-user', account: true },
        { page: 'cart', label: 'Carrinho', icon: 'fa-cart-shopping' },
        { page: 'contact', label: 'Contato', icon: 'fa-envelope' }
    ];
    const currentPath = window.location.pathname.replace(/\/index\.php$/, '/');
    dockItems.forEach(item => {
        const link = document.createElement('a');
        link.href = item.account ? actions.children[2].href : pageUrl(item.page);
        if (item.account) link.classList.add('mobile-dock-account');
        const icon = document.createElement('span');
        icon.className = 'mobile-dock-icon';
        icon.innerHTML = `<i class="fa-solid ${item.icon}" aria-hidden="true"></i>`;
        const avatar = item.account ? actions.children[2].querySelector('img') : null;
        if (avatar) icon.replaceChildren(avatar.cloneNode(true));
        const text = document.createElement('span');
        text.className = 'mobile-dock-label';
        text.textContent = item.label;
        link.append(icon, text);
        if (new URL(link.href).pathname.replace(/\/index\.php$/, '/') === currentPath) {
            link.classList.add('is-current');
            link.setAttribute('aria-current', 'page');
        }
        mobileNav.append(link);
    });
    document.body.append(mobileNav);
    const dockLinks = [...mobileNav.querySelectorAll('a')];
    function positionDock(link) {
        const index = dockLinks.indexOf(link);
        if (index < 0) return;
        mobileNav.style.setProperty('--dock-x', `${(index + .5) * 100 / dockLinks.length}%`);
        mobileNav.classList.add('has-selection');
    }
    positionDock(mobileNav.querySelector('.is-current'));
    toggle.setAttribute('aria-controls', mobileNav.id);

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
    mobileNav.addEventListener('click', event => {
        const link = event.target.closest('a');
        if (!link) return;
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        mobileNav.querySelectorAll('a').forEach(item => item.classList.remove('is-current'));
        link.classList.add('is-current');
        positionDock(link);
        closeMenu();
        const delay = window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 300;
        window.setTimeout(() => window.location.assign(link.href), delay);
    });
    document.addEventListener('click', event => {
        if (!header.contains(event.target)) closeMenu();
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && header.classList.contains('menu-open')) {
            closeMenu();
            toggle.focus();
        }
    });

    window.addEventListener("resize", () => {
        if (window.innerWidth > 1100) closeMenu();
    });
});
