// Back-to-top visibility
(function () {
    const btn = document.getElementById('back-to-top');
    if (!btn) return;
    window.addEventListener('scroll', function () {
        if (window.scrollY > 400) {
            btn.classList.add('show');
        } else {
            btn.classList.remove('show');
        }
    }, { passive: true });
})();

// Scroll reveal for sections with .reveal class
(function () {
    const revealEls = document.querySelectorAll('.reveal');
    if (!revealEls.length) return;

    if (!('IntersectionObserver' in window)) {
        revealEls.forEach(function (el) { el.classList.add('visible'); });
        return;
    }

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0,
        rootMargin: '0px 0px 80px 0px'
    });

    revealEls.forEach(function (el) {
        const rect = el.getBoundingClientRect();
        // Immediately reveal elements that are already within or above the viewport on initial load
        if (rect.top <= window.innerHeight && rect.bottom >= 0) {
            el.classList.add('visible');
        } else {
            observer.observe(el);
        }
    });
})();

