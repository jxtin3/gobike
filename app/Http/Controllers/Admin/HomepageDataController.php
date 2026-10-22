<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ImpactStat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageDataController extends Controller
{
    private const SECTIONS = ['impact', 'highlight', 'badge'];

    public function index(): View
    {
        $stats = ImpactStat::orderBy('sort_order')->orderBy('id')->get()->groupBy('section');

        return view('admin.operations.homepage-data.index', [
            'impactStats' => $stats->get('impact', collect()),
            'impactHighlights' => $stats->get('highlight', collect()),
            'impactBadge' => $stats->get('badge', collect()),
        ]);
    }

    public function storeImpactStat(Request $request): RedirectResponse
    {
        $section = in_array($request->input('section'), self::SECTIONS, true) ? $request->input('section') : 'impact';

        // A separate error bag per form, so a mistake shows next to the form it came from.
        $data = $request->validateWithBag("create_{$section}", [
            'section' => ['required', 'in:'.implode(',', self::SECTIONS)],
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:40'],
        ]);
        $data['sort_order'] = (ImpactStat::where('section', $data['section'])->max('sort_order') ?? -1) + 1;

        ImpactStat::create($data);

        return redirect()->route('admin.operations.homepage-data.index')
            ->with('success', 'Impact statistic added.');
    }

    public function updateImpactStat(Request $request, ImpactStat $impactStat): RedirectResponse
    {
        $impactStat->update($request->validateWithBag("stat_{$impactStat->id}", [
            'label' => ['required', 'string', 'max:100'],
            'value' => ['required', 'string', 'max:40'],
        ]));

        return redirect()->route('admin.operations.homepage-data.index')
            ->with('success', 'Impact statistic updated.');
    }

    public function destroyImpactStat(ImpactStat $impactStat): RedirectResponse
    {
        $impactStat->delete();

        return redirect()->route('admin.operations.homepage-data.index')
            ->with('success', 'Impact statistic removed.');
    }
}
