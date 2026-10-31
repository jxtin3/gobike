<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Patient;
use App\Models\SosRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * What residents thought of the GoBikers who helped them: 1-5 stars and a comment, left after an
 * emergency was resolved or a check-up was recorded.
 *
 * Read-only. The ratings come from the residents' app and are evidence of how the service went,
 * so an admin can look at them but not change them.
 */
class FeedbackController extends Controller
{
    private const TABS = ['all', 'sos', 'checkup', 'skipped'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('kind'), self::TABS, true) ? $request->query('kind') : 'all';
        $search = trim((string) $request->query('search', ''));

        $ratings = Feedback::with('resident:id,name,barangay,photo_path', 'gobiker:id,name,barangay,photo_path')
            ->when(in_array($tab, ['sos', 'checkup'], true), fn ($q) => $q->where('kind', $tab))
            ->when($tab === 'skipped', fn ($q) => $q->whereNull('rating'))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('resident', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('gobiker', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhere('comment', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.feedback.index', [
            'ratings' => $ratings,
            'tab' => $tab,
            'links' => $this->subjectLinks($ratings->getCollection()),
            'counts' => [
                'all' => Feedback::count(),
                'sos' => Feedback::where('kind', 'sos')->count(),
                'checkup' => Feedback::where('kind', 'checkup')->count(),
                'skipped' => Feedback::whereNull('rating')->count(),
            ],
            'figures' => [
                'rated' => Feedback::whereNotNull('rating')->count(),
                'average' => Feedback::whereNotNull('rating')->avg('rating'),
                'skipped' => Feedback::whereNull('rating')->count(),
                'gobikers' => Feedback::whereNotNull('rating')->distinct()->count('gobiker_id'),
            ],
        ]);
    }

    /**
     * Where each rating came from, so the admin can open the emergency or the check-up itself.
     *
     * "kind" says which table subject_id points at; there is no morph map, so the ids are looked
     * up per kind, in two queries rather than one per row.
     *
     * @param  Collection<int, Feedback>  $ratings
     * @return array<int, string> keyed by feedback id
     */
    private function subjectLinks($ratings): array
    {
        $byKind = $ratings->groupBy('kind')->map(fn ($rows) => $rows->pluck('subject_id')->all());

        $sosIds = SosRequest::whereIn('id', $byKind['sos'] ?? [])->pluck('id')->all();
        $patientIds = Patient::whereIn('id', $byKind['checkup'] ?? [])->pluck('id')->all();

        $links = [];
        foreach ($ratings as $rating) {
            $links[$rating->id] = match (true) {
                $rating->kind === 'sos' && in_array($rating->subject_id, $sosIds) => route('admin.operations.sos.show', $rating->subject_id),
                $rating->kind === 'checkup' && in_array($rating->subject_id, $patientIds) => route('admin.operations.patients.show', $rating->subject_id),
                // The emergency or check-up was deleted; the rating of the GoBiker still stands.
                default => '',
            };
        }

        return $links;
    }
}
