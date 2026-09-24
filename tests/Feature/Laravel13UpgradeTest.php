<?php

declare(strict_types=1);

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

test('cache config disables cached object unserialization by default', function (): void {
    expect(config('cache.serializable_classes'))->toBeFalse();
});

test('sanctum uses the laravel 13 csrf middleware', function (): void {
    expect(config('sanctum.middleware.validate_csrf_token'))
        ->toBe(PreventRequestForgery::class);
});
