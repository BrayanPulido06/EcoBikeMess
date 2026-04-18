document.addEventListener('DOMContentLoaded', function () {
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

    if (!sideMenu) {
        return;
    }

    const currentPath = window.location.pathname.replace(/\/+$/, '');
    const currentFile = currentPath.split('/').pop();

    sideMenu.querySelectorAll('a').forEach(function (link) {
        const href = link.getAttribute('href') || '';
        const normalizedHref = href.replace(/\/+$/, '');
        const linkFile = normalizedHref.split('/').pop();
        const isCurrent = normalizedHref === currentPath || linkFile === currentFile;
        link.classList.toggle('active', isCurrent);

        if (isCurrent) {
            const group = link.closest('.menu-group');
            if (group) {
                group.classList.add('open');
            }
        }
    });

    sideMenu.querySelectorAll('.menu-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const group = toggle.closest('.menu-group');
            if (!group) return;
            group.classList.toggle('open');
        });
    });
});
