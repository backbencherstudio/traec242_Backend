<?php

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user has default role user upon factory creation', function (): void {
    $user = createClientUser();

    expect($user->roles()->count())->toBe(1)
        ->and($user->hasRole(RoleSeeder::ROLE_USER))->toBeTrue()
        ->and($user->isUser())->toBeTrue()
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->isProvider())->toBeFalse();
});

test('assigning a new role replaces any existing role so the user has only one role', function (): void {
    $user = createClientUser();

    expect($user->roles()->count())->toBe(1)
        ->and($user->hasRole(RoleSeeder::ROLE_USER))->toBeTrue();

    $user->assignRole(RoleSeeder::ROLE_PROVIDER);

    expect($user->roles()->count())->toBe(1)
        ->and($user->hasRole(RoleSeeder::ROLE_PROVIDER))->toBeTrue()
        ->and($user->hasRole(RoleSeeder::ROLE_USER))->toBeFalse()
        ->and($user->isProvider())->toBeTrue()
        ->and($user->isUser())->toBeFalse();

    $user->assignRole(RoleSeeder::ROLE_ADMIN);

    expect($user->roles()->count())->toBe(1)
        ->and($user->hasRole(RoleSeeder::ROLE_ADMIN))->toBeTrue()
        ->and($user->hasRole(RoleSeeder::ROLE_PROVIDER))->toBeFalse()
        ->and($user->isAdmin())->toBeTrue();
});

test('syncRoles keeps only one single role even if multiple roles are provided', function (): void {
    $user = createClientUser();

    $user->syncRoles([RoleSeeder::ROLE_PROVIDER, RoleSeeder::ROLE_ADMIN]);

    expect($user->roles()->count())->toBe(1)
        ->and($user->hasRole(RoleSeeder::ROLE_PROVIDER))->toBeTrue()
        ->and($user->hasRole(RoleSeeder::ROLE_ADMIN))->toBeFalse();
});

test('setRole sets the single role and replaces any previous role', function (): void {
    $user = createClientUser();

    $user->setRole(RoleSeeder::ROLE_ADMIN);

    expect($user->roles()->count())->toBe(1)
        ->and($user->role)->toBe(RoleSeeder::ROLE_ADMIN)
        ->and($user->isAdmin())->toBeTrue();
});
