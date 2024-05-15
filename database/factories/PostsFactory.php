<?php

namespace Database\Factories;

use App\Models\Posts;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Posts::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'businessName' => $this->faker->company, // Generate a random business name
            'description' => $this->faker->sentence(), // Generate a random description
            'image' => 'https://via.placeholder.com/800x800', // Use a placeholder image
            'is_active' => 1, // Set active status
        ];
    }
}