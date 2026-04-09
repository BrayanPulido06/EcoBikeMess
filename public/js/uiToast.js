(() => {
    const STYLE_ID = 'ecobike-ui-toast-style';
    const STACK_ID = 'ecobike-ui-toast-stack';

    const ensureStyles = () => {
        if (document.getElementById(STYLE_ID)) return;
        const style = document.createElement('style');
        style.id = STYLE_ID;
        style.textContent = `
            #${STACK_ID} {
                position: fixed;
                top: 14px;
                right: 14px;
                z-index: 10050;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: min(92vw, 380px);
                pointer-events: none;
            }
            .ecobike-toast {
                pointer-events: auto;
                display: grid;
                grid-template-columns: 10px 1fr auto;
                gap: 10px;
                align-items: start;
                padding: 12px 12px;
                border-radius: 12px;
                background: #ffffff;
                box-shadow: 0 16px 40px rgba(0,0,0,0.18);
                border: 1px solid rgba(0,0,0,0.06);
                overflow: hidden;
                animation: ecobike_toast_in 140ms ease-out;
            }
            @keyframes ecobike_toast_in {
                from { transform: translateY(-6px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
            .ecobike-toast__bar {
                width: 10px;
                height: 100%;
                border-radius: 10px;
                background: #64748b;
            }
            .ecobike-toast[data-type="success"] .ecobike-toast__bar { background: #16a34a; }
            .ecobike-toast[data-type="error"] .ecobike-toast__bar { background: #dc2626; }
            .ecobike-toast[data-type="warning"] .ecobike-toast__bar { background: #f59e0b; }
            .ecobike-toast[data-type="info"] .ecobike-toast__bar { background: #2563eb; }

            .ecobike-toast__title {
                margin: 0 0 2px;
                font-size: 14px;
                font-weight: 800;
                color: #0f172a;
            }
            .ecobike-toast__msg {
                margin: 0;
                font-size: 13.5px;
                color: #334155;
                line-height: 1.25rem;
                word-break: break-word;
            }
            .ecobike-toast__close {
                border: none;
                background: transparent;
                cursor: pointer;
                color: #475569;
                font-size: 18px;
                line-height: 1;
                padding: 2px 6px;
                border-radius: 8px;
            }
            .ecobike-toast__close:focus { outline: 2px solid #667eea; outline-offset: 2px; }
        `;
        document.head.appendChild(style);
    };

    const ensureStack = () => {
        let stack = document.getElementById(STACK_ID);
        if (stack) return stack;
        ensureStyles();
        stack = document.createElement('div');
        stack.id = STACK_ID;
        stack.setAttribute('aria-live', 'polite');
        stack.setAttribute('aria-relevant', 'additions');
        document.body.appendChild(stack);
        return stack;
    };

    const toast = (message, opts = {}) => {
        const stack = ensureStack();
        const type = opts.type || 'info';
        const title = opts.title || '';
        const duration = typeof opts.duration === 'number' ? opts.duration : 2600;

        const el = document.createElement('div');
        el.className = 'ecobike-toast';
        el.dataset.type = type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');

        const safeTitle = String(title || '').trim();
        const safeMsg = String(message || '').trim();
        el.innerHTML = `
            <div class="ecobike-toast__bar"></div>
            <div>
                ${safeTitle ? `<p class="ecobike-toast__title"></p>` : ''}
                <p class="ecobike-toast__msg"></p>
            </div>
            <button type="button" class="ecobike-toast__close" aria-label="Cerrar">&times;</button>
        `;

        if (safeTitle) el.querySelector('.ecobike-toast__title').textContent = safeTitle;
        el.querySelector('.ecobike-toast__msg').textContent = safeMsg;

        const remove = () => {
            if (!el.isConnected) return;
            el.style.opacity = '0';
            el.style.transform = 'translateY(-6px)';
            setTimeout(() => el.remove(), 140);
        };

        el.querySelector('.ecobike-toast__close').addEventListener('click', remove);
        stack.appendChild(el);

        if (duration > 0) setTimeout(remove, duration);
        return { remove };
    };

    window.EcoBikeUI = window.EcoBikeUI || {};
    window.EcoBikeUI.toast = toast;
})();

