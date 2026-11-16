document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#product-form');
    const input = form?.querySelector('[name="product_images[]"]');
    const preview = document.querySelector('#selected-image-preview');
    const status = document.querySelector('#selected-image-status');
    if (!input || !preview || !status) return;
    let selected = [];
    let nextId = 0;
    const orderInput = document.querySelector('#product-image-order');
    let entries = Array.from(preview.querySelectorAll('[data-existing-image]')).map(card => ({
        key: 'existing:' + card.dataset.existingImage, card,
        checkbox: card.querySelector('[name="keep_images[]"]')
    }));
    const active = entry => !entry.checkbox || entry.checkbox.checked;
    function move(entry, offset, cover = false) {
        const included = entries.filter(active);
        const index = included.indexOf(entry);
        const target = cover ? 0 : index + offset;
        if (index < 0 || target < 0 || target >= included.length) return;
        included.splice(index, 1); included.splice(target, 0, entry);
        entries = [...included, ...entries.filter(item => !active(item))];
        sync();
        entry.card.querySelector('[data-move="' + (cover ? 'cover' : offset) + '"]')?.focus();
    }
    const keptCount = () => form.querySelectorAll('[name="keep_images[]"]:checked').length;
    const bytes = value => {
        const match = String(value).trim().match(/^(\d+(?:\.\d+)?)\s*([KMG])?$/i);
        return match ? Number(match[1]) * 1024 ** ({ K: 1, M: 2, G: 3 }[match[2]?.toUpperCase()] || 0) : Infinity;
    };
    const fileLimit = Math.min(5 * 1024 ** 2, bytes(input.dataset.fileLimit));
    const totalLimit = bytes(input.dataset.totalLimit);
    function sync() {
        const transfer = new DataTransfer();
        selected.forEach(entry => transfer.items.add(entry.file));
        input.files = transfer.files;
        input.required = keptCount() === 0;
        const tooMany = keptCount() + selected.length > 20;
        const tooLarge = selected.reduce((sum, entry) => sum + entry.file.size, 0) > totalLimit * 0.95;
        input.setCustomValidity(tooMany ? 'Cada produto pode ter até 20 fotos. Remova algumas imagens.' : tooLarge ? 'O conjunto de fotos excede o limite por envio. Envie menos imagens por vez.' : '');
        preview.replaceChildren();
        const included = entries.filter(active);
        entries.forEach(entry => {
            const card = entry.card;
            card.querySelector('[data-image-actions]')?.remove();
            const actions = document.createElement('div');
            actions.dataset.imageActions = '';
            actions.className = 'image-order-actions';
            const position = included.indexOf(entry);
            const caption = document.createElement('strong');
            caption.textContent = position < 0 ? 'Será removida' : position === 0 ? '1 · Capa do produto' : 'Foto ' + (position + 1);
            actions.append(caption);
            for (const [label, offset, cover] of [['← Anterior', -1, false], ['Próxima →', 1, false], ['Usar como capa', 0, true]]) {
                const button = document.createElement('button');
                button.type = 'button'; button.textContent = label;
                button.dataset.move = cover ? 'cover' : String(offset);
                button.disabled = position < 0 || (cover || offset < 0 ? position === 0 : position === included.length - 1);
                button.setAttribute('aria-label', label + ' · foto ' + (position + 1));
                button.addEventListener('click', () => move(entry, offset, cover));
                actions.append(button);
            }
            card.append(actions);
            preview.append(card);
        });
        if (orderInput) orderInput.value = JSON.stringify(included.map(entry => entry.checkbox ? entry.key : 'new:' + selected.indexOf(entry)));
        status.textContent = `${selected.length} nova(s) foto(s) selecionada(s). ${keptCount() + selected.length}/20 fotos no produto.`;
        if (tooMany || tooLarge) status.textContent += ' ' + input.validationMessage;
    }
    input.addEventListener('change', () => {
        const warnings = [];
        for (const file of input.files) {
            if (!['image/jpeg', 'image/png', 'image/webp', 'image/gif'].includes(file.type)) { warnings.push(file.name + ': formato não permitido.'); continue; }
            if (file.size > fileLimit) { warnings.push(file.name + ': arquivo acima do limite permitido.'); continue; }
            if (selected.some(item => item.file.name === file.name && item.file.size === file.size && item.file.lastModified === file.lastModified)) continue;
            if (selected.length + keptCount() >= 20) { warnings.push('Limite de 20 fotos por produto atingido.'); break; }
            const card = document.createElement('div'); card.className = 'product-image-option selected-image';
            const image = document.createElement('img'); image.alt = 'Prévia de ' + file.name;
            const url = URL.createObjectURL(file); image.src = url;
            const name = document.createElement('span'); name.className = 'preview-filename'; name.textContent = file.name;
            const remove = document.createElement('button'); remove.type = 'button'; remove.textContent = 'Remover foto';
            const entry = { key: 'upload:' + nextId++, card, file, url };
            remove.addEventListener('click', () => {
                selected = selected.filter(item => item !== entry); entries = entries.filter(item => item !== entry);
                URL.revokeObjectURL(url); sync();
            });
            card.append(image, name, remove); selected.push(entry); entries.push(entry);
        }
        sync();
        if (warnings.length) status.textContent += ' ' + warnings.join(' ');
    });
    form.querySelectorAll('[name="keep_images[]"]').forEach(checkbox => checkbox.addEventListener('change', sync));
    window.addEventListener('pagehide', () => selected.forEach(entry => URL.revokeObjectURL(entry.url)));
    window.addEventListener('pageshow', sync);
    sync();
});
