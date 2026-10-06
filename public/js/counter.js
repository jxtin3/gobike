// Shared counter animation used by the homepage.
function createCounterAnimation(counts) {
    return {
        started: false,
        counts: counts,
        startCounters() {
            if (this.started) return;
            this.started = true;

            this.counts.forEach((stat, idx) => {
                const duration = 3000;
                const startTime = performance.now();

                const easeOut = t => 1 - Math.pow(1 - t, 3);

                const step = (now) => {
                    const elapsed = now - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const value = Math.round(easeOut(progress) * stat.target);
                    const formattedValue = stat.raw ? value.toString() : value.toLocaleString();
                    this.counts[idx].display = formattedValue + (stat.suffix || '');
                    if (progress < 1) requestAnimationFrame(step);
                };
                requestAnimationFrame(step);
            });
        }
    };
}

function initCountUpCounters() {
    const counters = document.querySelectorAll('[data-count-up]');
    if (!counters.length) return;

    const animate = (element) => {
        const targetText = element.dataset.countUp.trim();
        const match = targetText.match(/^([^0-9]*)([0-9][0-9,]*(?:\.[0-9]+)?)(.*)$/);

        if (!match) {
            element.textContent = targetText;
            return;
        }

        const [, prefix, numericText, suffix] = match;
        const target = Number(numericText.replaceAll(',', ''));
        const decimals = (numericText.split('.')[1] || '').length;
        const formatter = new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        });
        const duration = 1800;
        const startTime = performance.now();
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!Number.isFinite(target) || reduceMotion) {
            element.textContent = targetText;
            return;
        }

        element.textContent = `${prefix}${formatter.format(0)}${suffix}`;

        const step = (now) => {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const value = target * eased;
            element.textContent = `${prefix}${formatter.format(progress === 1 ? target : value)}${suffix}`;

            if (progress < 1) requestAnimationFrame(step);
        };

        requestAnimationFrame(step);
    };

    if (!('IntersectionObserver' in window)) {
        counters.forEach(animate);
        return;
    }

    const observer = new IntersectionObserver((entries, currentObserver) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            currentObserver.unobserve(entry.target);
            animate(entry.target);
        });
    }, { threshold: 0.35 });

    counters.forEach((counter) => observer.observe(counter));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCountUpCounters, { once: true });
} else {
    initCountUpCounters();
}