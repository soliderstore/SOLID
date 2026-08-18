document.addEventListener("DOMContentLoaded", () => {
    const isPageInsidePagesFolder = window.location.pathname.includes("/pages/");
    const basePath = isPageInsidePagesFolder ? "../" : "";
    let footer = document.querySelector("footer.footer");

    if (!footer) {
        footer = document.createElement("footer");
        footer.className = "footer";
        document.body.append(footer);
    }

    footer.innerHTML = `
        <div class="footer-content">
            <div class="footer-brand">
                <a href="${basePath}index.html" class="footer-logo">SØLID</a>
                <p>Streetwear premium para quem segue o próprio caminho.</p>
            </div>
            <div>
                <h4>Institucional</h4>
                <a href="${basePath}index.html">Início</a>
                <a href="${basePath}pages/about.html">Sobre nós</a>
                <a href="${basePath}pages/contact.html">Contato</a>
            </div>
            <div>
                <h4>Loja</h4>
                <a href="${basePath}pages/shop.html">Produtos</a>
                <a href="${basePath}pages/wishlist.html">Favoritos</a>
                <a href="${basePath}pages/cart.html">Carrinho</a>
            </div>
            <div>
                <h4>Atendimento</h4>
                <a href="https://wa.me/5531998244421" target="_blank" rel="noopener">WhatsApp</a>
                <a href="${basePath}pages/login.html">Minha conta</a>
                <a href="${basePath}pages/register.html">Criar conta</a>
            </div>
        </div>
        <div class="copyright">© ${new Date().getFullYear()} SØLID. Todos os direitos reservados.</div>`;
});
