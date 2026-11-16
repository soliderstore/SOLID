// Atualiza também produtos que já estavam salvos no carrinho ou favoritos.
for (const key of ["solid-cart", "solid-wishlist"]) {
    try {
        const items = JSON.parse(localStorage.getItem(key));
        if (!Array.isArray(items)) continue;
        localStorage.setItem(key, JSON.stringify(items.filter(item => PRODUCTS.some(product => String(product.id) === String(item.id))).map(item => {
            const current = PRODUCTS.find(product => String(product.id) === String(item.id));
            return current ? { ...item, name: current.name, price: current.price, image: current.images[0] } : item;
        })));
    } catch { /* A loja continua disponível quando o armazenamento está bloqueado. */ }
}
