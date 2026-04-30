{{--
    Raw Turnstile widget for use in Livewire components that manage their own
    token property (e.g. the Login page with `public string $turnstileToken = ''`).
--}}
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
                callback: (token) => $wire.set('turnstileToken', token),
                'expired-callback': () => $wire.set('turnstileToken', ''),
                'error-callback': () => $wire.set('turnstileToken', ''),
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

@error('turnstileToken')
    <p class="mt-2 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
@enderror

@once
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" defer></script>
@endonce
