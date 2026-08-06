document.addEventListener('DOMContentLoaded', function() {
    const revealItems = document.querySelectorAll('.reveal-on-scroll');
    const revealObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15
    });

    revealItems.forEach(function(item) {
        revealObserver.observe(item);
    });

    const updatePanelHeight = function(panel, isOpen) {
        if (!panel) {
            return;
        }

        panel.style.maxHeight = isOpen ? `${panel.scrollHeight}px` : '0px';
    };

    const refreshParentPanels = function(element) {
        let parent = element.parentElement ? element.parentElement.closest('.rate-content, .rate-nested-content') : null;

        while (parent) {
            if (parent.classList.contains('is-open')) {
                parent.style.maxHeight = `${parent.scrollHeight}px`;
            }

            parent = parent.parentElement ? parent.parentElement.closest('.rate-content, .rate-nested-content') : null;
        }
    };

    document.querySelectorAll('.rate-toggle, .rate-nested-toggle').forEach(function(button) {
        button.addEventListener('click', function() {
            const targetId = button.getAttribute('aria-controls');
            const target = document.getElementById(targetId);

            if (!target) {
                return;
            }

            const isOpen = button.classList.toggle('is-open');
            target.classList.toggle('is-open', isOpen);
            button.setAttribute('aria-expanded', String(isOpen));
            updatePanelHeight(target, isOpen);

            requestAnimationFrame(function() {
                refreshParentPanels(target);
            });
        });
    });
});
