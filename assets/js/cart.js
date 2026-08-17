/* ==========================================================
   SØLID - Carrinho
========================================================== */

document.addEventListener("DOMContentLoaded", () => {
    const productsContainer = document.querySelector(".cart-products");
    const subtotal = document.getElementById("subtotal");
    const total = document.getElementById("total");
    const summary = document.querySelector(".cart-summary");
    const cartContent = document.querySelector(".cart-content");

    function format(value) {
        return value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function getCart() {
        try {
            const cart = JSON.parse(localStorage.getItem("solid-cart"));
            return Array.isArray(cart) ? cart : [];
        } catch {
            return [];
        }
    }

    function saveCart(cart) {
        localStorage.setItem("solid-cart", JSON.stringify(cart));
    }

    function updateTotals(cart) {
        const cartTotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        subtotal.textContent = format(cartTotal);
        total.textContent = format(cartTotal);
    }

    function renderCart() {
        const cart = getCart();
        updateTotals(cart);

        if (cart.length === 0) {
            document.querySelector(".cart-page").classList.add("cart-is-empty");
            cartContent.classList.add("empty-cart-content");
            productsContainer.innerHTML = `
                <div class="empty-cart-card">
                    <div class="empty-cart-icon"><i class="fa-solid fa-bag-shopping"></i></div>
                    <span>SEU CARRINHO</span>
                    <h2>Ainda não há produtos por aqui.</h2>
                    <p>Descubra peças feitas para acompanhar sua identidade e escolha a sua favorita.</p>
                    <a href="shop.html" class="btn-primary">Explorar a coleção <i class="fa-solid fa-arrow-right"></i></a>
                </div>`;
            summary.style.display = "none";
            return;
        }

        document.querySelector(".cart-page").classList.remove("cart-is-empty");
        cartContent.classList.remove("empty-cart-content");
        summary.style.display = "block";
        productsContainer.innerHTML = cart.map((item, index) => `
            <div class="card cart-item" data-index="${index}">
                <div class="card-image"><img src="${item.image}" alt="${item.name}"></div>
                <div class="card-content">
                    <h3 class="card-title">${item.name}</h3>
                    <p>Tamanho: ${item.size} · Cor: ${item.color}</p>
                    <h4 class="card-price">${format(item.price)}</h4>
                    <div class="cart-actions">
                        <div class="quantity">
                            <button class="minus" type="button" aria-label="Diminuir quantidade">−</button>
                            <input type="number" value="${item.quantity}" min="1" aria-label="Quantidade">
                            <button class="plus" type="button" aria-label="Aumentar quantidade">+</button>
                        </div>
                        <button class="remove" type="button">Remover</button>
                    </div>
                </div>
            </div>`).join("");
    }

    productsContainer.addEventListener("click", event => {
        const card = event.target.closest(".cart-item");
        if (!card) return;

        const index = Number(card.dataset.index);
        const cart = getCart();
        if (!cart[index]) return;

        if (event.target.closest(".plus")) cart[index].quantity++;
        else if (event.target.closest(".minus")) cart[index].quantity = Math.max(1, cart[index].quantity - 1);
        else if (event.target.closest(".remove")) cart.splice(index, 1);
        else return;

        saveCart(cart);
        renderCart();
    });

    productsContainer.addEventListener("change", event => {
        if (!event.target.matches("input[type='number']")) return;
        const card = event.target.closest(".cart-item");
        const cart = getCart();
        const index = Number(card.dataset.index);
        if (!cart[index]) return;

        cart[index].quantity = Math.max(1, Number(event.target.value) || 1);
        saveCart(cart);
        renderCart();
    });

    renderCart();
});
