const container = document.querySelector("#products");
const projectRoot = new URL("../../", document.currentScript.src).pathname;

if (container) {

    const visibleProducts = window.location.pathname.endsWith("/index.php") || window.location.pathname.endsWith("/")
        ? PRODUCTS.filter(product => product.featured) : PRODUCTS;
    container.innerHTML = visibleProducts.map(product => `

            <div class="card">

                <div class="card-image">

                    <span class="badge">${product.badge}</span>

                    <img alt="${product.name}">

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

    `).join("");

    const print = `${projectRoot}assets/images/products/estampa.jpeg`;
    const placeholder = `${projectRoot}assets/images/logo/logo.png`;
    container.querySelectorAll(".card-image img").forEach((image, index) => {
        const { images, fallbackImages = [] } = visibleProducts[index];
        const [cover, ...gallery] = images;
        const sources = new Set([
            cover,
            ...gallery.filter(source => source !== print),
            ...fallbackImages,
            ...gallery,
            placeholder
        ]).values();

        function loadNextImage() {
            const { value, done } = sources.next();
            if (!done) image.src = value;
        }

        image.addEventListener("error", loadNextImage);
        loadNextImage();
    });

}
