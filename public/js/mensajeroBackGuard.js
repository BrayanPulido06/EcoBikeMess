(() => {
    const GUARD_FLAG = '__ecobikeMensajeroBackGuard';
    const EXIT_HINT_MS = 2000;
    const ROOT_STATE_KEY = 'ecobike-mensajero-root';

    let exitArmed = false;
    let exitTimer = null;
    let initialized = false;

    const toast = (message, type = 'info', title = 'Navegación') => {
        const toastFn = window.EcoBikeUI?.toast;
        if (typeof toastFn === 'function') {
            toastFn(message, { type, title, duration: 2200 });
            return;
        }

        let box = document.getElementById('ecobike-back-guard-toast');
        if (!box) {
            box = document.createElement('div');
            box.id = 'ecobike-back-guard-toast';
            box.style.position = 'fixed';
            box.style.left = '50%';
            box.style.bottom = '18px';
            box.style.transform = 'translateX(-50%)';
            box.style.zIndex = '10060';
            box.style.background = 'rgba(15, 23, 42, 0.92)';
            box.style.color = '#fff';
            box.style.padding = '10px 14px';
            box.style.borderRadius = '999px';
            box.style.fontSize = '14px';
            box.style.boxShadow = '0 12px 28px rgba(0, 0, 0, 0.18)';
            box.style.maxWidth = 'min(90vw, 360px)';
            box.style.textAlign = 'center';
            document.body.appendChild(box);
        }

        box.textContent = title ? `${title}: ${message}` : message;
        box.style.opacity = '1';
        clearTimeout(box._hideTimer);
        box._hideTimer = setTimeout(() => {
            box.style.opacity = '0';
        }, 1800);
    };

    const getVisible = (selector) => Array.from(document.querySelectorAll(selector))
        .find((element) => {
            const style = window.getComputedStyle(element);
            return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
        }) || null;

    const closeElement = (element) => {
        if (!element) return false;

        if (element.classList.contains('vista-detalle') || element.classList.contains('vista-formulario')) {
            element.classList.add('oculto');
            return true;
        }

        if (element.classList.contains('modal') || element.classList.contains('detalle-modal')) {
            element.classList.remove('active');
            element.classList.add('oculto');
            element.style.display = 'none';
            element.setAttribute('aria-hidden', 'true');
            return true;
        }

        element.classList.add('oculto');
        element.style.display = 'none';
        return true;
    };

    const closeSidebar = () => {
        const sideMenu = document.getElementById('sideMenu');
        const menuOverlay = document.getElementById('menuOverlay');
        if (!sideMenu && !menuOverlay) return false;

        const wasOpen = sideMenu?.classList.contains('active') || menuOverlay?.classList.contains('active');
        sideMenu?.classList.remove('active');
        menuOverlay?.classList.remove('active');
        return wasOpen;
    };

    const closeOpenOverlay = () => {
        if (closeSidebar()) {
            return true;
        }

        const candidates = [
            '#whatsappModal',
            '#rotuloModal',
            '#detalleModal',
            '#modalConfirmacion',
            '#modalDecision',
            '#modalFotoOpciones',
            '#scanModal',
            '#manualModal',
            '#routeDetailModal',
            '.vista-formulario:not(.oculto)',
            '.vista-detalle:not(.oculto)',
            '.modal.active',
            '.detalle-modal',
            '.modal:not(.oculto)'
        ];

        for (const selector of candidates) {
            const element = getVisible(selector);
            if (element && closeElement(element)) {
                return true;
            }
        }

        return false;
    };

    const setRootState = () => {
        try {
            history.replaceState({ [ROOT_STATE_KEY]: true }, '', window.location.href);
            history.pushState({ [ROOT_STATE_KEY]: true }, '', window.location.href);
        } catch (_) {
            // Ignorar si el navegador bloquea el historial.
        }
    };

    const armExitHint = () => {
        exitArmed = true;
        clearTimeout(exitTimer);
        exitTimer = setTimeout(() => {
            exitArmed = false;
        }, EXIT_HINT_MS);
        toast('Pulsa otra vez para salir', 'info');
    };

    const onPopState = () => {
        if (closeOpenOverlay()) {
            setRootState();
            exitArmed = false;
            return;
        }

        if (!exitArmed) {
            armExitHint();
            setRootState();
            return;
        }

        exitArmed = false;
    };

    const init = () => {
        if (initialized || window[GUARD_FLAG]) return;
        window[GUARD_FLAG] = true;
        initialized = true;
        setRootState();
        window.addEventListener('popstate', onPopState);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }

    window.EcoBikeBackGuard = {
        refreshRootState: setRootState,
        closeOpenOverlay,
        armExitHint
    };
})();
