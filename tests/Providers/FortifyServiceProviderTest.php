<?php

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

it('two-factor rate limiter returns a per-minute limit', function () {
    $request = Request::create('/two-factor-challenge', 'POST');
    $request->setLaravelSession(app('session')->driver());

    $result = RateLimiter::limiter('two-factor')($request);

    expect($result)->toBeInstanceOf(Limit::class);
});

it('passkeys rate limiter returns a per-minute limit', function () {
    $request = Request::create('/passkeys', 'POST');
    $request->setLaravelSession(app('session')->driver());

    $result = RateLimiter::limiter('passkeys')($request);

    expect($result)->toBeInstanceOf(Limit::class);
});
