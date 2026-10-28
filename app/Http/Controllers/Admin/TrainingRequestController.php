<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainingRequestController extends Controller
{
    private const WHEN = ['upcoming', 'today', 'past'];

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

        // Training date: upcoming ones are listed soonest first, so the team sees what is next.
        $when = in_array($request->input('when'), self::WHEN, true) ? $request->input('when') : null;
        match ($when) {
            'upcoming' => $query->reorder()->whereDate('preferred_date', '>=', today())->orderBy('preferred_date')->orderBy('id'),
            'today' => $query->whereDate('preferred_date', today()),
            'past' => $query->whereDate('preferred_date', '<', today()),
            default => null,
        };

        $requests = $query->paginate(20)->withQueryString();

        return view('admin.operations.training-requests.index', [
            'requests' => $requests,
            'when' => $when,
            // Plans on offer, plus retired ones only while old requests still use them.
            'tiers' => array_filter(
                TrainingRequest::TIERS,
                fn ($tier) => in_array($tier, TrainingRequest::OFFERED, true) || TrainingRequest::where('tier', $tier)->exists(),
                ARRAY_FILTER_USE_KEY,
            ),
            'totalCount' => TrainingRequest::count(),
            'pendingCount' => TrainingRequest::where('status', 'pending')->count(),
            'upcomingCount' => TrainingRequest::whereIn('status', ['pending', 'contacted'])->whereDate('preferred_date', '>=', today())->count(),
        ]);
    }

    public function updateStatus(TrainingRequest $trainingRequest, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(TrainingRequest::STATUSES)],
        ]);

        $trainingRequest->update($data);

        return back()->with('success', "Request from {$trainingRequest->name} marked {$trainingRequest->status_label}.");
    }

    public function destroy(TrainingRequest $trainingRequest): RedirectResponse
    {
        $name = $trainingRequest->name;
        $trainingRequest->delete();

        return back()->with('success', "Training request from {$name} deleted.");
    }
}
