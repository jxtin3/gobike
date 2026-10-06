
//  Mantra 
(function () {
    const words = ['Power of Youth', 'Community Action', 'Local Leadership', 'Collective Purpose'];
    let index = 0;

    function flipWord() {
        const el = document.getElementById('mantra-word');
        if (!el) return;

        el.classList.remove('mantra-flip-in');
        el.classList.add('mantra-flip-out');

        setTimeout(() => {
            index = (index + 1) % words.length;
            el.textContent = words[index];

            el.classList.remove('mantra-flip-out');
            void el.offsetWidth;
            el.classList.add('mantra-flip-in');
        }, 250);
    }

    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('mantra-word');
        if (el) setInterval(flipWord, 2800);
    });
})();

//  program slider  
function programSlider() {
    return {
        active: 0,
        interval: null,
        intervalMs: 5000,
        progress: 0,
        progressInterval: null,
        programs: [
            {
                img: '/images/prog-1.jpg',
                accent: '#F97316',
                title: 'Go Bike',
                desc: 'Promoting health, mobility, and sustainable livelihoods on two wheels.',
                href: '/what-we-do'
            },
            {
                img: '/images/story-2.jpg',
                accent: '#2FA7FF',
                title: 'Community Outreach',
                desc: 'Engaging local communities through livelihood programs, nutrition drives, and educational workshops that build long-term capacity and self-reliance.',
                href: '/what-we-do'
            },
            {
                img: '/images/story-3.jpg',
                accent: '#F97316',
                title: 'Volunteer Mobilization',
                desc: 'Recruiting, training, and deploying youth cyclists as community responders — building a network ready to act during emergencies and routine service.',
                href: '/what-we-do'
            },
            {
                img: '/images/home.jpg',
                accent: '#132D6B',
                title: 'Disaster Preparedness',
                desc: 'Equipping communities with the knowledge, tools, and confidence to prepare for, respond to, and recover from natural disasters — before they strike.',
                href: '/what-we-do'
            }
        ],
        init() {
            this.start();
        },
        start() {
            this.resetProgress();
            this.interval = setInterval(() => {
                this.next(false);
            }, this.intervalMs);
        },
        pause() {
            if (this.interval) clearInterval(this.interval);
            if (this.progressInterval) clearInterval(this.progressInterval);
            this.interval = null;
            this.progressInterval = null;
        },
        resume() {
            if (!this.interval) this.start();
        },
        next(manual = true) {
            this.active = (this.active + 1) % this.programs.length;
            this.resetProgress();
            if (manual && this.interval) {
                clearInterval(this.interval);
                this.interval = setInterval(() => { this.next(false); }, this.intervalMs);
            }
        },
        prev(manual = true) {
            this.active = (this.active - 1 + this.programs.length) % this.programs.length;
            this.resetProgress();
            if (manual && this.interval) {
                clearInterval(this.interval);
                this.interval = setInterval(() => { this.next(false); }, this.intervalMs);
            }
        },
        goTo(index) {
            this.active = index;
            this.resetProgress();
            clearInterval(this.interval);
            this.start();
        },
        resetProgress() {
            clearInterval(this.progressInterval);
            this.progress = 0;
            // wait a tick so DOM updates 0%
            setTimeout(() => {
                this.progress = 100;
            }, 50);
        }
    }
}
