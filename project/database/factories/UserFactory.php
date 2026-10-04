<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = \App\Models\User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password_hash' => Hash::make('Password-123'),
            'role' => 'technician',
            'view_scope' => 'assigned_only',
            'status' => 'active',
            'mfa_enabled' => false,
        ];
    }

    public function chairman(): static
    {
        return $this->state(fn () => ['role' => 'chairman', 'view_scope' => 'all']);
    }

    public function secretary(): static
    {
        return $this->state(fn () => ['role' => 'secretary', 'view_scope' => 'all']);
    }

    public function engineer(): static
    {
        return $this->state(fn () => ['role' => 'engineer', 'view_scope' => 'sites']);
    }

    public function schoolManager(): static
    {
        return $this->state(fn () => ['role' => 'school_manager', 'view_scope' => 'own_site']);
    }
}
