<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => Str::limit(fake()->unique()->userName(), 50, ''),
            'email' => fake()->unique()->safeEmail(),
            'nama' => Str::limit(fake()->name(), 100, ''),
            'password' => static::$password ??= Hash::make('password'),
            'peran' => 'operator',
            'aktif' => true,
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'admin',
        ]);
    }

    /**
     * Indicate that the user is an operator.
     */
    public function operator(): static
    {
        return $this->state(fn (array $attributes) => [
            'peran' => 'operator',
        ]);
    }

    /**
     * Indicate that the user account is inactive.
     */
    public function nonaktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'aktif' => false,
        ]);
    }

    /**
     * Indicate that the user account is locked until a given time.
     */
    public function terkunci(?string $sampai = null): static
    {
        return $this->state(fn (array $attributes) => [
            'gagal_login' => 5,
            'terkunci_sampai' => $sampai ?? now()->addMinutes(10),
        ]);
    }
}
