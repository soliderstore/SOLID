document.addEventListener("DOMContentLoaded", () => {
    const content = document.querySelector("#checkout-content");
    const form = document.querySelector("#checkout-form");
    const itemsContainer = document.querySelector("#checkout-items");
    const totalElement = document.querySelector("#checkout-total");
    const checkoutCoupon = document.querySelector("#checkout-coupon");
    const phoneInput = document.querySelector("#customer-phone");
    const cpfInput = document.querySelector("#customer-cpf");
    const zipInput = document.querySelector("#customer-zip");
    const emailInput = document.querySelector("#customer-email");

    function digits(value, limit) {
        return value.replace(/\D/g, "").slice(0, limit);
    }

    function formatPhone(value) {
        const valueDigits = digits(value, 11);
        if (valueDigits.length <= 2) return valueDigits ? `(${valueDigits}` : "";
        if (valueDigits.length <= 7) return `(${valueDigits.slice(0, 2)}) ${valueDigits.slice(2)}`;
        return `(${valueDigits.slice(0, 2)}) ${valueDigits.slice(2, 7)}-${valueDigits.slice(7)}`;
    }

    function formatCpf(value) {
        const valueDigits = digits(value, 11);
        return valueDigits
            .replace(/(\d{3})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d)/, "$1.$2")
            .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    }

    function formatZip(value) {
        const valueDigits = digits(value, 8);
        return valueDigits.replace(/(\d{5})(\d)/, "$1-$2");
    }

    function isValidCpf(value) {
        const valueDigits = digits(value, 11);
        if (valueDigits.length !== 11 || /^(\d)\1{10}$/.test(valueDigits)) return false;

        const calculateDigit = length => {
            const sum = valueDigits.slice(0, length).split("").reduce((total, digit, index) => total + Number(digit) * (length + 1 - index), 0);
            const remainder = (sum * 10) % 11;
            return remainder === 10 ? 0 : remainder;
        };

        return calculateDigit(9) === Number(valueDigits[9]) && calculateDigit(10) === Number(valueDigits[10]);
    }

    phoneInput.addEventListener("input", () => {
        phoneInput.value = formatPhone(phoneInput.value);
        phoneInput.setCustomValidity(digits(phoneInput.value, 11).length === 11 ? "" : "Digite um WhatsApp válido com DDD.");
    });

    cpfInput.addEventListener("input", () => {
        cpfInput.value = formatCpf(cpfInput.value);
        cpfInput.setCustomValidity(!cpfInput.value || isValidCpf(cpfInput.value) ? "" : "Digite um CPF válido.");
    });

    zipInput.addEventListener("input", () => {
        zipInput.value = formatZip(zipInput.value);
        zipInput.setCustomValidity(digits(zipInput.value, 8).length === 8 ? "" : "Digite um CEP válido.");
    });

    emailInput.addEventListener("input", () => {
        const isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value);
        emailInput.setCustomValidity(isValidEmail ? "" : "Digite um e-mail válido, como nome@exemplo.com.");
    });

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

    const orderSubtotal = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const couponApplied = localStorage.getItem("solid-coupon") === "SOLID10" && localStorage.getItem("solid-first-purchase-used") !== "true";
    const discountValue = couponApplied ? orderSubtotal * 0.1 : 0;
    const orderTotal = orderSubtotal - discountValue;
    itemsContainer.innerHTML = cart.map(item => `
        <div class="checkout-item">
            <img src="${item.image}" alt="${item.name}">
            <div><strong>${item.quantity}x ${item.name}</strong><small>Tam. ${item.size} · ${item.color}</small></div>
            <span>${format(item.price * item.quantity)}</span>
        </div>`).join("");
    checkoutCoupon.innerHTML = couponApplied
        ? `<span>Cupom SOLID10</span><strong>− ${format(discountValue)}</strong>`
        : "";
    totalElement.textContent = format(orderTotal);

    form.addEventListener("submit", event => {
        event.preventDefault();

        [phoneInput, cpfInput, zipInput, emailInput].forEach(input =>
            input.dispatchEvent(new Event("input"))
        );

        if (!form.reportValidity()) return;

        const orderCode = `SOLID-${Date.now().toString().slice(-6)}`;

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

        const discountLine = couponApplied ? `\nCupom SOLID10: − ${format(discountValue)}` : "";
        const message = `Olá! Quero confirmar meu pedido na SØLID.\n\n*PEDIDO ${orderCode}*\nData: ${new Date().toLocaleString("pt-BR")}\n\n*DADOS DO CLIENTE*\nNome: ${customer.name}\nWhatsApp: ${customer.phone}\nE-mail: ${customer.email}${customer.cpf ? `\nCPF: ${customer.cpf}` : ""}\n\n*ENDEREÇO*\n${customer.address}, ${customer.number}${customer.complement ? ` - ${customer.complement}` : ""}\nBairro: ${customer.neighborhood}\nCEP: ${customer.zip}\nCidade/UF: ${customer.city}\n\n*RECEBIMENTO E PAGAMENTO*\nRecebimento: ${customer.delivery}\nFrete: a confirmar pela loja\nPagamento: ${customer.payment}${customer.paymentDetails ? `\nDetalhes do pagamento: ${customer.paymentDetails}` : ""}\n\n*ITENS DO PEDIDO*\n${orderItems}\n\nSubtotal: ${format(orderSubtotal)}${discountLine}\n*TOTAL DOS PRODUTOS: ${format(orderTotal)}*\n\n${customer.notes ? `*OBSERVAÇÕES*\n${customer.notes}\n\n` : ""}Aguardo a confirmação do pedido, disponibilidade e valor do frete.`;
        if (couponApplied) {
            localStorage.setItem("solid-first-purchase-used", "true");
            localStorage.removeItem("solid-coupon");
        }
        window.open(`https://wa.me/5531998244421?text=${encodeURIComponent(message)}`, "_blank", "noopener");
    });
});
