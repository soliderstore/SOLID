(() => {
    const states = new WeakMap();
    const zipDigits = input => input.value.replace(/\D/g, "");
    function handleZip(event) {
        const input = event.target;
        if (!input.matches('[data-zip], #customer-zip')) return;
        let state = states.get(input);
        if (!state) {
            const status = document.createElement("small");
            status.className = "address-lookup-status";
            status.setAttribute("role", "status");
            status.id = input.id + "-status";
            input.insertAdjacentElement("afterend", status);
            input.setAttribute("aria-describedby", [input.getAttribute("aria-describedby"), status.id].filter(Boolean).join(" "));
            state = { status };
            states.set(input, state);
        }
        const cep = zipDigits(input);
        if (state.cep === cep) return;
        state.cep = cep;
        clearTimeout(state.timer);
        state.controller?.abort();
        state.status.textContent = "";
        if (cep.length !== 8) return;
        state.timer = setTimeout(() => lookup(input, state, cep), 350);
    }
    async function lookup(input, state, cep) {
        const controller = new AbortController();
        state.controller = controller;
        const timeout = setTimeout(() => controller.abort(), 8000);
        state.status.textContent = "Buscando endereço...";
        const prefix = input.id.replace(/-zip$/, "");
        const field = name => document.getElementById(`${prefix}-${name}`);
        const previous = Object.fromEntries(["address", "neighborhood", "city"].map(name => [name, field(name)?.value]));
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`, { signal: controller.signal });
            if (!response.ok) throw new Error("lookup");
            const data = await response.json();
            if (zipDigits(input) !== cep || state.controller !== controller) return;
            if (data.erro) {
                state.status.textContent = "CEP não encontrado. Confira o CEP ou preencha o endereço manualmente.";
                return;
            }
            const values = { address: data.logradouro || "", neighborhood: data.bairro || "", city: data.localidade && data.uf ? `${data.localidade} / ${data.uf}` : "" };
            for (const [name, value] of Object.entries(values)) {
                const target = field(name);
                if (target && target.value === previous[name]) {
                    target.value = value;
                    target.dispatchEvent(new Event("input", { bubbles: true }));
                }
            }
            state.status.textContent = values.address && values.neighborhood && values.city
                ? "Endereço preenchido. Informe o número e, se necessário, o complemento."
                : "CEP localizado. Complete os campos de endereço que faltam e informe o número.";
        } catch {
            if (zipDigits(input) === cep && state.controller === controller) {
                state.status.textContent = "Não foi possível consultar o CEP. Preencha o endereço manualmente.";
            }
        } finally { clearTimeout(timeout); }
    }
    document.addEventListener("input", handleZip);
    // Também funciona ao colar ou preencher o CEP pelo navegador.
    document.addEventListener("change", handleZip);
})();
