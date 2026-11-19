<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminLiveFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Polled by every admin page (resources/js/admin/live-updates.js) so sidebar counts stay current and new
 * sign-ups, applications and messages show up without a reload. Hostinger shared hosting has no websockets.
 */
class LiveUpdatesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $after = $request->query('after');

        return response()
            ->json(AdminLiveFeed::snapshot(is_array($after) ? $after : []))
            ->header('Cache-Control', 'no-store');
    }
}
