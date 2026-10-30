{{--
    Raw Turnstile widget for use in Livewire components that manage their own
    token property (e.g. the Login page with `public string $turnstileToken = ''`).
--}}
<div
    wire:ignore
    x-data="{
        widgetId: null,
        stopListening: null,
        init() {
            const render = () => {
                if (typeof turnstile === 'undefined') {
                    setTimeout(render, 50);
                    return;
                }
                this.widgetId = turnstile.render(this.$refs.widget, {
                    sitekey: '{{ $siteKey }}',
                    callback: (token) => this.$wire.set('turnstileToken', token),
                    'expired-callback': () => this.$wire.set('turnstileToken', ''),
                    'error-callback': () => this.$wire.set('turnstileToken', ''),
                });
            };
            render();

            this.stopListening = this.$wire.on('turnstile.reset', () => {
                if (this.widgetId === null || typeof turnstile === 'undefined') {
                    return;
                }

                try {
                    turnstile.reset(this.widgetId);
                } catch (e) {
                    // The widget is already gone, for example while the MFA step is shown.
                }
            });
        },
        destroy() {
            if (this.stopListening) {
                this.stopListening();
            }

            if (this.widgetId !== null && typeof turnstile !== 'undefined') {
                try {
                    turnstile.remove(this.widgetId);
                } catch (e) {
                    // Nothing left to remove.
                }
            }

            this.widgetId = null;
        },
    }"
>
    <div x-ref="widget"></div>
</div>

@error('turnstileToken')
    <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
@enderror

@once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
@endonce
