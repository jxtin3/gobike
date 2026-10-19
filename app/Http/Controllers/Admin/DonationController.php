<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;

class DonationController extends Controller
{
    public function index(Request $request)
    {
        $query = Donation::latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('org_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($method = $request->input('method')) {
            $query->where('payment_method', $method);
        }

        $donations = $query->paginate(20)->withQueryString();

        return view('admin.operations.donations.index', [
            'donations'   => $donations,
            'totalPaid'   => Donation::where('status', 'paid')->sum('amount_usd'),
            'totalCount'  => Donation::where('status', 'paid')->count(),
            'pendingCount'=> Donation::where('status', 'pending')->count(),
        ]);
    }
}
