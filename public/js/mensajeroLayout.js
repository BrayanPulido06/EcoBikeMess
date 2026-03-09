document.addEventListener('DOMContentLoaded', function () {
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
