<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class Laravel13UpgradeTest extends TestCase
{
    public function test_cache_config_disables_cached_object_unserialization_by_default(): void
    {
        $this->assertFalse(config('cache.serializable_classes'));
    }

    public function test_sanctum_uses_the_laravel_13_csrf_middleware(): void
    {
        $this->assertSame(
            PreventRequestForgery::class,
            config('sanctum.middleware.validate_csrf_token'),
        );
    }
}
