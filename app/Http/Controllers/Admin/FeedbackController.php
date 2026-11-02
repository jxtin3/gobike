<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Patient;
use App\Models\SosRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What residents thought of the GoBikers who helped them: 1-5 stars and a comment, left after an
 * emergency was resolved or a check-up was recorded.
 *
 * The list shows each GoBiker once, with how they are rated overall; a GoBiker's own page shows
 * every resident's feedback about them.
 *
 * Read-only. The ratings come from the residents' app and are evidence of how the service went,
 * so an admin can look at them but not change them.
 */
class FeedbackController extends Controller
{
    /** The list counts GoBikers, and a skip says nothing about one, so it has no tab here. */
    private const LIST_TABS = ['all', 'sos', 'checkup'];

    private const TABS = ['all', 'sos', 'checkup', 'skipped'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('kind'), self::LIST_TABS, true) ? $request->query('kind') : 'all';
        $search = trim((string) $request->query('search', ''));

        $gobikers = Feedback::query()
            ->when(in_array($tab, ['sos', 'checkup'], true), fn ($q) => $q->where('kind', $tab))
            ->when($search !== '', fn ($q) => $q->whereHas('gobiker', fn ($user) => $user->where(
                fn ($match) => $match->where('name', 'like', "%{$search}%")->orWhere('barangay', 'like', "%{$search}%")
            )))
            ->selectRaw('gobiker_id, AVG(rating) as average, COUNT(rating) as rated, COUNT(*) - COUNT(rating) as skipped, MAX(created_at) as last_at')
            ->groupBy('gobiker_id')
            // Best first, like the ratings themselves: an admin opens this to see how the service is
            // going. Someone with only skips has no stars to average, so they sit last instead of
            // being treated as the worst. Ties go to the one rated more, then the more recent, and
            // the id breaks any remaining tie so the order holds across pages.
            ->orderByRaw('CASE WHEN COUNT(rating) = 0 THEN 1 ELSE 0 END')
            ->orderByRaw('AVG(rating) DESC')
            ->orderByRaw('COUNT(rating) DESC')
            ->orderByRaw('MAX(created_at) DESC')
            ->orderByRaw('gobiker_id')
            ->paginate(15)
            ->withQueryString();

        // The rows are totals, not ratings, so the GoBikers come in one query of their own.
        $users = User::whereIn('id', $gobikers->getCollection()->pluck('gobiker_id')->filter())
            ->get(['id', 'name', 'barangay', 'photo_path'])
            ->keyBy('id');

        return view('admin.operations.feedback.index', [
            'gobikers' => $gobikers,
            'users' => $users,
            'tab' => $tab,
            'counts' => [
                'all' => $this->gobikersRated(),
                'sos' => $this->gobikersRated('sos'),
                'checkup' => $this->gobikersRated('checkup'),
            ],
            'figures' => [
                'rated' => Feedback::whereNotNull('rating')->count(),
                'average' => Feedback::whereNotNull('rating')->avg('rating'),
                'skipped' => Feedback::whereNull('rating')->count(),
                'gobikers' => Feedback::whereNotNull('rating')->distinct()->count('gobiker_id'),
            ],
        ]);
    }

    /** Everything the residents said about one GoBiker. */
    public function show(Request $request, User $gobiker)
    {
        // A GoBiker, or someone who was one and still has feedback: not any account at all.
        abort_unless(
            $gobiker->role === 'GoBiker' || Feedback::where('gobiker_id', $gobiker->id)->exists(),
            404,
        );

        $tab = in_array($request->query('kind'), self::TABS, true) ? $request->query('kind') : 'all';
        $search = trim((string) $request->query('search', ''));
        $mine = fn () => Feedback::where('gobiker_id', $gobiker->id);

        $ratings = $mine()->with('resident:id,name,barangay,photo_path')
            ->when(in_array($tab, ['sos', 'checkup'], true), fn ($q) => $q->where('kind', $tab))
            ->when($tab === 'skipped', fn ($q) => $q->whereNull('rating'))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('resident', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhere('comment', 'like', "%{$search}%");
                });
            })
            // Best first, worst last: a skip carries no stars at all, so it sits below the one-star
            // ratings instead of being treated as the worst. Ratings that tie stay newest-first,
            // and the id breaks any remaining tie so the order holds across pages.
            ->orderByRaw('CASE WHEN rating IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('rating')
            ->latest()
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.feedback.show', [
            'gobiker' => $gobiker,
            'ratings' => $ratings,
            'tab' => $tab,
            'links' => $this->subjectLinks($ratings->getCollection()),
            'counts' => [
                'all' => $mine()->count(),
                'sos' => $mine()->where('kind', 'sos')->count(),
                'checkup' => $mine()->where('kind', 'checkup')->count(),
                'skipped' => $mine()->whereNull('rating')->count(),
            ],
            'figures' => [
                'rated' => $mine()->whereNotNull('rating')->count(),
                'average' => $mine()->whereNotNull('rating')->avg('rating'),
                'skipped' => $mine()->whereNull('rating')->count(),
            ],
        ]);
    }

    /** How many GoBikers have feedback, for a kind of it or for all. A deleted GoBiker counts as one. */
    private function gobikersRated(?string $kind = null): int
    {
        return Feedback::query()
            ->when($kind, fn ($q) => $q->where('kind', $kind))
            ->distinct()
            ->count(DB::raw('COALESCE(gobiker_id, 0)'));
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
