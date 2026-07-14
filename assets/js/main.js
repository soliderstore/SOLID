document.addEventListener("DOMContentLoaded", () => {
    const header = document.querySelector(".header");
    const menu = document.querySelector(".header .menu");
    const actions = document.querySelector(".header .actions");

    if (!header || !menu || !actions) return;

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
        if (window.innerWidth > 900) closeMenu();
    });
});
