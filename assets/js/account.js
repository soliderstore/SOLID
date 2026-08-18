document.addEventListener("DOMContentLoaded", () => {
    const accountKey = "solid-account";
    const sessionKey = "solid-session";
    const apiUrl = "../api/account.php";
    const getAccount = () => { try { return JSON.parse(localStorage.getItem(accountKey)); } catch { return null; } };
    const saveAccount = account => localStorage.setItem(accountKey, JSON.stringify(account));
    const getSession = () => localStorage.getItem(sessionKey) === "active" && getAccount();
    const escapeHtml = (value = "") => String(value).replace(/[&<>"]/g, char => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" })[char]);

    async function api(action, payload = {}) {
        const response = await fetch(apiUrl, {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ action, ...payload })
        });
        const rawResponse = await response.text();
        let data;

        try {
            data = JSON.parse(rawResponse);
        } catch {
            throw new Error("A API PHP não respondeu. Confirme se o projeto foi enviado ao InfinityFree e se o arquivo api/account.php está na hospedagem.");
        }
        if (!response.ok || !data.ok) throw new Error(data.message || "Não foi possível concluir esta ação.");
        return data;
    }

    function saveSession(user) {
        saveAccount(user);
        localStorage.setItem(sessionKey, "active");
    }

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
            const account = collect("register");
            account.password = document.querySelector("#register-password").value;
            if (!await savePhoto(document.querySelector("#register-image"), account, message)) return;
            try {
                const data = await api("register", account);
                saveSession(data.user);
                window.location.href = "profile.php";
            } catch (error) { message.textContent = error.message; }
        });
    }

    const loginForm = document.querySelector("#login-form");
    if (loginForm) loginForm.addEventListener("submit", event => {
        event.preventDefault();
        const message = document.querySelector("#account-message");
        api("login", { email: document.querySelector("#login-email").value.trim(), password: document.querySelector("#login-password").value })
            .then(data => { saveSession(data.user); window.location.href = "profile.php"; })
            .catch(error => { message.textContent = error.message; });
    });

    const content = document.querySelector("#profile-content");
    if (!content) return;

    async function renderProfile() {
        let account = null;
        try {
            const data = await api("session");
            account = data.user;
            saveSession(account);
        } catch {
            localStorage.removeItem(sessionKey);
            localStorage.removeItem(accountKey);
        }
        if (!account) { content.innerHTML = `<div class="account-card account-empty"><i class="fa-regular fa-user"></i><h2>Entre na sua conta</h2><p>Faça login ou crie uma conta para acessar este espaço.</p><a href="login.php" class="btn-primary">Entrar</a><a href="register.php" class="btn-secondary">Criar conta</a></div>`; return; }
        const photo = account.profileImage ? `<img src="${account.profileImage}" alt="Foto de ${escapeHtml(account.name)}">` : account.name.charAt(0).toUpperCase();
        content.innerHTML = `<div class="account-card profile-card"><div class="profile-avatar">${photo}</div><span>MINHA CONTA</span><h2>Olá, ${escapeHtml(account.name.split(" ")[0])}!</h2><p>${escapeHtml(account.email)}</p><div class="profile-data"><div><small>WhatsApp</small><strong>${escapeHtml(account.phone)}</strong></div><div><small>Endereço</small><strong>${escapeHtml(account.address)}, ${escapeHtml(account.number)}${account.complement ? ` - ${escapeHtml(account.complement)}` : ""}<br>${escapeHtml(account.neighborhood)} · ${escapeHtml(account.city)}<br>CEP: ${escapeHtml(account.zip)}</strong></div><div><small>Novidades por e-mail</small><strong>${account.newsletter ? "Ativadas" : "Não ativadas"}</strong></div></div><div class="profile-links"><a href="wishlist.php"><i class="fa-regular fa-heart"></i> Meus favoritos</a><a href="cart.php"><i class="fa-solid fa-bag-shopping"></i> Meu carrinho</a></div><button id="edit-profile" class="profile-edit" type="button">Editar meus dados</button><button id="logout-button" type="button">Sair da conta</button></div>`;
        document.querySelector("#edit-profile").addEventListener("click", () => renderEdit(account));
        document.querySelector("#logout-button").addEventListener("click", async () => {
            try { await api("logout"); } finally { localStorage.removeItem(sessionKey); localStorage.removeItem(accountKey); window.location.href = "login.php"; }
        });
    }

    function renderEdit(account) {
        content.innerHTML = `<section class="account-card edit-profile-card"><h1>Editar dados</h1><p>Mantenha suas informações e endereço atualizados.</p><form id="profile-form" class="account-form">${fields("profile", account)}<label class="account-full">Foto de perfil <small>(opcional, até 1,5 MB)</small><input id="profile-image" type="file" accept="image/*"></label><label class="newsletter-choice account-full"><input id="profile-newsletter" type="checkbox" ${account.newsletter ? "checked" : ""}> Quero receber lançamentos, promoções e novidades por e-mail.</label><p id="account-message" class="account-message" role="alert"></p><button class="btn-primary" type="submit">Salvar alterações</button><button id="cancel-edit" class="account-cancel" type="button">Cancelar</button></form></section>`;
        const form = document.querySelector("#profile-form"); setupMasks(form);
        document.querySelector("#cancel-edit").addEventListener("click", renderProfile);
        form.addEventListener("submit", async event => {
            event.preventDefault();
            const message = document.querySelector("#account-message");
            const updated = collect("profile", account);
            if (!await savePhoto(document.querySelector("#profile-image"), updated, message)) return;
            try { const data = await api("update", updated); saveSession(data.user); renderProfile(); }
            catch (error) { message.textContent = error.message; }
        });
    }

    renderProfile();
});
