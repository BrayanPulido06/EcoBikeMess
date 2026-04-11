document.addEventListener('DOMContentLoaded', function () {
    // Desactivar Service Worker/caché agresiva en secciones de mensajero (evita que el móvil cargue CSS/JS viejos)
    try {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(regs => {
                regs.forEach(r => r.unregister());
            }).catch(() => {});
        }
        if (window.caches && typeof window.caches.keys === 'function') {
            window.caches.keys().then(keys => {
                keys.forEach(k => window.caches.delete(k));
            }).catch(() => {});
        }
    } catch (_) {}

    const menuBtn = document.getElementById('menuBtn');
    const sideMenu = document.getElementById('sideMenu');
    const menuOverlay = document.getElementById('menuOverlay');

    if (menuBtn && sideMenu && menuOverlay) {
        menuBtn.addEventListener('click', function () {
            sideMenu.classList.add('active');
            menuOverlay.classList.add('active');
        });

        menuOverlay.addEventListener('click', function () {
            sideMenu.classList.remove('active');
            menuOverlay.classList.remove('active');
        });
    }

    const currentFile = window.location.pathname.split('/').pop();
    if (!currentFile || !sideMenu) {
        return;
    }

    sideMenu.querySelectorAll('a').forEach(function (link) {
        const href = link.getAttribute('href') || '';
        const linkFile = href.split('/').pop();
        const isCurrent = linkFile === currentFile;
        link.classList.toggle('active', isCurrent);
    });
});
