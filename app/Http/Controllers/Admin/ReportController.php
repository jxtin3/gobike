<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Services\ReportAnalytics;

class ReportController extends Controller
{
    public function index(Request $request, ReportAnalytics $analytics): View
    {
        return view('admin.operations.reports.index', $analytics->forRequest($request));
    }
}
