<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\City;
use App\Models\Municipality;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /** List all provinces. */
    public function provinces(): JsonResponse
    {
        return response()->json([
            'provinces' => Province::orderBy('name')->get(['id', 'name', 'region', 'code']),
        ]);
    }

    /** List cities in a province. */
    public function cities(Request $request): JsonResponse
    {
        $provinceId = $request->query('province_id');

        $query = City::with('province:id,name')->orderBy('name');
        if ($provinceId) {
            $query->where('province_id', $provinceId);
        }

        return response()->json([
            'cities' => $query->get(['id', 'name', 'province_id', 'code']),
        ]);
    }

    /** List municipalities in a city. */
    public function municipalities(Request $request): JsonResponse
    {
        $cityId = $request->query('city_id');

        $query = Municipality::with('city:id,name')->orderBy('name');
        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        return response()->json([
            'municipalities' => $query->get(['id', 'name', 'city_id', 'code']),
        ]);
    }

    /** List barangays in a municipality. */
    public function barangays(Request $request): JsonResponse
    {
        $municipalityId = $request->query('municipality_id');

        $query = Barangay::with([
            'municipality:id,name',
            'city:id,name',
            'province:id,name',
        ])->orderBy('sort_order');

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        return response()->json([
            'barangays' => $query->get(['id', 'name', 'municipality_id', 'city_id', 'province_id', 'sort_order']),
        ]);
    }
}