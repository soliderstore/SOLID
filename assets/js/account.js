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
            throw new Error("O servidor retornou uma resposta inválida. Verifique os logs do PHP e a estrutura do banco de dados.");
        }
        if (!response.ok || !data.ok) throw new Error(data.message || "Não foi possível concluir esta ação.");
        return data;
    }

    function rememberNewsletter(data) {
        if (data.newsletterMessage) sessionStorage.setItem("solid-newsletter-message", data.newsletterMessage);
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

    const destination = new URLSearchParams(window.location.search).get("next") === "checkout" ? "checkout.php" : "profile.php";
    const googleParams = new URLSearchParams(window.location.search);
    document.querySelectorAll('[data-google-login]').forEach(link => {
        if (destination === 'checkout.php') link.href += '?next=checkout';
    });
    const googleErrors = {
        configuration: 'O login com Google ainda não foi configurado. Por enquanto, entre com e-mail e senha.',
        expired: 'A tentativa de login expirou. Clique novamente em Continuar com o Google.',
        cancelled: 'Login com Google cancelado. Você pode tentar novamente.',
        existing: 'Já existe uma conta com este e-mail. Entre com sua senha.',
        failed: 'Não foi possível entrar com o Google. Tente novamente mais tarde.'
    };
    if (googleParams.has('google_error')) {
        const message = document.querySelector('#account-message');
        if (message) message.textContent = googleErrors[googleParams.get('google_error')] || googleErrors.failed;
    }
    if (googleParams.get('google_login') === '1') {
        api('session').then(data => {
            saveSession(data.user);
            window.location.replace(destination);
        }).catch(error => {
            const message = document.querySelector('#account-message');
            if (message) message.textContent = error.message;
        });
    }
    if (destination === "checkout.php") {
        document.querySelectorAll('.account-alternative a').forEach(link => {
            link.href = link.getAttribute("href") + "?next=checkout";
        });
    }
    function setupMasks(root = document) {
        root.querySelectorAll("[data-cpf]").forEach(input => {
            const mask = () => { input.value = input.value.replace(/\D/g, "").slice(0, 11)
                .replace(/(\d{3})(\d)/, "$1.$2")
                .replace(/(\d{3})(\d)/, "$1.$2")
                .replace(/(\d{3})(\d{1,2})$/, "$1-$2"); };
            input.addEventListener("input", mask);
            mask();
        });
        root.querySelectorAll("[data-phone]").forEach(input => input.addEventListener("input", () => { input.value = formatPhone(input.value); }));
        root.querySelectorAll("[data-zip]").forEach(input => input.addEventListener("input", () => { input.value = input.value.replace(/\D/g, "").slice(0, 8).replace(/(\d{5})(\d)/, "$1-$2"); }));
    }

    function readImage(input) {
        const file = input.files?.[0];
        if (!file) return Promise.resolve(null);
        if (!file.type.startsWith("image/")) return Promise.reject(new Error("Escolha um arquivo de imagem."));
        return new Promise((resolve, reject) => {
            const url = URL.createObjectURL(file);
            const image = new Image();
            image.onload = () => {
                try {
                    const scale = Math.min(1, 1024 / Math.max(image.naturalWidth, image.naturalHeight));
                    const canvas = document.createElement('canvas');
                    canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
                    canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
                    const context = canvas.getContext('2d');
                    context.fillStyle = '#fff';
                    context.fillRect(0, 0, canvas.width, canvas.height);
                    context.drawImage(image, 0, 0, canvas.width, canvas.height);
                    resolve(canvas.toDataURL('image/jpeg', .85));
                } catch { reject(new Error('Não foi possível preparar esta foto. Escolha outra imagem.')); }
                finally { URL.revokeObjectURL(url); }
            };
            image.onerror = () => {
                URL.revokeObjectURL(url);
                reject(new Error('Não foi possível abrir esta imagem. Tente uma foto JPG, PNG ou WebP.'));
            };
            image.src = url;
        });
    }

    function photoPicker(account) {
        const photo = account.profileImage
            ? `<img src="${escapeHtml(account.profileImage)}" alt="Foto de perfil atual">`
            : escapeHtml(account.name.charAt(0).toUpperCase());
        return `<div class="profile-photo-picker"><div class="profile-photo-controls"><button type="button" class="profile-avatar profile-photo-button" aria-label="Ver foto de perfil ampliada" title="Ver foto">${photo}</button><button type="button" class="profile-photo-camera" aria-label="Alterar foto de perfil" title="Alterar foto"><i class="fa-solid fa-camera" aria-hidden="true"></i></button></div><input id="profile-image" type="file" accept="image/*" hidden><p class="profile-photo-status" role="status"></p></div>`;
    }

    function showProfilePhoto(source) {
        const dialog = document.createElement('dialog');
        dialog.className = 'profile-photo-viewer';
        dialog.setAttribute('aria-label', 'Foto de perfil ampliada');
        dialog.innerHTML = '<button type="button" class="profile-photo-close" aria-label="Fechar foto" autofocus>×</button><img alt="Foto de perfil completa">';
        dialog.querySelector('img').src = source;
        const previousOverflow = document.body.style.overflow;
        dialog.addEventListener('close', () => {
            document.body.style.overflow = previousOverflow;
            dialog.remove();
        }, { once: true });
        dialog.querySelector('button').addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        document.body.append(dialog);
        document.body.style.overflow = 'hidden';
        dialog.showModal();
    }

    function setupPhotoPicker(account, autoSave = false) {
        const button = content.querySelector('.profile-photo-button');
        const input = content.querySelector('#profile-image');
        const camera = content.querySelector('.profile-photo-camera');
        const status = content.querySelector('.profile-photo-status');
        let selectedPhoto = null;
        let pending = Promise.resolve();
        camera.addEventListener('click', () => input.click());
        button.addEventListener('click', () => {
            const photo = button.querySelector('img');
            if (photo) showProfilePhoto(photo.src);
            else input.click();
        });
        input.addEventListener('change', () => {
            if (!input.files?.length) return;
            button.disabled = true;
            camera.disabled = true;
            status.textContent = 'Preparando foto…';
            pending = (async () => {
                try {
                    const photo = await readImage(input);
                    if (autoSave) {
                        const data = await api('photo', { profileImage: photo });
                        saveSession(data.user);
                        account.profileImage = data.user.profileImage;
                    }
                    selectedPhoto = photo;
                    let preview = button.querySelector('img');
                    if (!preview) {
                        button.firstChild?.nodeType === Node.TEXT_NODE && button.firstChild.remove();
                        preview = document.createElement('img');
                        preview.alt = 'Foto de perfil atual';
                        button.prepend(preview);
                    }
                    preview.src = photo;
                    status.textContent = autoSave ? 'Foto atualizada.' : 'Foto selecionada. Salve as alterações para confirmar.';
                } catch (error) { status.textContent = error.message; }
                finally { button.disabled = false; camera.disabled = false; input.value = ''; }
            })();
        });
        return async () => { await pending; return selectedPhoto; };
    }

    function fields(prefix, account = {}) {
        return `<div class="account-form-grid">
            <label>Nome completo<input id="${prefix}-name" type="text" required value="${escapeHtml(account.name)}"></label>
            <label>WhatsApp<input id="${prefix}-phone" data-phone type="tel" required inputmode="numeric" maxlength="15" value="${escapeHtml(account.phone)}" placeholder="(00) 00000-0000"></label>
            <label class="account-full">E-mail<input id="${prefix}-email" type="email" required value="${escapeHtml(account.email)}" placeholder="nome@exemplo.com"></label>
            <label>CPF <small>(opcional)</small><input id="${prefix}-cpf" data-cpf inputmode="numeric" maxlength="14" type="text" value="${escapeHtml(account.cpf)}" placeholder="000.000.000-00"></label>
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
        const residenceNumber = registerForm.querySelector('#register-number');
        const digitsOnly = () => {
            residenceNumber.value = residenceNumber.value.replace(/[^0-9]/g, '');
        };
        residenceNumber.addEventListener('input', digitsOnly);
        residenceNumber.addEventListener('change', digitsOnly);
        const fieldValidations = [];
        ['cpf', 'email'].forEach(field => {
            const input = registerForm.querySelector(`#register-${field}`);
            const status = document.createElement('small');
            status.className = 'registration-validation';
            status.id = `${input.id}-validation`;
            status.setAttribute('aria-live', 'polite');
            input.insertAdjacentElement('afterend', status);
            input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), status.id].filter(Boolean).join(' '));
            let timer;
            let revision = 0;
            let controller;
            async function validate() {
                clearTimeout(timer);
                controller?.abort();
                controller = new AbortController();
                const current = ++revision;
                input.setCustomValidity('');
                status.textContent = 'Conferindo…';
                status.dataset.valid = '';
                try {
                    const response = await fetch('../api/validate-registration.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/json' },
                        credentials: 'same-origin', signal: controller.signal,
                        body: JSON.stringify({ field, value: input.value.trim() })
                    });
                    const result = await response.json();
                    if (current !== revision) return false;
                    if (!response.ok || !result.ok) throw new Error('validation');
                    input.setCustomValidity(result.valid ? '' : result.message);
                    input.setAttribute('aria-invalid', String(!result.valid));
                    status.dataset.valid = String(result.valid);
                    status.textContent = result.message;
                    return result.valid;
                } catch (error) {
                    if (current !== revision || error.name === 'AbortError') return false;
                    status.textContent = 'A conferência será feita ao enviar o cadastro.';
                    // A API de cadastro repete as regras, mesmo se esta consulta falhar.
                    return true;
                }
            }
            input.addEventListener('input', () => {
                revision++;
                controller?.abort();
                input.setCustomValidity('');
                input.removeAttribute('aria-invalid');
                status.textContent = '';
                clearTimeout(timer);
                timer = setTimeout(validate, 500);
            });
            input.addEventListener('blur', validate);
            fieldValidations.push(validate);
        });
        registerForm.querySelectorAll('input[type="text"], input[type="email"], input[type="password"], input[type="tel"]').forEach(input => {
            const limit = input.maxLength > 0 ? Math.min(input.maxLength, 50) : 50;
            input.maxLength = limit;
            const counter = document.createElement('small');
            counter.id = `${input.id}-limit`;
            counter.className = 'account-character-count';
            input.setAttribute('aria-describedby', [input.getAttribute('aria-describedby'), counter.id].filter(Boolean).join(' '));
            input.insertAdjacentElement('afterend', counter);
            const enforceLimit = () => {
                if (input.value.length > limit) input.value = input.value.slice(0, limit);
                counter.textContent = `${input.value.length}/${limit} caracteres`;
            };
            input.addEventListener('input', enforceLimit);
            input.addEventListener('change', enforceLimit);
            enforceLimit();
        });
        registerForm.addEventListener("submit", async event => {
            event.preventDefault();
            const message = document.querySelector("#account-message");
            const results = await Promise.all(fieldValidations.map(validate => validate()));
            if (results.includes(false) || !registerForm.reportValidity()) {
                registerForm.reportValidity();
                return;
            }
            const account = collect("register");
            account.password = document.querySelector("#register-password").value;
            digitsOnly();
            if (!registerForm.reportValidity()) return;
            try {
                const data = await api("register", account);
                rememberNewsletter(data);
                saveSession(data.user);
                window.location.href = destination;
            } catch (error) { message.textContent = error.message; }
        });
    }

    const loginForm = document.querySelector("#login-form");
    if (loginForm) loginForm.addEventListener("submit", event => {
        event.preventDefault();
        const message = document.querySelector("#account-message");
        api("login", { email: document.querySelector("#login-email").value.trim(), password: document.querySelector("#login-password").value })
            .then(data => { saveSession(data.user); window.location.href = destination; })
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
        content.innerHTML = `<div class="account-card profile-card">${photoPicker(account)}<span>MINHA CONTA</span><h2>Olá, ${escapeHtml(account.name.split(" ")[0])}!</h2><p>${escapeHtml(account.email)}</p><div class="profile-data"><div><small>WhatsApp</small><strong>${escapeHtml(account.phone)}</strong></div><div><small>Endereço</small><strong>${escapeHtml(account.address)}, ${escapeHtml(account.number)}${account.complement ? ` - ${escapeHtml(account.complement)}` : ""}<br>${escapeHtml(account.neighborhood)} · ${escapeHtml(account.city)}<br>CEP: ${escapeHtml(account.zip)}</strong></div><div><small>Novidades por e-mail</small><strong>${account.newsletter ? "Ativadas" : "Não ativadas"}</strong></div></div><div class="profile-links"><a href="wishlist.php"><i class="fa-regular fa-heart"></i> Meus favoritos</a><a href="cart.php"><i class="fa-solid fa-bag-shopping"></i> Meu carrinho</a></div><button id="edit-profile" class="profile-edit" type="button">Editar meus dados</button><button id="logout-button" type="button">Sair da conta</button></div>`;
        setupPhotoPicker(account, true);
        const newsletterMessage = sessionStorage.getItem("solid-newsletter-message");
        if (newsletterMessage) {
            const notice = document.createElement("p");
            notice.className = "account-message";
            notice.setAttribute("role", "status");
            notice.textContent = newsletterMessage;
            content.prepend(notice);
            sessionStorage.removeItem("solid-newsletter-message");
        }
        document.querySelector("#edit-profile").addEventListener("click", () => renderEdit(account));
        document.querySelector("#logout-button").addEventListener("click", async () => {
            try { await api("logout"); } finally { localStorage.removeItem(sessionKey); localStorage.removeItem(accountKey); window.location.href = "login.php"; }
        });
    }

    function renderEdit(account) {
        content.innerHTML = `<section class="account-card edit-profile-card"><h1>Editar dados</h1><p>Mantenha suas informações e endereço atualizados.</p><form id="profile-form" class="account-form">${photoPicker(account)}${fields("profile", account)}<label class="newsletter-choice account-full"><input id="profile-newsletter" type="checkbox" ${account.newsletter ? "checked" : ""}> Quero receber lançamentos, promoções e novidades por e-mail.</label><p id="account-message" class="account-message" role="alert"></p><button class="btn-primary" type="submit">Salvar alterações</button><button id="cancel-edit" class="account-cancel" type="button">Cancelar</button></form></section>`;
        const form = document.querySelector("#profile-form"); setupMasks(form);
        const selectedPhoto = setupPhotoPicker(account);
        document.querySelector("#cancel-edit").addEventListener("click", renderProfile);
        form.addEventListener("submit", async event => {
            event.preventDefault();
            const message = document.querySelector("#account-message");
            const updated = collect("profile", account);
            const photo = await selectedPhoto();
            if (photo) updated.profileImage = photo;
            try { const data = await api("update", updated); rememberNewsletter(data); saveSession(data.user); renderProfile(); }
            catch (error) { message.textContent = error.message; }
        });
    }

    renderProfile();
});
