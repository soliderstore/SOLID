(() => {
    if (!product || !container) return;
    const gallery = container.querySelector('.product-gallery');
    gallery.innerHTML = `
        <div class="product-carousel" role="region" aria-label="Fotos do produto" tabindex="0">
            <div class="product-slides"></div>
            <button type="button" class="gallery-arrow gallery-prev" aria-label="Foto anterior">‹</button>
            <button type="button" class="gallery-arrow gallery-next" aria-label="Próxima foto">›</button>
            <span class="gallery-counter" aria-live="polite"></span>
        </div>
        <div class="gallery-thumbnails"></div>
        <p class="gallery-hint">Deslize para os lados para ver mais fotos</p>
        <p class="gallery-unavailable" role="status" tabindex="-1">Fotos indisponíveis.</p>
    `;
    const carousel = gallery.querySelector('.product-carousel');
    const track = gallery.querySelector('.product-slides');
    const thumbnails = gallery.querySelector('.gallery-thumbnails');
    const previousButton = gallery.querySelector('.gallery-prev');
    const nextButton = gallery.querySelector('.gallery-next');
    const counter = gallery.querySelector('.gallery-counter');
    const controls = [previousButton, nextButton, counter, thumbnails, gallery.querySelector('.gallery-hint')];
    const unavailable = gallery.querySelector('.gallery-unavailable');
    const configured = [...new Set(product.images)];
    const alternatives = [...new Set(product.fallbackImages ?? [])].filter(source => !configured.includes(source));
    const placeholder = new URL('../images/logo/logo.png', document.currentScript.src).pathname;
    const placeholderSources = [...configured, ...alternatives].includes(placeholder) ? [] : [placeholder];
    const backups = [alternatives, placeholderSources].filter(sources => sources.length);
    const photos = [];
    let current = 0;
    let drag;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function slideIndex(index) {
        return Math.max(0, Math.min(photos.length - 1, index));
    }

    function updateGallery() {
        current = slideIndex(Math.round(track.scrollLeft / Math.max(track.clientWidth, 1)));
        photos.forEach(({ thumb }, i) => thumb.setAttribute('aria-pressed', String(i === current)));
        counter.textContent = `${current + 1} / ${photos.length}`;
        previousButton.disabled = current === 0;
        nextButton.disabled = current >= photos.length - 1;
    }

    function syncPhotos() {
        photos.forEach(({ source, slide, thumb }, index) => {
            slide.alt = source === placeholder ? 'Foto indisponível' : `${product.name} — foto ${index + 1} de ${photos.length}`;
            thumb.setAttribute('aria-label', `Ver foto ${index + 1}`);
        });
        controls.forEach(control => { control.hidden = photos.length < 2; });
        carousel.hidden = photos.length === 0;
        unavailable.hidden = photos.length > 0;
    }

    function go(index, behavior = reduced ? 'instant' : 'smooth') {
        track.scrollTo({ left: slideIndex(index) * track.clientWidth, behavior });
    }

    function removePhoto(photo) {
        const index = photos.indexOf(photo);
        if (index < 0) return;

        updateGallery();
        const selected = index < current ? current - 1 : current;
        const activeElement = document.activeElement;
        drag = null;
        track.classList.remove('is-dragging');
        photos.splice(index, 1);
        photo.slide.remove();
        photo.thumb.remove();
        if (!photos.length) addPhotos(backups.shift() ?? []);

        syncPhotos();
        go(selected, 'instant');
        updateGallery();
        const focusedControlHidden = photos.length < 2 && [previousButton, nextButton].includes(activeElement);
        const focusedGalleryHidden = !photos.length && activeElement === carousel;
        if (activeElement === photo.thumb || focusedControlHidden || focusedGalleryHidden) {
            const target = photos.length > 1 ? photos[current].thumb : (photos.length ? carousel : unavailable);
            target.focus({ preventScroll: true });
        }
    }

    function addPhotos(sources) {
        sources.forEach((source, index) => {
            const slide = document.createElement('img');
            slide.draggable = false;
            if (index === 0) slide.fetchPriority = 'high';
            else slide.loading = 'lazy';

            const thumb = document.createElement('button');
            thumb.type = 'button';
            const preview = document.createElement('img');
            preview.alt = '';
            thumb.append(preview);

            const photo = { source, slide, thumb };
            photos.push(photo);
            slide.addEventListener('error', () => removePhoto(photo), { once: true });
            preview.addEventListener('error', () => removePhoto(photo), { once: true });
            thumb.addEventListener('click', () => go(photos.indexOf(photo)));
            track.append(slide);
            thumbnails.append(thumb);
            slide.src = source;
            preview.src = source;
        });
    }

    previousButton.addEventListener('click', () => go(current - 1));
    nextButton.addEventListener('click', () => go(current + 1));
    track.addEventListener('scroll', updateGallery, { passive: true });
    carousel.addEventListener('keydown', event => {
        if (['ArrowLeft', 'ArrowRight'].includes(event.key)) { event.preventDefault(); go(current + (event.key === 'ArrowRight' ? 1 : -1)); }
    });
    track.addEventListener('pointerdown', event => {
        if (photos.length < 2 || event.pointerType !== 'mouse' || event.button !== 0) return;
        drag = { x: event.clientX, left: track.scrollLeft };
        track.setPointerCapture(event.pointerId);
        track.classList.add('is-dragging');
    });
    track.addEventListener('pointermove', event => { if (drag) track.scrollLeft = drag.left + drag.x - event.clientX; });
    function finishDrag() { if (!drag) return; drag = null; track.classList.remove('is-dragging'); updateGallery(); go(current); }
    track.addEventListener('pointerup', finishDrag);
    track.addEventListener('pointercancel', finishDrag);
    new ResizeObserver(() => { go(current, 'instant'); updateGallery(); }).observe(track);
    addPhotos(configured.length ? configured : (backups.shift() ?? []));
    syncPhotos();
    updateGallery();

    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
    const description = container.querySelector('.product-info > p');
    const tabs = document.createElement('section');
    tabs.className = 'product-feedback';
    tabs.innerHTML = `<div class="product-tabs" role="tablist" aria-label="Informações do produto"><button id="description-tab" type="button" role="tab" aria-selected="false" aria-controls="description-panel">Descrição</button><button id="reviews-tab" type="button" role="tab" aria-selected="true" aria-controls="reviews-panel">Avaliações <span id="reviews-count"></span></button></div><div id="description-panel" role="tabpanel" aria-labelledby="description-tab" tabindex="0" hidden><p>${escape(product.description)}</p></div><div id="reviews-panel" role="tabpanel" aria-labelledby="reviews-tab" tabindex="0"><div id="reviews-content" aria-live="polite">Carregando avaliações…</div></div>`;
    container.append(tabs);
    if (description) description.hidden = true;
    const tabButtons = [...tabs.querySelectorAll('[role="tab"]')];
    function selectTab(index) {
        tabButtons.forEach((button, i) => {
            button.setAttribute('aria-selected', String(i === index)); button.tabIndex = i === index ? 0 : -1;
            document.getElementById(button.getAttribute('aria-controls')).hidden = i !== index;
        });
    }
    tabButtons.forEach((button, i) => {
        button.addEventListener('click', () => selectTab(i));
        button.addEventListener('keydown', event => {
            if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) {
                event.preventDefault(); const next = event.key === 'Home' ? 0 : event.key === 'End' ? 1 : 1 - i; selectTab(next); tabButtons[next].focus();
            }
        });
    });
    selectTab(1);
    const ratingLink = document.createElement('button');
    ratingLink.className = 'product-rating-link'; ratingLink.type = 'button'; ratingLink.textContent = 'Ver avaliações';
    container.querySelector('.product-info h1').after(ratingLink);
    ratingLink.addEventListener('click', () => { selectTab(1); tabs.scrollIntoView({ behavior: reduced ? 'instant' : 'smooth', block: 'start' }); tabButtons[1].focus({ preventScroll: true }); });
    const stars = rating => '★'.repeat(Math.round(rating)) + '☆'.repeat(5 - Math.round(rating));
    async function loadReviews(payload) {
        const response = await fetch(`../api/reviews.php${payload ? '' : '?product_id=' + product.id}`, payload ? { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ ...payload, product_id: product.id }) } : { credentials: 'same-origin' });
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error(data.message || 'Não foi possível carregar as avaliações.');
        return data;
    }
    function renderReviews(data) {
        document.getElementById('reviews-count').textContent = `(${data.total})`;
        ratingLink.textContent = data.total ? `${stars(data.average)} ${data.average.toLocaleString('pt-BR')} · ${data.total} avaliações` : '☆☆☆☆☆ Ainda sem avaliações';
        document.getElementById('reviews-content').innerHTML = `<div class="reviews-summary"><strong>${data.average === null ? '—' : data.average.toLocaleString('pt-BR')}</strong><div><span class="review-stars">${stars(data.average || 0)}</span><p>${data.total} ${data.total === 1 ? 'avaliação de comprador' : 'avaliações de compradores'}</p></div></div>${data.eligible ? `<form id="review-form"><h3>${data.ownReview ? 'Editar minha avaliação' : 'Conte como foi sua experiência'}</h3><fieldset class="review-rating"><legend>Sua nota</legend>${[1,2,3,4,5].map(n => `<label><input type="radio" name="rating" value="${n}" required ${Number(data.ownReview?.rating) === n ? 'checked' : ''}><span>${n} ★</span></label>`).join('')}</fieldset><label for="review-comment">Seu comentário</label><textarea id="review-comment" minlength="3" maxlength="1000" required rows="4" placeholder="O que achou da peça, do tamanho e do acabamento?">${escape(data.ownReview?.comment || '')}</textarea><p id="review-message" role="status"></p><button type="submit" class="btn-primary">${data.ownReview ? 'Salvar avaliação' : 'Publicar avaliação'}</button></form>` : `<p class="review-eligibility">${data.loggedIn ? 'Para avaliar, conclua a compra deste produto e aguarde a liberação da sua conta pelo administrador.' : '<a href="login.php">Entre na sua conta</a> para avaliar um produto comprado após a liberação da loja.'}</p>`}<div class="review-list">${data.reviews.length ? data.reviews.map(review => `<article class="review-card"><div><strong>${escape(review.name)}</strong><span class="review-verified">Compra confirmada</span></div><span class="review-stars" aria-label="${review.rating} de 5 estrelas">${stars(review.rating)}</span><time datetime="${review.date}">${review.date.split('-').reverse().join('/')}</time><p>${escape(review.comment)}</p></article>`).join('') : '<p>Nenhuma avaliação ainda. As experiências dos compradores aparecerão aqui.</p>'}</div>`;
        document.getElementById('review-form')?.addEventListener('submit', async event => {
            event.preventDefault(); const form = event.currentTarget; const button = form.querySelector('button'); button.disabled = true;
            try {
                const updated = await loadReviews({ rating: Number(new FormData(form).get('rating')), comment: document.getElementById('review-comment').value.trim(), csrf: data.csrf });
                renderReviews(updated); document.getElementById('review-message').textContent = 'Avaliação salva. Obrigado por compartilhar!';
            } catch (error) { document.getElementById('review-message').textContent = error.message; button.disabled = false; }
        });
    }
    loadReviews().then(renderReviews).catch(error => { document.getElementById('reviews-content').textContent = error.message; });
})();
