<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            if ($user->roles()->count() === 0) {
                Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
                $user->assignRole('user');
            }
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    public function admin(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
            $user->syncRoles(['admin']);
        });
    }

    public function provider(): static
    {
        return $this->afterCreating(function (User $user): void {
            Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'api']);
            $user->syncRoles(['provider']);
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }
}
