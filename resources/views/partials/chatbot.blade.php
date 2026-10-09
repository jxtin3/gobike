{{-- GoBot floating chat widget --}}
<div
    x-data="chatbotWidget()"
    @keydown.escape.window="if (open) { close(); $event.stopPropagation(); }"
    class="gobot-widget fixed bottom-6 right-6 z-50 flex flex-col items-end gap-3"
>
    {{-- Chat panel --}}
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="gobot-panel flex w-[min(22rem,calc(100vw-2.5rem))] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-black/20"
        role="dialog"
        aria-label="Chat with GoBot"
    >
        {{-- Header --}}
        <div class="gobot-header flex shrink-0 items-center gap-3 bg-[#0D1B2A] px-4 py-3.5">
            <img
                src="{{ asset('images/GoBot.jpg') }}"
                alt=""
                class="h-10 w-10 rounded-full object-cover ring-2 ring-white/20"
            >
            <div class="min-w-0 flex-1">
                <p class="font-heading text-base font-semibold text-white">GoBot</p>
                <p class="flex items-center gap-1.5 text-[11px] text-white/65">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    OneGoBike assistant
                </p>
            </div>
            <button
                type="button"
                @click="close()"
                class="rounded-lg p-2 text-white/70 transition-colors hover:bg-white/10 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                aria-label="Close chat"
                title="Close chat"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Messages --}}
        <div
            x-ref="thread"
            class="gobot-thread min-h-0 flex-1 space-y-4 overflow-y-auto bg-[#F5F7FB] px-4 py-5"
            role="log"
            aria-live="polite"
        >
            <template x-for="(msg, index) in messages" :key="index">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="msg.role === 'user' ? 'gobot-bubble gobot-bubble-user' : 'gobot-bubble gobot-bubble-assistant'"
                        x-text="msg.text"
                    ></div>
                </div>
            </template>

            {{-- Typing indicator --}}
            <div x-show="loading" x-cloak class="flex justify-start">
                <div class="flex items-center gap-1.5 rounded-2xl rounded-bl-md border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <span class="gobot-dot h-1.5 w-1.5 rounded-full bg-slate-400"></span>
                    <span class="gobot-dot h-1.5 w-1.5 rounded-full bg-slate-400" style="animation-delay: 0.15s"></span>
                    <span class="gobot-dot h-1.5 w-1.5 rounded-full bg-slate-400" style="animation-delay: 0.3s"></span>
                </div>
            </div>
        </div>

        {{-- Error --}}
        <p
            x-show="error"
            x-cloak
            x-text="error"
            class="shrink-0 border-t border-rose-100 bg-rose-50 px-3 py-1.5 text-xs text-rose-700"
            role="alert"
        ></p>

        {{-- Input --}}
        <form @submit.prevent="send()" class="gobot-composer flex shrink-0 items-end gap-2 border-t border-slate-200 bg-white p-3">
            <label for="gobot-input" class="sr-only">Message</label>
            <textarea
                id="gobot-input"
                x-ref="input"
                x-model="input"
                @keydown.enter.prevent="if (!$event.shiftKey) send()"
                rows="1"
                maxlength="1000"
                :disabled="loading"
                placeholder="Ask GoBot…"
                class="gobot-textarea max-h-24 min-h-[2.75rem] flex-1 resize-none rounded-xl border border-slate-200 bg-[#F8FAFC] px-3.5 py-2.5 text-sm text-[#111827] placeholder:text-slate-400 focus:border-[#2FA7FF] focus:outline-none focus:ring-2 focus:ring-[#2FA7FF]/20 disabled:opacity-60"
            ></textarea>
            <button
                type="submit"
                :disabled="loading || !input.trim()"
                aria-label="Send"
                class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#F97316] text-white shadow-sm transition-all hover:bg-orange-600 hover:shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-[#F97316]/40 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                </svg>
            </button>
        </form>
    </div>

    {{-- Launcher + hover tooltip --}}
    <div class="relative" @mouseenter="hovered = true" @mouseleave="hovered = false">
        <div
            x-show="hovered && !open"
            x-cloak
            x-transition.opacity.duration.150ms
            class="pointer-events-none absolute bottom-full right-0 mb-2 whitespace-nowrap rounded-lg bg-[#0D1B2A] px-3 py-1.5 text-xs font-medium text-white shadow-lg"
        >
            Chat with GoBot
            <span class="absolute -bottom-1 right-5 h-2 w-2 rotate-45 bg-[#0D1B2A]"></span>
        </div>

        <button
            type="button"
            @click="toggle()"
            x-show="!open"
            x-cloak
            :aria-expanded="open.toString()"
            :aria-label="open ? 'Close GoBot chat' : 'Open GoBot chat'"
            class="group relative flex h-14 w-14 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-white shadow-lg shadow-black/20 ring-1 ring-black/5 transition-transform duration-200 hover:scale-105 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#2FA7FF] focus-visible:ring-offset-2"
        >
            <img
                src="{{ asset('images/GoBot.jpg') }}"
                alt="GoBot"
                class="h-full w-full object-cover"
            >
        </button>
    </div>
</div>

<style>
    .gobot-panel {
        width: min(22rem, calc(100vw - 2.5rem));
        height: min(70dvh, 38rem);
        max-height: calc(100dvh - 7rem);
    }

    .gobot-thread {
        min-height: 0;
        overscroll-behavior: contain;
        scrollbar-color: #cbd5e1 transparent;
        scrollbar-width: thin;
    }

    .gobot-thread::-webkit-scrollbar {
        width: 6px;
    }

    .gobot-thread::-webkit-scrollbar-thumb {
        border-radius: 999px;
        background: #cbd5e1;
    }

    .gobot-bubble {
        max-width: 88%;
        padding: .75rem .9rem;
        border-radius: 1rem;
        font-size: .875rem;
        line-height: 1.55;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        box-shadow: 0 1px 2px rgb(15 23 42 / 6%);
    }

    .gobot-bubble-user {
        border-bottom-right-radius: .35rem;
        background: #132d6b;
        color: #fff;
    }

    .gobot-bubble-assistant {
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: .35rem;
        background: #fff;
        color: #111827;
    }

    .gobot-textarea {
        line-height: 1.5;
    }

    .gobot-dot {
        animation: gobot-bounce 1s ease-in-out infinite;
    }

    @media (max-width: 640px) {
        .gobot-widget {
            right: 1rem;
            bottom: 1rem;
        }

        .gobot-panel {
            width: calc(100vw - 2rem);
            height: min(calc(100dvh - 7rem), 40rem);
            max-height: calc(100dvh - 7rem);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .gobot-dot {
            animation: none;
        }
    }

    @keyframes gobot-bounce {
        0%, 80%, 100% { transform: translateY(0); opacity: 0.4; }
        40% { transform: translateY(-4px); opacity: 1; }
    }
</style>
