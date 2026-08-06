document.addEventListener('DOMContentLoaded', function() {
    const revealItems = document.querySelectorAll('.reveal-on-scroll');
    const counters = document.querySelectorAll('[data-counter]');

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

    const animateCounter = function(counter) {
        const target = Number(counter.getAttribute('data-counter')) || 0;
        const suffix = target === 100 ? '%' : target === 0 ? '' : '+';
        const duration = 900;
        const start = performance.now();

        const update = function(now) {
            const progress = Math.min((now - start) / duration, 1);
            const value = Math.round(target * progress);
            counter.textContent = `${value}${suffix}`;

            if (progress < 1) {
                requestAnimationFrame(update);
            }
        };

        requestAnimationFrame(update);
    };

    const counterObserver = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.4
    });

    counters.forEach(function(counter) {
        counterObserver.observe(counter);
    });

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
        });
    });
});
