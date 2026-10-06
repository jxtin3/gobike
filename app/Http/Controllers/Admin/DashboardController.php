<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportAnalytics;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, ReportAnalytics $analytics)
    {
        return view('admin.dashboard', [
            ...$analytics->forRequest($request),
            'dashboardEntrance' => (bool) $request->session()->pull('admin_dashboard_entrance', false),
        ]);
    }
}
