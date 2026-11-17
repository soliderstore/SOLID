const params = new URLSearchParams(window.location.search);

const id = Number(params.get("id"));

const product = PRODUCTS.find(item => item.id === id);

const container = document.querySelector("#product-container");

if (!container) {

    console.error("Elemento #product-container não encontrado.");

} else if (!product) {

    container.innerHTML = `
        <h1>Produto não encontrado.</h1>
    `;

} else {

    container.innerHTML = `

        <div class="product-layout">

            <div class="product-gallery"></div>

            <div class="product-info">

                <span class="badge">

                    ${product.badge}

                </span>

                <h1>

                    ${product.name}

                </h1>

                <h2>

                    ${product.price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" })}

                </h2>

                <p>

                    ${product.description}

                </p>

                <h4>Tamanho</h4>

                <select id="size">

                    ${product.sizes.map(size => `

                        <option value="${size}">${size}</option>

                    `).join("")}

                </select>

                <h4>Cor</h4>

                <select id="color">

                    ${product.colors.map(color => `

                        <option value="${color}">${color}</option>

                    `).join("")}

                </select>

                <button id="add-cart" ${product.stock <= 0 ? "disabled" : ""}>

                    ${product.stock <= 0 ? "Produto esgotado" : "Adicionar ao Carrinho"}

                </button>

                <button id="add-wishlist" type="button">

                    <i class="fa-regular fa-heart"></i> Adicionar aos Favoritos

                </button>

            </div>

        </div>

    `;
}

const addWishlistButton = document.getElementById("add-wishlist");

if (addWishlistButton) {

    addWishlistButton.addEventListener("click", () => {

        const size = document.getElementById("size").value;
        const color = document.getElementById("color").value;
        let wishlist;

        try {
            wishlist = JSON.parse(localStorage.getItem("solid-wishlist")) || [];
        } catch {
            wishlist = [];
        }

        const alreadySaved = wishlist.some(item => item.id === product.id);

        if (!alreadySaved) {
            wishlist.push({
                id: product.id,
                name: product.name,
                price: product.price,
                image: product.images[0],
                size,
                color
            });
            localStorage.setItem("solid-wishlist", JSON.stringify(wishlist));
        }

        addWishlistButton.innerHTML = '<i class="fa-solid fa-heart"></i> Produto favoritado';
        addWishlistButton.disabled = true;
    });
}
/* =====================================================
   CARRINHO
===================================================== */

const addCartButton = document.getElementById("add-cart");

if (addCartButton) {

    addCartButton.addEventListener("click", () => {

        const size = document.getElementById("size").value;

        const color = document.getElementById("color").value;

        let cart = JSON.parse(localStorage.getItem("solid-cart")) || [];

        const existingProduct = cart.find(item =>

            item.id === product.id &&
            item.size === size &&
            item.color === color

        );

        if (existingProduct) {

            existingProduct.quantity++;

        } else {

            cart.push({

                id: product.id,

                name: product.name,

                price: product.price,

                image: product.images[0],

                size: size,

                color: color,

                quantity: 1

            });

        }

        localStorage.setItem(

            "solid-cart",

            JSON.stringify(cart)

        );

        addCartButton.innerHTML = "✔ Produto Adicionado";

        addCartButton.disabled = true;

        setTimeout(() => {

            addCartButton.innerHTML = "Adicionar ao Carrinho";

            addCartButton.disabled = false;

        }, 2000);

    });

}
