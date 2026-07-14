document.addEventListener("DOMContentLoaded", () => {
    const content = document.querySelector("#checkout-content");
    const form = document.querySelector("#checkout-form");
    const itemsContainer = document.querySelector("#checkout-items");
    const totalElement = document.querySelector("#checkout-total");

    function getCart() {
        try {
            const cart = JSON.parse(localStorage.getItem("solid-cart"));
            return Array.isArray(cart) ? cart : [];
        } catch {
            return [];
        }
    }

    function format(value) {
        return value.toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
    }

    const cart = getCart();

    if (cart.length === 0) {
        content.innerHTML = `
            <div class="checkout-empty">
                <h2>Seu carrinho está vazio</h2>
                <p>Adicione produtos antes de finalizar a compra.</p>
                <a href="shop.html" class="btn-primary">Ir para a loja</a>
            </div>`;
        return;
    }

    const orderTotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    itemsContainer.innerHTML = cart.map(item => `
        <div class="checkout-item">
            <img src="${item.image}" alt="${item.name}">
            <div><strong>${item.quantity}x ${item.name}</strong><small>Tam. ${item.size} · ${item.color}</small></div>
            <span>${format(item.price * item.quantity)}</span>
        </div>`).join("");
    totalElement.textContent = format(orderTotal);

    form.addEventListener("submit", event => {
        event.preventDefault();

        const customer = {
            name: document.querySelector("#customer-name").value.trim(),
            phone: document.querySelector("#customer-phone").value.trim(),
            email: document.querySelector("#customer-email").value.trim(),
            cpf: document.querySelector("#customer-cpf").value.trim(),
            address: document.querySelector("#customer-address").value.trim(),
            number: document.querySelector("#customer-number").value.trim(),
            complement: document.querySelector("#customer-complement").value.trim(),
            neighborhood: document.querySelector("#customer-neighborhood").value.trim(),
            zip: document.querySelector("#customer-zip").value.trim(),
            city: document.querySelector("#customer-city").value.trim(),
            delivery: document.querySelector("#delivery-method").value,
            payment: document.querySelector("#payment-method").value,
            paymentDetails: document.querySelector("#payment-details").value.trim(),
            notes: document.querySelector("#customer-notes").value.trim()
        };

        const orderItems = cart.map(item =>
            `• ${item.quantity}x ${item.name} (Tam: ${item.size} | Cor: ${item.color}) — ${format(item.price * item.quantity)}`
        ).join("\n");

        const message = `Olá! Quero finalizar este pedido na SØLID.\n\n*DADOS DO CLIENTE*\nNome: ${customer.name}\nWhatsApp: ${customer.phone}\nE-mail: ${customer.email}${customer.cpf ? `\nCPF: ${customer.cpf}` : ""}\n\n*ENDEREÇO*\n${customer.address}, ${customer.number}${customer.complement ? ` - ${customer.complement}` : ""}\nBairro: ${customer.neighborhood}\nCEP: ${customer.zip}\nCidade/UF: ${customer.city}\n\n*RECEBIMENTO E PAGAMENTO*\nRecebimento: ${customer.delivery}\nPagamento: ${customer.payment}${customer.paymentDetails ? `\nDetalhes: ${customer.paymentDetails}` : ""}\n\n*PEDIDO*\n${orderItems}\n\n*TOTAL DOS PRODUTOS: ${format(orderTotal)}*\n\n${customer.notes ? `*OBSERVAÇÕES*\n${customer.notes}\n\n` : ""}Aguardo a confirmação do pedido e do frete.`;
        window.open(`https://wa.me/5531998244421?text=${encodeURIComponent(message)}`, "_blank", "noopener");
    });
});
