<?php

namespace App\Http\Controllers;

use App\Enums\MenuItemCategory;
use App\Http\Resources\MenuItemResource;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Traits\AuthorizesOwnership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuItemController extends Controller
{
    use AuthorizesOwnership;

    public function index(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorizeOwnership($request, $restaurant);

        $query = $restaurant->menuItems();

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        $limit = min((int) $request->input('limit', 10), 100);

        return $this->paginated('Menu items retrieved successfully.', $query->orderBy('id')->cursorPaginate($limit), MenuItemResource::class);
    }

    public function store(Request $request, Restaurant $restaurant): JsonResponse
    {
        $this->authorizeOwnership($request, $restaurant);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => ['nullable', Rule::enum(MenuItemCategory::class)],
            'is_available' => 'boolean',
        ]);

        $menuItem = $restaurant->menuItems()->create($validated);

        return $this->success('Menu item created successfully.', new MenuItemResource($menuItem), 201);
    }

    public function update(Request $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorizeOwnership($request, $menuItem->restaurant);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'category' => ['nullable', Rule::enum(MenuItemCategory::class)],
            'is_available' => 'boolean',
        ]);

        $menuItem->update($validated);

        return $this->success('Menu item updated successfully.', new MenuItemResource($menuItem));
    }

    public function destroy(Request $request, MenuItem $menuItem): JsonResponse
    {
        $this->authorizeOwnership($request, $menuItem->restaurant);

        $menuItem->delete();

        return $this->success('Menu item deleted successfully.');
    }
}
