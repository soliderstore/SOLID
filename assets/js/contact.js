document.addEventListener("DOMContentLoaded", () => {
    const form = document.querySelector("#contact-form");
    const phoneInput = document.querySelector("#contact-phone");

    function formatPhone(value) {
        const digits = value.replace(/\D/g, "").slice(0, 11);
        if (digits.length <= 2) return digits ? `(${digits}` : "";
        if (digits.length <= 7) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    }

    phoneInput.addEventListener("input", () => {
        phoneInput.value = formatPhone(phoneInput.value);
        const phoneDigits = phoneInput.value.replace(/\D/g, "");
        phoneInput.setCustomValidity(phoneDigits.length === 11 ? "" : "Digite um WhatsApp válido com DDD.");
    });

    form.addEventListener("submit", event => {
        event.preventDefault();
        phoneInput.dispatchEvent(new Event("input"));

        if (!form.reportValidity()) return;

        const name = document.querySelector("#contact-name").value.trim();
        const subject = document.querySelector("#contact-subject").value;
        const message = document.querySelector("#contact-message").value.trim();
        const text = `Olá, SØLID! Tenho uma dúvida pelo site.\n\n*DADOS DE CONTATO*\nNome: ${name}\nWhatsApp: ${phoneInput.value}\n\n*ASSUNTO*\n${subject}\n\n*DÚVIDA*\n${message}\n\nAguardo o retorno. Obrigado!`;

        window.open(`https://wa.me/5531998244421?text=${encodeURIComponent(text)}`, "_blank", "noopener");
    });
});
