(() => {
    let dialog;
    function showLogin() {
        if (!dialog) {
            dialog = document.createElement("dialog");
            dialog.className = "purchase-auth-modal";
            dialog.setAttribute("aria-labelledby", "purchase-auth-title");
            dialog.setAttribute("aria-describedby", "purchase-auth-description");
            dialog.innerHTML = `<button type="button" class="purchase-auth-close" aria-label="Fechar">×</button>
                <span class="purchase-auth-brand">SØLID</span>
                <h2 id="purchase-auth-title">Entre para finalizar sua compra</h2>
                <p id="purchase-auth-description">Faça login ou crie uma conta para continuar. Seus produtos continuam no carrinho.</p>
                <a href="login.php?next=checkout" class="btn-primary">Fazer login</a>
                <a href="register.php?next=checkout" class="btn-secondary">Criar conta</a>`;
            document.body.append(dialog);
            dialog.querySelector("button").addEventListener("click", () => dialog.close());
            dialog.addEventListener("click", event => { if (event.target === dialog) {
                const bounds = dialog.getBoundingClientRect();
                if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
            }});
        }
        if (!dialog.open) dialog.showModal();
    }
    async function requireLogin() {
        let response, data;
        try {
            response = await fetch("../api/account.php", {
                method: "POST", credentials: "same-origin",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ action: "session" })
            });
            data = await response.json();
        } catch {
            throw new Error("Não foi possível verificar sua sessão. Tente novamente.");
        }
        if (response.status === 401) { showLogin(); return false; }
        if (!response.ok || !data.ok) throw new Error(data.message || "Não foi possível verificar sua sessão. Tente novamente.");
        return true;
    }
    window.solidPurchaseAuth = { requireLogin };
    document.addEventListener("DOMContentLoaded", async () => {
        document.querySelectorAll('a[href="checkout.php"]').forEach(link => {
            link.addEventListener("click", async event => {
                event.preventDefault();
                try { if (await requireLogin()) window.location.href = link.href; }
                catch (error) {
                    const message = document.querySelector("#coupon-message");
                    if (message) message.textContent = error.message;
                }
            });
        });
        const content = document.querySelector("#checkout-content");
        if (!content) return;
        content.inert = true;
        try { content.inert = !await requireLogin(); }
        catch (error) { document.querySelector("#checkout-message").textContent = error.message; }
    });
})();
