<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\RestaurantController;
use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::prefix('public')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->prefix('private')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('restaurants', [RestaurantController::class, 'index']);
    Route::post('restaurants', [RestaurantController::class, 'store']);
    Route::get('restaurants/{restaurant}', [RestaurantController::class, 'show']);
    Route::put('restaurants/{restaurant}', [RestaurantController::class, 'update']);
    Route::delete('restaurants/{restaurant}', [RestaurantController::class, 'destroy']);

    Route::get('restaurants/{restaurant}/menu_items', [MenuItemController::class, 'index']);
    Route::post('restaurants/{restaurant}/menu_items', [MenuItemController::class, 'store']);

    Route::put('menu_items/{menuItem}', [MenuItemController::class, 'update']);
    Route::delete('menu_items/{menuItem}', [MenuItemController::class, 'destroy']);
});
