@props([
    'siteKey' => config('filament-turnstile.site_key'),
    'theme'   => 'auto',
    'size'    => 'normal',
])

@if($siteKey)
    <div
        class="cf-turnstile"
        data-sitekey="{{ $siteKey }}"
        data-theme="{{ $theme }}"
        data-size="{{ $size }}"
        {{ $attributes }}
    ></div>

    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
