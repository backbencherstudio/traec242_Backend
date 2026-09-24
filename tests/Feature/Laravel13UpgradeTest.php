<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

test('cache config disables cached object unserialization by default', function () {
    expect(config('cache.serializable_classes'))->toBeFalse();
});

test('sanctum uses the laravel 13 csrf middleware', function () {
    expect(config('sanctum.middleware.validate_csrf_token'))
        ->toBe(PreventRequestForgery::class);
});
