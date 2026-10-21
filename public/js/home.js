
//  program slider  
function programSlider() {
    return {
        active: 0,
        touchStartX: null,
        touchStartY: null,
        dragging: false,
        dragged: false,
        dragStartX: null,
        dragStartY: null,
        interval: null,
        intervalMs: 4000,
        progress: 0,
        progressInterval: null,
        programs: [
            {
                img: '/images/homepage/go_bike.jpg',
                accent: '#F97316',
                title: 'Go Bike',
                desc: 'Transforming young volunteers into active community responders by equipping them with the skills, awareness, and spirit of volunteerism to respond to community needs, promote safety and lend a helping a hand when it matters most',
                href: '/what-we-do'
            },
            {
                img: '/images/homepage/alwar.jpg',
                accent: '#2FA7FF',
                title: 'Alwar',
                desc: 'Continuing to empower students within school communities to become proactive young responders, equiped with knowledge, skills, and compassion to promote safety, preparedness, and services to others.',
                href: '/what-we-do'
            },
            {
                img: '/images/homepage/ronda_kalusugan.jpg',
                accent: '#F97316',
                title: 'Ronda Kalusugan',
                desc: 'Mobilizing volunteers to reach communites with basic healthcare services, including blood pressure monitoring and blood sugar checking, promoting early awareness and making essential health checks more accessible to everyone.',
                href: '/what-we-do'
            },
            {
                img: '/images/homepage/padyakaalaman.png',
                accent: '#132D6B',
                title: 'PadyaKaalaman',
                desc: 'Making knowledge accessible through educational initiatives that inspire curiosity, encourage learning, and open opportunities for learners.',
                href: '/what-we-do'
            },
            {
                img: '/images/homepage/padyakaisipan.jpeg',
                accent: '#2FA7FF',
                title: 'Padyakaisipan',
                desc: 'Promoting mental health awareness by creating safe spaces for conversations breaking the stigma surrounding mental healt, and encouraging young people to prioritize emotional well-being, understanding, and support for one another.',
                href: '/what-we-do'
            },
            {
                img: '/images/homepage/hatake.jpeg',
                accent: '#F97316',
                title: 'Hatake Farm',
                desc: 'Bridging communities together through sustainable farming, food security, and shared harvests that nurture both the land and the people.',
                href: '/what-we-do'
            }
        ],
        // Position of a slide relative to the active one: 0 = centre, -1/1 = peeking at the sides,
        // anything further is parked off-screen. Wraps around so the carousel loops.
        offset(index) {
            const n = this.programs.length;
            let o = (index - this.active + n) % n;
            if (o > n / 2) o -= n;
            return o;
        },
        slideClass(index) {
            const o = this.offset(index);
            if (o === 0) return 'is-active';
            if (Math.abs(o) === 1) return 'is-side';
            return 'is-hidden';
        },
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
        touchStart(event) {
            const touch = event.changedTouches[0];
            this.touchStartX = touch.clientX;
            this.touchStartY = touch.clientY;
        },
        touchEnd(event) {
            if (this.touchStartX === null || this.touchStartY === null) return;

            const touch = event.changedTouches[0];
            const deltaX = touch.clientX - this.touchStartX;
            const deltaY = touch.clientY - this.touchStartY;
            this.touchStartX = null;
            this.touchStartY = null;

            if (Math.abs(deltaX) < 50 || Math.abs(deltaX) <= Math.abs(deltaY)) return;

            if (deltaX < 0) {
                this.next();
            } else {
                this.prev();
            }
        },
        // Mouse: click and drag left/right to change slides (touch uses touchStart/touchEnd above)
        pointerStart(event) {
            if (event.pointerType !== 'mouse' || event.button !== 0) return;
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.dragging = true;
            this.dragged = false;
        },
        pointerEnd(event) {
            if (!this.dragging) return;
            this.dragging = false;

            const deltaX = event.clientX - this.dragStartX;
            const deltaY = event.clientY - this.dragStartY;
            if (Math.abs(deltaX) < 50 || Math.abs(deltaX) <= Math.abs(deltaY)) return;

            // a real drag: don't let the release count as a click on the "Learn More" link
            this.dragged = true;
            setTimeout(() => { this.dragged = false; }, 150);
            if (deltaX < 0) {
                this.next();
            } else {
                this.prev();
            }
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
