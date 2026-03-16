<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function actingAsUser(): static
    {
        return $this->actingAs($this->user, 'sanctum');
    }

    public function test_unauthenticated_user_cannot_access_restaurants(): void
    {
        $this->getJson('/api/private/restaurants')
            ->assertStatus(401)
            ->assertJson(['status' => 'failed']);
    }

    public function test_can_list_own_restaurants_with_pagination(): void
    {
        Restaurant::factory()->count(15)->create(['user_id' => $this->user->id]);
        Restaurant::factory()->count(3)->create(); // another user's restaurants

        $response = $this->actingAsUser()->getJson('/api/private/restaurants');

        $response->assertStatus(200)
            ->assertJson(['status' => 'success'])
            ->assertJsonStructure([
                'status', 'message',
                'data' => [
                    'items' => [['id', 'name', 'address']],
                    'meta' => ['per_page', 'has_more', 'next_cursor', 'prev_cursor'],
                ],
            ]);

        $this->assertEquals(10, count($response->json('data.items')));
        $this->assertTrue($response->json('data.meta.has_more'));
    }

    public function test_can_create_a_restaurant(): void
    {
        $response = $this->actingAsUser()->postJson('/api/private/restaurants', [
            'name' => 'My Restaurant',
            'address' => '123 Main St',
            'phone' => '555-1234',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => ['name' => 'My Restaurant'],
            ]);

        $this->assertDatabaseHas('restaurants', [
            'name' => 'My Restaurant',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_create_restaurant_requires_name_and_address(): void
    {
        $this->actingAsUser()->postJson('/api/private/restaurants', [])
            ->assertStatus(422)
            ->assertJson(['status' => 'failed'])
            ->assertJsonPath('data.name', fn ($v) => !empty($v))
            ->assertJsonPath('data.address', fn ($v) => !empty($v));
    }

    public function test_can_show_own_restaurant_with_menu_items(): void
    {
        $restaurant = Restaurant::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAsUser()->getJson("/api/private/restaurants/{$restaurant->id}");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => ['id' => $restaurant->id],
            ])
            ->assertJsonStructure(['data' => ['menu_items']]);
    }

    public function test_cannot_show_another_users_restaurant(): void
    {
        $other = Restaurant::factory()->create();

        $this->actingAsUser()->getJson("/api/private/restaurants/{$other->id}")
            ->assertStatus(403)
            ->assertJson(['status' => 'failed']);
    }

    public function test_can_update_own_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAsUser()->putJson("/api/private/restaurants/{$restaurant->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200)
            ->assertJson(['data' => ['name' => 'Updated Name']]);

        $this->assertDatabaseHas('restaurants', ['id' => $restaurant->id, 'name' => 'Updated Name']);
    }

    public function test_cannot_update_another_users_restaurant(): void
    {
        $other = Restaurant::factory()->create();

        $this->actingAsUser()->putJson("/api/private/restaurants/{$other->id}", ['name' => 'Hacked'])
            ->assertStatus(403);
    }

    public function test_can_delete_own_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create(['user_id' => $this->user->id]);

        $this->actingAsUser()->deleteJson("/api/private/restaurants/{$restaurant->id}")
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('restaurants', ['id' => $restaurant->id]);
    }

    public function test_cannot_delete_another_users_restaurant(): void
    {
        $other = Restaurant::factory()->create();

        $this->actingAsUser()->deleteJson("/api/private/restaurants/{$other->id}")
            ->assertStatus(403);
    }
}
