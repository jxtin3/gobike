<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomePartner;
use App\Models\ImpactStat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomepageDataController extends Controller
{
    public function index(): View
    {
        return view('admin.operations.homepage-data.index', [
            'partners' => HomePartner::orderBy('sort_order')->orderBy('id')->get(),
            'impactStats' => ImpactStat::where('section', 'impact')->orderBy('sort_order')->orderBy('id')->get(),
            'impactHighlights' => ImpactStat::where('section', 'highlight')->orderBy('sort_order')->orderBy('id')->get(),
            'impactBadge' => ImpactStat::where('section', 'badge')->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }

    public function storePartner(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'abbreviation' => ['required', 'string', 'max:40'],
        ]);
        $data['sort_order'] = (HomePartner::max('sort_order') ?? -1) + 1;

        HomePartner::create($data);

        return redirect()->route('admin.operations.homepage-data.index')
            ->with('success', 'Partnership added.');
    }

    public function updatePartner(Request $request, HomePartner $partner): RedirectResponse
    {
        $partner->update($request->validate([
            'name' => ['required', 'string', 'max:120'],
            'abbreviation' => ['required', 'string', 'max:40'],
        ]));

        return redirect()->route('admin.operations.homepage-data.index')
            ->with('success', 'Partnership updated.');
    }

    public function destroyPartner(HomePartner $partner): RedirectResponse
    {
        $partner->delete();

        return redirect()->route('admin.operations.homepage-data.index')
            ->with('success', 'Partnership removed.');
    }

    public function storeImpactStat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'section' => ['required', 'in:impact,highlight,badge'],
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
        $impactStat->update($request->validate([
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
