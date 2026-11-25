<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\LiveMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The resident's live map: the GoBikers of their town. Their own position never leaves their phone. */
class ResidentMapController extends Controller
{
    /**
     * Every GoBiker of the resident's town whose app is open.
     *
     * Residents are not plotted for anyone, GoBikers included, so the app has nothing to
     * upload: it draws the resident's own position straight from the phone.
     */
    public function map(Request $request): JsonResponse
    {
        $barangay = $request->user()->barangay;

        return response()->json([
            'barangay' => $barangay,
            'town' => LiveMap::townOf($barangay),
            'gobikers' => LiveMap::onlineGobikers($barangay, $request->user()->id),
        ]);
    }
}
