<?php

namespace Database\Factories;

use App\Models\FamilyTree;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        $sex = fake()->randomElement(['female', 'male', 'unknown']);

        return [
            'family_tree_id' => FamilyTree::factory(),
            'created_by' => User::factory(),
            'given_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'surname' => fake()->lastName(),
            'birth_surname' => fake()->optional()->lastName(),
            'sex' => $sex,
            'birth_date' => fake()->dateTimeBetween('-90 years', '-10 years'),
            'birth_date_text' => null,
            'death_date' => null,
            'death_date_text' => null,
            'birth_place' => fake()->city(),
            'death_place' => null,
            'is_living' => true,
            'headline' => fake()->optional()->sentence(4),
            'notes' => fake()->optional()->paragraph(),
        ];
    }
}
