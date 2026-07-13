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

            <div class="product-gallery">

                <img
                    class="main-image"
                    id="main-image"
                    src="${product.images[0]}"
                    alt="${product.name}">

                <div class="thumbs">

                    ${product.images.map(image => `

                        <img
                            src="${image}"
                            alt="${product.name}"
                            onclick="document.getElementById('main-image').src='${image}'">

                    `).join("")}

                </div>

            </div>

            <div class="product-info">

                <span class="badge">

                    ${product.badge}

                </span>

                <h1>

                    ${product.name}

                </h1>

                <h2>

                    R$ ${product.price.toFixed(2)}

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

                <button id="add-cart">

                    Adicionar ao Carrinho

                </button>

            </div>

        </div>

    `;
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