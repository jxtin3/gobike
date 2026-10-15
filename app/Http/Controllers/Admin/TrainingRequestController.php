<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Http\Request;

class TrainingRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = TrainingRequest::latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($tier = $request->input('tier')) {
            $query->where('tier', $tier);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.operations.training-requests.index', [
            'requests'      => $requests,
            'totalCount'    => TrainingRequest::count(),
            'pendingCount'  => TrainingRequest::where('status', 'pending')->count(),
            'contactedCount'=> TrainingRequest::where('status', 'contacted')->count(),
        ]);
    }

    public function updateStatus(TrainingRequest $trainingRequest, Request $request)
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,contacted,completed,cancelled'],
        ]);

        $trainingRequest->update($data);

        return back()->with('success', 'Training request status updated.');
    }
}
