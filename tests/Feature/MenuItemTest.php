<?php

namespace Tests\Feature;

use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuItemTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->restaurant = Restaurant::factory()->create(['user_id' => $this->user->id]);
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    public function test_can_list_menu_items_with_pagination(): void
    {
        MenuItem::factory()->count(15)->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->actingAsUser()
            ->getJson("/api/private/restaurants/{$this->restaurant->id}/menu_items");

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'data' => [
                    'items' => [['id', 'name', 'price', 'category']],
                    'meta' => ['per_page', 'has_more', 'next_cursor', 'prev_cursor'],
                ],
            ]);

        $this->assertEquals(10, count($response->json('data.items')));
        $this->assertTrue($response->json('data.meta.has_more'));
    }

    public function test_can_filter_menu_items_by_category(): void
    {
        MenuItem::factory()->count(3)->create(['restaurant_id' => $this->restaurant->id, 'category' => 'main']);
        MenuItem::factory()->count(2)->create(['restaurant_id' => $this->restaurant->id, 'category' => 'drink']);

        $response = $this->actingAsUser()
            ->getJson("/api/private/restaurants/{$this->restaurant->id}/menu_items?category=main");

        $response->assertStatus(200);
        $this->assertEquals(3, count($response->json('data.items')));

        collect($response->json('data.items'))->each(function ($item) {
            $this->assertEquals('main', $item['category']);
        });
    }

    public function test_can_search_menu_items_by_name(): void
    {
        MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Grilled Chicken']);
        MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Chicken Soup']);
        MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Beef Steak']);

        $response = $this->actingAsUser()
            ->getJson("/api/private/restaurants/{$this->restaurant->id}/menu_items?search=chicken");

        $response->assertStatus(200);
        $this->assertEquals(2, count($response->json('data.items')));
    }

    public function test_can_filter_by_category_and_search_by_name(): void
    {
        MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Grilled Chicken', 'category' => 'main']);
        MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Chicken Soup', 'category' => 'appetizer']);
        MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => 'Beef Steak', 'category' => 'main']);

        $response = $this->actingAsUser()
            ->getJson("/api/private/restaurants/{$this->restaurant->id}/menu_items?search=chicken&category=main");

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json('data.items')));
        $this->assertEquals('Grilled Chicken', $response->json('data.items.0.name'));
    }

    public function test_cannot_list_menu_items_of_another_users_restaurant(): void
    {
        $other = Restaurant::factory()->create();

        $this->actingAsUser()
            ->getJson("/api/private/restaurants/{$other->id}/menu_items")
            ->assertStatus(403);
    }

    public function test_can_add_menu_item_to_own_restaurant(): void
    {
        $response = $this->actingAsUser()
            ->postJson("/api/private/restaurants/{$this->restaurant->id}/menu_items", [
                'name' => 'Burger',
                'price' => 12.99,
                'category' => 'main',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => ['name' => 'Burger', 'category' => 'main'],
            ]);

        $this->assertDatabaseHas('menu_items', [
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Burger',
        ]);
    }

    public function test_add_menu_item_requires_name_and_price(): void
    {
        $this->actingAsUser()
            ->postJson("/api/private/restaurants/{$this->restaurant->id}/menu_items", [])
            ->assertStatus(422)
            ->assertJson(['status' => 'failed'])
            ->assertJsonPath('data.name', fn ($v) => !empty($v))
            ->assertJsonPath('data.price', fn ($v) => !empty($v));
    }

    public function test_can_update_menu_item(): void
    {
        $menuItem = MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $response = $this->actingAsUser()
            ->putJson("/api/private/menu_items/{$menuItem->id}", [
                'name' => 'Updated Item',
                'price' => 9.99,
            ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Item']]);

        $this->assertDatabaseHas('menu_items', ['id' => $menuItem->id, 'name' => 'Updated Item']);
    }

    public function test_cannot_update_menu_item_of_another_users_restaurant(): void
    {
        $other = Restaurant::factory()->create();
        $menuItem = MenuItem::factory()->create(['restaurant_id' => $other->id]);

        $this->actingAsUser()
            ->putJson("/api/private/menu_items/{$menuItem->id}", ['name' => 'Hacked'])
            ->assertStatus(403);
    }

    public function test_can_delete_menu_item(): void
    {
        $menuItem = MenuItem::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $this->actingAsUser()
            ->deleteJson("/api/private/menu_items/{$menuItem->id}")
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('menu_items', ['id' => $menuItem->id]);
    }

    public function test_cannot_delete_menu_item_of_another_users_restaurant(): void
    {
        $other = Restaurant::factory()->create();
        $menuItem = MenuItem::factory()->create(['restaurant_id' => $other->id]);

        $this->actingAsUser()
            ->deleteJson("/api/private/menu_items/{$menuItem->id}")
            ->assertStatus(403);
    }
}
