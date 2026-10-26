const PRODUCTS = [

    {
        id: 1,

        sku: "SOLID001",

        featured: true,

        badge: "NOVO",

        category: "Moletom",

        name: "Moletom Rei dos Reis",

        description: "Moletom oversized premium desenvolvido em algodão de alta qualidade. Confortável, resistente e perfeito para qualquer ocasião.",

        price: 129.99,


        rating: 5,

        reviews: 18,

        stock: 20,

        images: [

            "../assets/images/products/M1.jpeg",

            "../assets/images/products/M2.jpeg",

            "../assets/images/products/estampa.jpeg"

        ],

        sizes: [

            "P",

            "M",

            "G",

            "GG"

        ],

        colors: [

            "Preto",

            "Branco"

        ]
    },

    {
        id: 2,

        sku: "SOLID002",

        featured: true,

        badge: "LANÇAMENTO",

        category: "Moletom",

        name: "Moletom Feminino Rei dos Reis",

        description: "Moletom premium feminino SØLID com acabamento de alta qualidade e modelagem confortável.",

        price: 129.99,


        rating: 5,

        reviews: 11,

        stock: 15,

        images: [

            "../assets/images/products/F1.jpeg",

            "../assets/images/products/F2.jpeg",

            "../assets/images/products/estampa.jpeg"

        ],

        sizes: [

            "P",

            "M",

            "G"

        ],

        colors: [

            "Preto",

            "Branco"

        ]
    }

];  

// Atualiza também produtos que já estavam salvos no carrinho ou favoritos.
for (const key of ["solid-cart", "solid-wishlist"]) {
    try {
        const items = JSON.parse(localStorage.getItem(key));
        if (!Array.isArray(items)) continue;
        localStorage.setItem(key, JSON.stringify(items.map(item => {
            const current = PRODUCTS.find(product => String(product.id) === String(item.id));
            return current ? { ...item, price: current.price } : item;
        })));
    } catch { /* A loja continua disponível quando o armazenamento está bloqueado. */ }
}
