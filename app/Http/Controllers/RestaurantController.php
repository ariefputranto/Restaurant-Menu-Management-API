<?php

namespace App\Http\Controllers;

use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use App\Traits\AuthorizesOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    use AuthorizesOwnership;

    public function index(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 10), 100);
        $restaurants = $request->user()->restaurants()->orderBy('id')->cursorPaginate($limit);

        return $this->paginated('Restaurants retrieved successfully.', $restaurants, RestaurantResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'opening_hours' => 'nullable|string|max:255',
        ]);

        $restaurant = $request->user()->restaurants()->create($validated);

        return $this->success('Restaurant created successfully.', new RestaurantResource($restaurant), 201);
    }

    public function show(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorizeOwnership($request, $restaurant);

        $restaurant->load('menuItems');

        return $this->success('Restaurant retrieved successfully.', new RestaurantResource($restaurant));
    }

    public function update(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorizeOwnership($request, $restaurant);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'opening_hours' => 'nullable|string|max:255',
        ]);

        $restaurant->update($validated);

        return $this->success('Restaurant updated successfully.', new RestaurantResource($restaurant));
    }

    public function destroy(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorizeOwnership($request, $restaurant);

        $restaurant->delete();

        return $this->success('Restaurant deleted successfully.');
    }
}
