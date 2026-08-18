document.addEventListener("DOMContentLoaded", () => {
    const isLocalServer = ["localhost", "127.0.0.1"].includes(window.location.hostname);
    const projectRoot = isLocalServer ? "/SOLID/" : "/";
    const pageUrl = page => page === "index" ? projectRoot : `${projectRoot}pages/${page}.php`;
    let footer = document.querySelector("footer.footer");

    if (!footer) {
        footer = document.createElement("footer");
        footer.className = "footer";
        document.body.append(footer);
    }

    footer.innerHTML = `
        <div class="footer-content">
            <div class="footer-brand">
                <a href="${pageUrl("index")}" class="footer-logo">SØLID</a>
                <p>Streetwear premium para quem segue o próprio caminho.</p>
            </div>
            <div>
                <h4>Institucional</h4>
                <a href="${pageUrl("index")}">Início</a>
                <a href="${pageUrl("about")}">Sobre nós</a>
                <a href="${pageUrl("contact")}">Contato</a>
            </div>
            <div>
                <h4>Loja</h4>
                <a href="${pageUrl("shop")}">Produtos</a>
                <a href="${pageUrl("wishlist")}">Favoritos</a>
                <a href="${pageUrl("cart")}">Carrinho</a>
            </div>
            <div>
                <h4>Atendimento</h4>
                <a href="https://wa.me/5531998244421" target="_blank" rel="noopener">WhatsApp</a>
                <a href="${pageUrl("login")}">Minha conta</a>
                <a href="${pageUrl("register")}">Criar conta</a>
            </div>
        </div>
        <div class="copyright">© ${new Date().getFullYear()} SØLID. Todos os direitos reservados.</div>`;
});
