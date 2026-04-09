(() => {
    const LIGHTBOX_ID = 'ecobike-image-lightbox';
    const STYLE_ID = 'ecobike-image-lightbox-style';

    const ensureStyles = () => {
        if (document.getElementById(STYLE_ID)) return;
        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            #${LIGHTBOX_ID} {
                position: fixed;
                inset: 0;
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 10000;
            }
            #${LIGHTBOX_ID}.is-open { display: flex; }
            #${LIGHTBOX_ID} .ecobike-ilb__backdrop {
                position: absolute;
                inset: 0;
                background: rgba(0, 0, 0, 0.75);
            }
            #${LIGHTBOX_ID} .ecobike-ilb__dialog {
                position: relative;
                z-index: 1;
                max-width: min(92vw, 1200px);
                max-height: 92vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
                padding: 12px;
            }
            #${LIGHTBOX_ID} .ecobike-ilb__img {
                max-width: 92vw;
                max-height: 86vh;
                width: auto;
                height: auto;
                border-radius: 10px;
                background: #fff;
                box-shadow: 0 20px 60px rgba(0,0,0,0.45);
            }
            #${LIGHTBOX_ID} .ecobike-ilb__caption {
                color: #f1f1f1;
                font-size: 14px;
                text-align: center;
                max-width: 92vw;
                word-break: break-word;
            }
            #${LIGHTBOX_ID} .ecobike-ilb__close {
                position: absolute;
                top: 8px;
                right: 10px;
                border: none;
                width: 38px;
                height: 38px;
                border-radius: 10px;
                background: rgba(255,255,255,0.92);
                color: #111;
                font-size: 22px;
                line-height: 1;
                cursor: pointer;
                box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            }
            #${LIGHTBOX_ID} .ecobike-ilb__close:focus { outline: 2px solid #667eea; outline-offset: 2px; }
        `;
        document.head.appendChild(style);
    };

    const ensureLightbox = () => {
        let root = document.getElementById(LIGHTBOX_ID);
        if (root) return root;

        ensureStyles();

        root = document.createElement('div');
        root.id = LIGHTBOX_ID;
        root.setAttribute('aria-hidden', 'true');
        root.innerHTML = `
            <div class="ecobike-ilb__backdrop" data-ecobike-ilb="close"></div>
            <div class="ecobike-ilb__dialog" role="dialog" aria-modal="true">
                <button type="button" class="ecobike-ilb__close" data-ecobike-ilb="close" aria-label="Cerrar">&times;</button>
                <img class="ecobike-ilb__img" alt="">
                <div class="ecobike-ilb__caption" hidden></div>
            </div>
        `;
        document.body.appendChild(root);
        return root;
    };

    const state = {
        lastActiveElement: null,
        bodyOverflow: null
    };

    const open = (src, altText) => {
        if (!src) return;
        const root = ensureLightbox();
        const img = root.querySelector('.ecobike-ilb__img');
        const caption = root.querySelector('.ecobike-ilb__caption');
        const closeBtn = root.querySelector('.ecobike-ilb__close');

        state.lastActiveElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        state.bodyOverflow = document.body.style.overflow;
        document.body.style.overflow = 'hidden';

        img.src = src;
        img.alt = altText || '';
        const hasCaption = Boolean(altText && String(altText).trim().length > 0);
        caption.hidden = !hasCaption;
        caption.textContent = hasCaption ? String(altText).trim() : '';

        root.classList.add('is-open');
        root.setAttribute('aria-hidden', 'false');
        closeBtn?.focus?.();
    };

    const close = () => {
        const root = document.getElementById(LIGHTBOX_ID);
        if (!root) return;
        root.classList.remove('is-open');
        root.setAttribute('aria-hidden', 'true');

        const img = root.querySelector('.ecobike-ilb__img');
        if (img) img.src = '';

        document.body.style.overflow = state.bodyOverflow ?? '';
        const toFocus = state.lastActiveElement;
        state.lastActiveElement = null;
        state.bodyOverflow = null;
        toFocus?.focus?.();
    };

    const init = () => {
        if (window.EcoBikeImageLightbox?.__inited) return;
        window.EcoBikeImageLightbox = window.EcoBikeImageLightbox || {};
        window.EcoBikeImageLightbox.__inited = true;
        window.EcoBikeImageLightbox.open = open;
        window.EcoBikeImageLightbox.close = close;

        document.addEventListener('click', (e) => {
            const target = e.target;
            if (!(target instanceof Element)) return;

            const closer = target.closest('[data-ecobike-ilb="close"]');
            if (closer) {
                e.preventDefault();
                close();
                return;
            }

            const trigger = target.closest('[data-lightbox-src], a.js-image-lightbox');
            if (!trigger) return;

            const src =
                trigger.getAttribute('data-lightbox-src') ||
                (trigger instanceof HTMLAnchorElement ? trigger.getAttribute('href') : null);

            if (!src) return;
            e.preventDefault();

            const alt = trigger.getAttribute('data-lightbox-alt') || trigger.getAttribute('aria-label') || '';
            open(src, alt);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            const root = document.getElementById(LIGHTBOX_ID);
            if (!root || !root.classList.contains('is-open')) return;
            e.preventDefault();
            close();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

