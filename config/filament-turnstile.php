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
    | Cloudflare test site keys (see developers.cloudflare.com/turnstile):
    |   1x00000000000000000000AA  always passes
    |   2x00000000000000000000AB  always fails
    |   3x00000000000000000000FF  forces an interactive challenge
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
    | Cloudflare test secret keys:
    |   1x0000000000000000000000000000000AA  always passes
    |   2x0000000000000000000000000000000AA  always fails
    |   3x0000000000000000000000000000000AA  returns "token already spent"
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
