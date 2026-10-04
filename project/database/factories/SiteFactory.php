<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SiteFactory extends Factory
{
    protected $model = \App\Models\Site::class;

    public function definition(): array
    {
        return [
            'type' => 'school',
            'code' => 'SITE-'.$this->faker->unique()->numberBetween(1000, 999999),
            'name_ar' => $this->faker->company(),
            'status' => 'active',
        ];
    }
}
