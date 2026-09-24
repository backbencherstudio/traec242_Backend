<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can list clients using UserManagementClientResource and sendResponse', function (): void {
    $client = User::factory()->create([
        'type' => 0,
        'name' => 'Alice',
        'last_name' => 'Smith',
        'email' => 'alice@example.com',
    ]);

    $response = $this->actingAs(createAdminUser(), 'api')
        ->getJson('/api/admin/client/index');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'image_url',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'joined',
                    'total_orders',
                    'total_spent',
                    'status',
                    'is_verified',
                ],
            ],
            'meta',
            'links',
        ]);
});

test('admin can list sellers using UserManagementSellerResource and sendResponse', function (): void {
    $seller = User::factory()->create([
        'type' => 2,
        'name' => 'Bob',
        'last_name' => 'Builder',
        'email' => 'bob@example.com',
    ]);

    $response = $this->actingAs(createAdminUser(), 'api')
        ->getJson('/api/admin/client/seller-index');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'image_url',
                    'name',
                    'email',
                    'phone',
                    'address',
                    'joined',
                    'total_products',
                    'status',
                    'is_verified',
                ],
            ],
            'meta',
            'links',
        ]);
});

test('admin can view client details with stats', function (): void {
    $client = User::factory()->create([
        'type' => 0,
        'name' => 'Charlie',
        'last_name' => 'Brown',
    ]);

    $response = $this->actingAs(createAdminUser(), 'api')
        ->getJson("/api/admin/client/show-details/{$client->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Charlie Brown')
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'phone',
                'image_url',
                'address',
                'status',
                'is_verified',
                'joined',
                'stats' => ['total_orders', 'total_spent'],
            ],
        ]);
});

test('admin can update user status', function (): void {
    $user = User::factory()->create(['status' => 1]);

    $response = $this->actingAs(createAdminUser(), 'api')
        ->patchJson("/api/admin/client/change-status/{$user->id}", [
            'status' => 0,
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'Inactive');

    expect($user->fresh()->status)->toBeFalse();
});

test('admin can delete user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs(createAdminUser(), 'api')
        ->deleteJson("/api/admin/client/delete-user/{$user->id}");

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'User deleted successfully.');

    $this->assertSoftDeleted('users', ['id' => $user->id]);
});
