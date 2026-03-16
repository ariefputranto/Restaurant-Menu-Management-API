<?php

namespace App\Traits;

use App\Models\Restaurant;
use Illuminate\Http\Request;

trait AuthorizesOwnership
{
    protected function authorizeOwnership(Request $request, Restaurant $restaurant): void
    {
        if ($restaurant->user_id !== $request->user()->id) {
            abort(403);
        }
    }
}
