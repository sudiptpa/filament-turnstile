<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile Site Key
    |--------------------------------------------------------------------------
    |
    | Your Cloudflare Turnstile site key. This is the public key rendered in
    | the widget. Find it in your Cloudflare dashboard under Turnstile.
    |
    | Test site key (always passes): 1x00000000000000000000AA
    | Test site key (always blocks): 2x00000000000000000000AB
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile Secret Key
    |--------------------------------------------------------------------------
    |
    | Your Cloudflare Turnstile secret key. Used for server-side token
    | verification. Never expose this in the browser.
    |
    | Test secret key (always passes): 1x0000000000000000000000000000000AA
    | Test secret key (always fails):  2x0000000000000000000000000000000AA
    |
    */

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Verification Endpoint
    |--------------------------------------------------------------------------
    |
    | The Cloudflare Turnstile token verification URL. You should not need
    | to change this unless you are testing against a custom proxy.
    |
    */

    'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeouts
    |--------------------------------------------------------------------------
    |
    | Connect and read timeouts (in seconds) for the verification HTTP request.
    |
    */

    'connect_timeout' => 5,
    'timeout' => 10,

];
