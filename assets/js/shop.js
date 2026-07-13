const container = document.querySelector("#products");

if (container) {

    PRODUCTS.forEach(product => {

        container.innerHTML += `

            <div class="card">

                <div class="card-image">

                    <span class="badge">${product.badge}</span>

                    <img src="${product.images[0]}" alt="${product.name}">

                </div>

                <div class="card-content">

                    <h3 class="card-title">

                        ${product.name}

                    </h3>

                    <p class="card-price">

                        R$ ${product.price.toFixed(2)}

                    </p>

                    <button
                        onclick="location.href='product.html?id=${product.id}'">

                        Ver Produto

                    </button>

                </div>

            </div>

        `;

    });

}