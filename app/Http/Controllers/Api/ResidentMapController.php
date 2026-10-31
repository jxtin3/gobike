<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\LiveMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** The resident's live map: the GoBikers near them. Their own position never leaves their phone. */
class ResidentMapController extends Controller
{
    /**
     * Online GoBikers in the resident's barangay.
     *
     * Residents are not plotted for anyone, so the app has nothing to upload: it draws the
     * resident's own position straight from the phone.
     */
    public function map(Request $request): JsonResponse
    {
        $barangay = $request->user()->barangay;

        return response()->json([
            'barangay' => $barangay,
            'gobikers' => LiveMap::onlineGobikers($barangay),
        ]);
    }
}
