document.addEventListener('DOMContentLoaded', () => {
    const root = ['localhost', '127.0.0.1'].includes(location.hostname) ? '/SOLID/' : '/';
    document.querySelectorAll('.newsletter form, .collections-newsletter form').forEach(form => {
        const consent = document.createElement('label');
        consent.className = 'newsletter-consent';
        consent.innerHTML = '<input type="checkbox" required> Quero receber e-mails sobre roupas novas e descontos. Posso cancelar minha inscrição a qualquer momento.';
        form.append(consent);
        const message = document.createElement('p');
        message.className = 'newsletter-message';
        message.setAttribute('role', 'status');
        form.after(message);
        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (!form.reportValidity()) return;
            const button = form.querySelector('button');
            button.disabled = true;
            message.textContent = 'Enviando inscrição...';
            try {
                const response = await fetch(root + 'api/newsletter.php', {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email: form.querySelector('input[type="email"]').value.trim(), consent: consent.querySelector('input').checked })
                });
                const data = await response.json();
                if (!response.ok || !data.ok) throw new Error(data.message || 'Não foi possível concluir a inscrição.');
                message.textContent = data.message;
                form.reset();
            } catch (error) {
                message.textContent = error instanceof SyntaxError || error instanceof TypeError
                    ? 'Não foi possível conectar ao serviço. Tente novamente.' : error.message;
            } finally { button.disabled = false; }
        });
    });
});
