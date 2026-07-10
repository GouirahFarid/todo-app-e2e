<?php

namespace Database\Factories;

use App\Models\TodoItem;
use App\Models\TodoList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TodoItem>
 */
class TodoItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'todo_list_id' => TodoList::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'is_completed' => false,
        ];
    }

    /**
     * Indicate that the item is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_completed' => true,
        ]);
    }
}
