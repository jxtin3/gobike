<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    /** Display name of each editable legal page, used in flash and JSON messages. */
    private const LABELS = [
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms & Conditions',
        'cookies' => 'Cookie Policy',
    ];

    public function editPrivacy(): View
    {
        return $this->edit('privacy');
    }

    public function updatePrivacy(Request $request): JsonResponse|RedirectResponse
    {
        return $this->update($request, 'privacy');
    }

    public function editTerms(): View
    {
        return $this->edit('terms');
    }

    public function updateTerms(Request $request): JsonResponse|RedirectResponse
    {
        return $this->update($request, 'terms');
    }

    public function editCookies(): View
    {
        return $this->edit('cookies');
    }

    public function updateCookies(Request $request): JsonResponse|RedirectResponse
    {
        return $this->update($request, 'cookies');
    }

    private function edit(string $key): View
    {
        return view("admin.operations.legal.{$key}", [
            'document' => LegalDocument::getByKey($key),
            'activeDoc' => $key,
        ]);
    }

    private function update(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'last_updated' => ['nullable', 'string', 'max:50'],
            'sections' => ['nullable', 'array'],
            'sections.*.title' => ['nullable', 'string', 'max:255'],
            'sections.*.content' => ['nullable', 'string'],
        ]);

        // Filter out empty sections
        $sections = array_values(array_filter($data['sections'] ?? [], function ($section) {
            return ! empty(trim($section['title'] ?? '')) || ! empty(trim($section['content'] ?? ''));
        }));

        $lastUpdated = ! empty(trim($data['last_updated'] ?? '')) ? $data['last_updated'] : date('F d, Y');

        $document = LegalDocument::updateOrCreate(
            ['key' => $key],
            [
                'title' => $data['title'],
                'last_updated' => $lastUpdated,
                'sections' => $sections,
            ]
        );

        $label = self::LABELS[$key];

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "{$label} saved successfully.",
                'last_updated' => $document->last_updated,
            ]);
        }

        return redirect()->route("admin.operations.legal.{$key}")
            ->with('success', "{$label} updated successfully.");
    }
}
