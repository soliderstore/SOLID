document.addEventListener("DOMContentLoaded", () => {
    const accountKey = "solid-account";
    const sessionKey = "solid-session";
    const getAccount = () => { try { return JSON.parse(localStorage.getItem(accountKey)); } catch { return null; } };
    const saveAccount = account => localStorage.setItem(accountKey, JSON.stringify(account));
    const getSession = () => localStorage.getItem(sessionKey) === "active" && getAccount();
    const escapeHtml = (value = "") => String(value).replace(/[&<>"]/g, char => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[char]);

    function formatPhone(value) {
        const digits = value.replace(/\D/g, "").slice(0, 11);
        if (digits.length <= 2) return digits ? `(${digits}` : "";
        if (digits.length <= 7) return `(${digits.slice(0, 2)}) ${digits.slice(2)}`;
        return `(${digits.slice(0, 2)}) ${digits.slice(2, 7)}-${digits.slice(7)}`;
    }

    function setupMasks(root = document) {
        root.querySelectorAll("[data-phone]").forEach(input => input.addEventListener("input", () => { input.value = formatPhone(input.value); }));
        root.querySelectorAll("[data-zip]").forEach(input => input.addEventListener("input", () => { input.value = input.value.replace(/\D/g, "").slice(0, 8).replace(/(\d{5})(\d)/, "$1-$2"); }));
    }

    function readImage(input) {
        const file = input.files?.[0];
        if (!file) return Promise.resolve(null);
        if (!file.type.startsWith("image/") || file.size > 1500000) return Promise.reject(new Error("Escolha uma imagem de até 1,5 MB."));
        return new Promise(resolve => { const reader = new FileReader(); reader.onload = () => resolve(reader.result); reader.readAsDataURL(file); });
    }

    function fields(prefix, account = {}) {
        return `<div class="account-form-grid">
            <label>Nome completo<input id="${prefix}-name" type="text" required value="${escapeHtml(account.name)}"></label>
            <label>WhatsApp<input id="${prefix}-phone" data-phone type="tel" required inputmode="numeric" maxlength="15" value="${escapeHtml(account.phone)}" placeholder="(00) 00000-0000"></label>
            <label class="account-full">E-mail<input id="${prefix}-email" type="email" required value="${escapeHtml(account.email)}" placeholder="nome@exemplo.com"></label>
            <label>CPF <small>(opcional)</small><input id="${prefix}-cpf" type="text" value="${escapeHtml(account.cpf)}" placeholder="000.000.000-00"></label>
            <label>CEP<input id="${prefix}-zip" data-zip type="text" required inputmode="numeric" maxlength="9" value="${escapeHtml(account.zip)}" placeholder="00000-000"></label>
            <label class="account-full">Rua / Avenida<input id="${prefix}-address" type="text" required value="${escapeHtml(account.address)}"></label>
            <label>Número<input id="${prefix}-number" type="text" required value="${escapeHtml(account.number)}"></label>
            <label>Complemento <small>(opcional)</small><input id="${prefix}-complement" type="text" value="${escapeHtml(account.complement)}"></label>
            <label>Bairro<input id="${prefix}-neighborhood" type="text" required value="${escapeHtml(account.neighborhood)}"></label>
            <label>Cidade / UF<input id="${prefix}-city" type="text" required value="${escapeHtml(account.city)}" placeholder="Ex.: Belo Horizonte / MG"></label>
        </div>`;
    }

    function collect(prefix, current = {}) {
        const get = key => document.querySelector(`#${prefix}-${key}`).value.trim();
        return { ...current, name: get("name"), phone: get("phone"), email: get("email").toLowerCase(), cpf: get("cpf"), zip: get("zip"), address: get("address"), number: get("number"), complement: get("complement"), neighborhood: get("neighborhood"), city: get("city"), newsletter: document.querySelector(`#${prefix}-newsletter`).checked };
    }

    async function savePhoto(input, account, message) {
        try { const photo = await readImage(input); if (photo) account.profileImage = photo; return true; }
        catch (error) { message.textContent = error.message; return false; }
    }

    const registerForm = document.querySelector("#register-form");
    if (registerForm) {
        setupMasks(registerForm);
        registerForm.addEventListener("submit", async event => {
            event.preventDefault();
            const message = document.querySelector("#account-message");
            if (getAccount()) { message.textContent = "Já existe uma conta criada neste navegador. Entre para continuar."; return; }
            const account = collect("register");
            account.password = document.querySelector("#register-password").value;
            if (!await savePhoto(document.querySelector("#register-image"), account, message)) return;
            saveAccount(account); localStorage.setItem(sessionKey, "active"); window.location.href = "profile.html";
        });
    }

    const loginForm = document.querySelector("#login-form");
    if (loginForm) loginForm.addEventListener("submit", event => {
        event.preventDefault();
        const account = getAccount();
        const message = document.querySelector("#account-message");
        if (!account || account.email !== document.querySelector("#login-email").value.trim().toLowerCase() || account.password !== document.querySelector("#login-password").value) { message.textContent = "E-mail ou senha incorretos. Crie uma conta se ainda não possui cadastro."; return; }
        localStorage.setItem(sessionKey, "active"); window.location.href = "profile.html";
    });

    const content = document.querySelector("#profile-content");
    if (!content) return;

    function renderProfile() {
        const account = getSession();
        if (!account) { content.innerHTML = `<div class="account-card account-empty"><i class="fa-regular fa-user"></i><h2>Entre na sua conta</h2><p>Faça login ou crie uma conta para acessar este espaço.</p><a href="login.html" class="btn-primary">Entrar</a><a href="register.html" class="btn-secondary">Criar conta</a></div>`; return; }
        const photo = account.profileImage ? `<img src="${account.profileImage}" alt="Foto de ${escapeHtml(account.name)}">` : account.name.charAt(0).toUpperCase();
        content.innerHTML = `<div class="account-card profile-card"><div class="profile-avatar">${photo}</div><span>MINHA CONTA</span><h2>Olá, ${escapeHtml(account.name.split(" ")[0])}!</h2><p>${escapeHtml(account.email)}</p><div class="profile-data"><div><small>WhatsApp</small><strong>${escapeHtml(account.phone)}</strong></div><div><small>Endereço</small><strong>${escapeHtml(account.address)}, ${escapeHtml(account.number)}${account.complement ? ` - ${escapeHtml(account.complement)}` : ""}<br>${escapeHtml(account.neighborhood)} · ${escapeHtml(account.city)}<br>CEP: ${escapeHtml(account.zip)}</strong></div><div><small>Novidades por e-mail</small><strong>${account.newsletter ? "Ativadas" : "Não ativadas"}</strong></div></div><div class="profile-links"><a href="wishlist.html"><i class="fa-regular fa-heart"></i> Meus favoritos</a><a href="cart.html"><i class="fa-solid fa-bag-shopping"></i> Meu carrinho</a></div><button id="edit-profile" class="profile-edit" type="button">Editar meus dados</button><button id="logout-button" type="button">Sair da conta</button></div>`;
        document.querySelector("#edit-profile").addEventListener("click", renderEdit);
        document.querySelector("#logout-button").addEventListener("click", () => { localStorage.removeItem(sessionKey); window.location.href = "login.html"; });
    }

    function renderEdit() {
        const account = getSession();
        content.innerHTML = `<section class="account-card edit-profile-card"><h1>Editar dados</h1><p>Mantenha suas informações e endereço atualizados.</p><form id="profile-form" class="account-form">${fields("profile", account)}<label class="account-full">Foto de perfil <small>(opcional, até 1,5 MB)</small><input id="profile-image" type="file" accept="image/*"></label><label class="newsletter-choice account-full"><input id="profile-newsletter" type="checkbox" ${account.newsletter ? "checked" : ""}> Quero receber lançamentos, promoções e novidades por e-mail.</label><p id="account-message" class="account-message" role="alert"></p><button class="btn-primary" type="submit">Salvar alterações</button><button id="cancel-edit" class="account-cancel" type="button">Cancelar</button></form></section>`;
        const form = document.querySelector("#profile-form"); setupMasks(form);
        document.querySelector("#cancel-edit").addEventListener("click", renderProfile);
        form.addEventListener("submit", async event => { event.preventDefault(); const message = document.querySelector("#account-message"); const updated = collect("profile", account); if (!await savePhoto(document.querySelector("#profile-image"), updated, message)) return; saveAccount(updated); renderProfile(); });
    }

    renderProfile();
});
