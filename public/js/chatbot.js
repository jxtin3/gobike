document.addEventListener('alpine:init', () => {
    Alpine.data('chatbotWidget', () => ({
        open: false,
        input: '',
        loading: false,
        typing: false,
        typeTimer: null,
        typeFinish: null,
        error: '',
        messages: [],
        welcomed: false,
        welcomeTimer: null,
        maxHistory: 12,
        welcomeText: "Hi! I'm GoBot, the OneGoBike assistant. Ask me how to volunteer, get trained, donate, or find any page, and I'll guide you step by step. (English or Tagalog)",

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
            this.finishTyping();
            this.cancelWelcome();
        },

        playWelcome() {
            if (this.welcomed || this.messages.length > 0) return;

            this.loading = true;
            this.$nextTick(() => this.scrollToBottom());

            this.welcomeTimer = setTimeout(() => {
                this.welcomeTimer = null;
                this.welcomed = true;
                this.loading = false;
                this.typeMessage(this.welcomeText).then(() => {
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

        // Reveal a bot reply a few characters at a time. The full text stays in `text`
        // (used for chat history); `shown` is what the bubble displays.
        typeMessage(text) {
            return new Promise((resolve) => {
                const instant = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
                this.messages.push({ role: 'model', text, shown: instant ? text : '' });
                const index = this.messages.length - 1;
                this.$nextTick(() => this.scrollToBottom());

                if (instant) {
                    resolve();
                    return;
                }

                // Longer answers type a little faster so they never take more than ~4 seconds.
                const step = Math.max(1, Math.ceil(text.length / 200));
                let pos = 0;
                this.typing = true;

                this.typeFinish = () => {
                    clearInterval(this.typeTimer);
                    this.typeTimer = null;
                    this.typeFinish = null;
                    this.messages[index].shown = text;
                    this.typing = false;
                    this.$nextTick(() => this.scrollToBottom());
                    resolve();
                };

                this.typeTimer = setInterval(() => {
                    pos += step;
                    if (pos >= text.length) {
                        this.typeFinish();
                        return;
                    }
                    this.messages[index].shown = text.slice(0, pos);
                    this.scrollToBottom();
                }, 20);
            });
        },

        finishTyping() {
            if (this.typeFinish) this.typeFinish();
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
            if (!text || this.loading || this.typing) return;

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

                this.loading = false;
                await this.typeMessage(data.reply || 'Sorry, I could not generate a reply.');
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
