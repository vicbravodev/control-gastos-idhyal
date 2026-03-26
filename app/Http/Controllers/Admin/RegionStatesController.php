<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\JsonResponse;

class RegionStatesController extends Controller
{
    public function __invoke(Region $region): JsonResponse
    {
        $this->authorize('view', $region);

        $states = $region->states()
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json(['data' => $states]);
    }
}
