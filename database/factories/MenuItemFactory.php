<?php

namespace Database\Factories;

use App\Enums\MenuItemCategory;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 1, 100),
            'category' => $this->faker->randomElement(MenuItemCategory::cases()),
            'is_available' => true,
        ];
    }
}
