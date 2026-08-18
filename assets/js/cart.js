/* ==========================================================
   SØLID - Carrinho
========================================================== */

document.addEventListener("DOMContentLoaded", () => {
    const productsContainer = document.querySelector(".cart-products");
    const subtotal = document.getElementById("subtotal");
    const total = document.getElementById("total");
    const summary = document.querySelector(".cart-summary");
    const cartContent = document.querySelector(".cart-content");
    const discountElement = document.getElementById("discount");
    const couponInput = document.getElementById("coupon-code");
    const applyCouponButton = document.getElementById("apply-coupon");
    const couponMessage = document.getElementById("coupon-message");
    const validCoupon = "SOLID10";
    const apiUrl = "../api/account.php";

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

    function getCoupon() {
        return localStorage.getItem("solid-coupon") === validCoupon ? validCoupon : null;
    }

    async function accountApi(action) {
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ action })
        });
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.message || "Não foi possível validar o cupom.");
        return data;
    }

    function getDiscount(subtotalValue) {
        return getCoupon() ? subtotalValue * 0.1 : 0;
    }

    function updateTotals(cart) {
        const cartTotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
        const discount = getDiscount(cartTotal);
        subtotal.textContent = format(cartTotal);
        discountElement.textContent = `− ${format(discount)}`;
        total.textContent = format(cartTotal - discount);

        if (getCoupon()) {
            couponInput.value = validCoupon;
            couponMessage.innerHTML = "Cupom <strong>SOLID10</strong> aplicado: 10% de desconto.";
            couponMessage.classList.add("coupon-success");
        }
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
                    <a href="shop.php" class="btn-primary">Explorar a coleção <i class="fa-solid fa-arrow-right"></i></a>
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
                        <div class="remove-control">
                            <button class="remove" type="button">Remover</button>
                            <div class="remove-confirm" aria-live="polite">
                                <span>Remover item?</span>
                                <button class="confirm-remove" type="button">Confirmar</button>
                                <button class="cancel-remove" type="button" aria-label="Cancelar remoção">Cancelar</button>
                            </div>
                        </div>
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
        else if (event.target.closest(".remove")) {
            card.querySelector(".remove-control")?.classList.add("is-confirming");
            return;
        }
        else if (event.target.closest(".cancel-remove")) {
            card.querySelector(".remove-control")?.classList.remove("is-confirming");
            return;
        }
        else if (event.target.closest(".confirm-remove")) cart.splice(index, 1);
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

    applyCouponButton.addEventListener("click", async () => {
        const code = couponInput.value.trim().toUpperCase();

        if (code === validCoupon) {
            try {
                const status = await accountApi("coupon_status");
                if (status.couponUsed) throw new Error("Este cupom já foi utilizado nesta conta.");
                localStorage.setItem("solid-coupon", validCoupon);
                couponMessage.innerHTML = "Cupom <strong>SOLID10</strong> aplicado: 10% de desconto.";
                couponMessage.classList.add("coupon-success");
            } catch (error) {
                localStorage.removeItem("solid-coupon");
                couponMessage.textContent = error.message === "Faça login para continuar."
                    ? "Entre na sua conta para usar este cupom uma única vez."
                    : error.message;
                couponMessage.classList.remove("coupon-success");
            }
        } else {
            localStorage.removeItem("solid-coupon");
            couponMessage.textContent = "Cupom inválido. Tente SOLID10 para ganhar 10% de desconto.";
            couponMessage.classList.remove("coupon-success");
        }

        updateTotals(getCart());
    });

    renderCart();

    async function syncAppliedCoupon() {
        if (!getCoupon()) return;
        try {
            const status = await accountApi("coupon_status");
            if (status.couponUsed) throw new Error("Este cupom já foi utilizado nesta conta.");
        } catch (error) {
            localStorage.removeItem("solid-coupon");
            couponMessage.textContent = error.message === "Faça login para continuar."
                ? "Entre na sua conta para usar este cupom uma única vez."
                : error.message;
            couponMessage.classList.remove("coupon-success");
            updateTotals(getCart());
        }
    }

    syncAppliedCoupon();
});
