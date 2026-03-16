<?php

namespace Database\Seeders;

use App\Enums\MenuItemCategory;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $restaurants = [
            [
                'name' => 'The Golden Fork',
                'address' => '12 Sunset Blvd, Los Angeles, CA',
                'phone' => '+1 213-555-0101',
                'opening_hours' => '10:00 - 22:00',
                'menu_items' => [
                    ['name' => 'Caesar Salad', 'description' => 'Crisp romaine with house-made Caesar dressing', 'price' => 9.50, 'category' => MenuItemCategory::Appetizer, 'is_available' => true],
                    ['name' => 'Grilled Salmon', 'description' => 'Atlantic salmon with lemon butter sauce', 'price' => 24.00, 'category' => MenuItemCategory::Main, 'is_available' => true],
                    ['name' => 'Beef Tenderloin', 'description' => '8oz tenderloin with seasonal vegetables', 'price' => 32.00, 'category' => MenuItemCategory::Main, 'is_available' => true],
                    ['name' => 'Chocolate Lava Cake', 'description' => 'Warm chocolate cake with vanilla ice cream', 'price' => 8.50, 'category' => MenuItemCategory::Dessert, 'is_available' => true],
                    ['name' => 'Fresh Lemonade', 'description' => 'Freshly squeezed lemonade with mint', 'price' => 4.00, 'category' => MenuItemCategory::Drink, 'is_available' => true],
                ],
            ],
            [
                'name' => 'Bamboo Garden',
                'address' => '88 Chinatown Square, San Francisco, CA',
                'phone' => '+1 415-555-0202',
                'opening_hours' => '11:00 - 21:00',
                'menu_items' => [
                    ['name' => 'Spring Rolls', 'description' => 'Crispy vegetable spring rolls with sweet chili dip', 'price' => 7.00, 'category' => MenuItemCategory::Appetizer, 'is_available' => true],
                    ['name' => 'Kung Pao Chicken', 'description' => 'Spicy stir-fried chicken with peanuts and peppers', 'price' => 16.50, 'category' => MenuItemCategory::Main, 'is_available' => true],
                    ['name' => 'Dim Sum Basket', 'description' => 'Assorted steamed dumplings (6 pcs)', 'price' => 12.00, 'category' => MenuItemCategory::Appetizer, 'is_available' => true],
                    ['name' => 'Mango Pudding', 'description' => 'Silky smooth mango pudding with fresh mango', 'price' => 6.50, 'category' => MenuItemCategory::Dessert, 'is_available' => true],
                    ['name' => 'Jasmine Tea', 'description' => 'Traditional pot of fragrant jasmine tea', 'price' => 3.50, 'category' => MenuItemCategory::Drink, 'is_available' => true],
                ],
            ],
        ];

        foreach ($restaurants as $data) {
            $menuItems = $data['menu_items'];
            unset($data['menu_items']);

            $restaurant = Restaurant::create(array_merge($data, ['user_id' => $user->id]));

            foreach ($menuItems as $item) {
                MenuItem::create(array_merge($item, ['restaurant_id' => $restaurant->id]));
            }
        }
    }
}
