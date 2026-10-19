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
    public function editPrivacy(): View
    {
        $document = LegalDocument::getByKey('privacy');
        $activeDoc = 'privacy';
        return view('admin.operations.legal.privacy', compact('document', 'activeDoc'));
    }

    public function updatePrivacy(Request $request): JsonResponse|RedirectResponse
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
            return !empty(trim($section['title'] ?? '')) || !empty(trim($section['content'] ?? ''));
        }));

        $lastUpdated = !empty(trim($data['last_updated'] ?? '')) ? $data['last_updated'] : date('F d, Y');

        $document = LegalDocument::updateOrCreate(
            ['key' => 'privacy'],
            [
                'title' => $data['title'],
                'last_updated' => $lastUpdated,
                'sections' => $sections,
            ]
        );

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Privacy Policy saved successfully.',
                'last_updated' => $document->last_updated,
            ]);
        }

        return redirect()->route('admin.operations.legal.privacy')
            ->with('success', 'Privacy Policy updated successfully.');
    }

    public function editTerms(): View
    {
        $document = LegalDocument::getByKey('terms');
        $activeDoc = 'terms';
        return view('admin.operations.legal.terms', compact('document', 'activeDoc'));
    }

    public function updateTerms(Request $request): JsonResponse|RedirectResponse
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
            return !empty(trim($section['title'] ?? '')) || !empty(trim($section['content'] ?? ''));
        }));

        $lastUpdated = !empty(trim($data['last_updated'] ?? '')) ? $data['last_updated'] : date('F d, Y');

        $document = LegalDocument::updateOrCreate(
            ['key' => 'terms'],
            [
                'title' => $data['title'],
                'last_updated' => $lastUpdated,
                'sections' => $sections,
            ]
        );

        if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Terms & Conditions saved successfully.',
                'last_updated' => $document->last_updated,
            ]);
        }

        return redirect()->route('admin.operations.legal.terms')
            ->with('success', 'Terms & Conditions updated successfully.');
    }
}
