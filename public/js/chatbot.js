document.addEventListener('alpine:init', () => {
    Alpine.data('chatbotWidget', () => ({
        open: false,
        hovered: false,
        input: '',
        loading: false,
        error: '',
        messages: [],
        welcomed: false,
        welcomeTimer: null,
        maxHistory: 12,
        welcomeText: "Hi! I'm GoBot, the OneGoBike assistant. Ask me about our programs, volunteering, donations, or how to get involved.",

        toggle() {
            if (this.open) {
                this.close();
            } else {
                this.open = true;
                this.error = '';
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.$refs.input?.focus();
                });
                this.playWelcome();
            }
        },

        close() {
            this.open = false;
            this.error = '';
            this.cancelWelcome();
        },

        playWelcome() {
            if (this.welcomed || this.messages.length > 0) return;

            this.loading = true;
            this.$nextTick(() => this.scrollToBottom());

            this.welcomeTimer = setTimeout(() => {
                this.welcomeTimer = null;
                this.messages.push({ role: 'model', text: this.welcomeText });
                this.welcomed = true;
                this.loading = false;
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.$refs.input?.focus();
                });
            }, 1200);
        },

        cancelWelcome() {
            if (this.welcomeTimer) {
                clearTimeout(this.welcomeTimer);
                this.welcomeTimer = null;
            }
            // Only clear typing if we were still waiting for the intro
            if (!this.welcomed) {
                this.loading = false;
            }
        },

        scrollToBottom() {
            const thread = this.$refs.thread;
            if (thread) {
                thread.scrollTop = thread.scrollHeight;
            }
        },

        historyPayload() {
            // Exclude the static welcome (first model message) from API history
            const turns = this.messages.slice(this.welcomed ? 1 : 0).filter((m) => m.role === 'user' || m.role === 'model');
            return turns.slice(-this.maxHistory).map((m) => ({
                role: m.role,
                text: m.text.slice(0, 1000),
            }));
        },

        async send() {
            const text = this.input.trim();
            if (!text || this.loading) return;

            this.error = '';
            this.input = '';
            this.messages.push({ role: 'user', text: text.slice(0, 1000) });
            this.loading = true;

            this.$nextTick(() => this.scrollToBottom());

            try {
                const history = this.historyPayload().slice(0, -1); // prior turns only (exclude current user msg)

                const res = await fetch('/chatbot/message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        message: text.slice(0, 1000),
                        history,
                    }),
                });

                const data = await res.json().catch(() => ({}));

                if (!res.ok) {
                    const fallback = "Sorry, I'm not available to respond right now. Please try again later.";
                    this.messages.push({
                        role: 'model',
                        text: data.message || data.reply || fallback,
                    });
                    if (!data.message && !data.reply) {
                        this.error = fallback;
                    }
                    return;
                }

                this.messages.push({
                    role: 'model',
                    text: data.reply || 'Sorry, I could not generate a reply.',
                });
            } catch (e) {
                const fallback = 'Failed to reach GoBot. Please check your connection and try again.';
                this.error = fallback;
                this.messages.push({ role: 'model', text: fallback });
            } finally {
                this.loading = false;
                this.$nextTick(() => {
                    this.scrollToBottom();
                    this.$refs.input?.focus();
                });
            }
        },
    }));
});
