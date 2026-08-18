/* ==========================================================
   SØLID - Lista de desejos
========================================================== */

document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector("#wishlist-products");
    if (!container) return;

    function getWishlist() {
        try {
            const wishlist = JSON.parse(localStorage.getItem("solid-wishlist"));
            return Array.isArray(wishlist) ? wishlist : [];
        } catch {
            return [];
        }
    }

    function saveWishlist(wishlist) {
        localStorage.setItem("solid-wishlist", JSON.stringify(wishlist));
    }

    function format(value) {
        return value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    function render() {
        const wishlist = getWishlist();

        if (wishlist.length === 0) {
            container.innerHTML = `
                <div class="wishlist-empty">
                    <i class="fa-regular fa-heart"></i>
                    <h2>Sua lista de desejos está vazia</h2>
                    <p>Salve seus produtos favoritos para encontrá-los facilmente depois.</p>
                    <a href="shop.php" class="btn-primary">Conhecer produtos</a>
                </div>`;
            return;
        }

        container.innerHTML = wishlist.map((item, index) => `
            <article class="card wishlist-card" data-index="${index}">
                <div class="card-image">
                    <img src="${item.image}" alt="${item.name}">
                </div>
                <div class="card-content">
                    <h3 class="card-title">${item.name}</h3>
                    <p class="card-price">${format(item.price)}</p>
                    <button class="btn-primary add-to-cart" type="button">Adicionar ao Carrinho</button>
                    <button class="remove-wishlist" type="button">Remover</button>
                </div>
            </article>`).join("");
    }

    container.addEventListener("click", (event) => {
        const card = event.target.closest(".wishlist-card");
        if (!card) return;

        const index = Number(card.dataset.index);
        const wishlist = getWishlist();
        const item = wishlist[index];
        if (!item) return;

        if (event.target.closest(".remove-wishlist")) {
            wishlist.splice(index, 1);
            saveWishlist(wishlist);
            render();
            return;
        }

        if (event.target.closest(".add-to-cart")) {
            let cart;
            try {
                cart = JSON.parse(localStorage.getItem("solid-cart")) || [];
            } catch {
                cart = [];
            }

            const existingItem = cart.find(cartItem =>
                cartItem.id === item.id && cartItem.size === item.size && cartItem.color === item.color
            );

            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ ...item, quantity: 1 });
            }

            localStorage.setItem("solid-cart", JSON.stringify(cart));
            event.target.textContent = "Adicionado ao Carrinho";
        }
    });

    render();
});
