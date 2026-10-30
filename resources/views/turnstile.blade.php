@php
    $statePath = $getStatePath();
    $siteKey = $getSiteKey();
    $isConfigured = $isConfigured();
@endphp

@if ($isConfigured && $siteKey)
    <x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
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
                            callback: (token) => this.$wire.set('{{ $statePath }}', token),
                            'expired-callback': () => this.$wire.set('{{ $statePath }}', ''),
                            'error-callback': () => this.$wire.set('{{ $statePath }}', ''),
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
                            // The widget is already gone.
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
    </x-dynamic-component>
@endif

@once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
@endonce
