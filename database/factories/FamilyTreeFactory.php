<?php

namespace Database\Factories;

use App\Models\FamilyTree;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FamilyTree>
 */
class FamilyTreeFactory extends Factory
{
    protected $model = FamilyTree::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->lastName().' Family Tree',
            'description' => fake()->sentence(),
            'home_region' => fake()->city().', '.fake()->country(),
            'privacy' => fake()->randomElement(['private', 'invited', 'public']),
        ];
    }
}
