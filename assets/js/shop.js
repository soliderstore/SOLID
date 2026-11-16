const container = document.querySelector("#products");
const projectRoot = ["localhost", "127.0.0.1"].includes(window.location.hostname) ? "/SOLID/" : "/";

if (container) {

    const visibleProducts = window.location.pathname.endsWith("/index.php") || window.location.pathname.endsWith("/")
        ? PRODUCTS.filter(product => product.featured) : PRODUCTS;
    visibleProducts.forEach(product => {

        container.innerHTML += `

            <div class="card">

                <div class="card-image">

                    <span class="badge">${product.badge}</span>

                    <img src="${projectRoot + product.images[0].replace(/^\.\.\//, "")}" alt="${product.name}">

                </div>

                <div class="card-content">

                    <h3 class="card-title">

                        ${product.name}

                    </h3>

                    <p class="card-price">

                        ${product.price.toLocaleString("pt-BR", { style: "currency", currency: "BRL" })}

                    </p>

                    <button
                        onclick="window.location.href='${projectRoot}pages/product.php?id=${product.id}'">

                        Ver Produto

                    </button>

                </div>

            </div>

        `;

    });

}
