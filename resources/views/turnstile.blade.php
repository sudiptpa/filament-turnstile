@php
    $statePath = $getStatePath();
    $siteKey = $getSiteKey();
    $isConfigured = $isConfigured();
@endphp

@if ($isConfigured && $siteKey)
    <x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
        <div
            wire:ignore
            x-data="{ widgetId: null }"
            x-init="
                const init = () => {
                    if (typeof turnstile === 'undefined') {
                        setTimeout(init, 50);
                        return;
                    }
                    widgetId = turnstile.render($refs.widget, {
                        sitekey: '{{ $siteKey }}',
                        callback: (token) => $wire.set('{{ $statePath }}', token),
                        'expired-callback': () => $wire.set('{{ $statePath }}', ''),
                        'error-callback': () => $wire.set('{{ $statePath }}', ''),
                    });
                };
                init();

                $wire.on('turnstile.reset', () => {
                    if (widgetId !== null && typeof turnstile !== 'undefined') {
                        turnstile.reset(widgetId);
                    }
                });
            "
        >
            <div x-ref="widget"></div>
        </div>
    </x-dynamic-component>
@endif

@once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
@endonce
