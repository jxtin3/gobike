<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function editPrivacy(): View
    {
        $document = LegalDocument::getByKey('privacy');
        return view('admin.operations.legal.privacy', compact('document'));
    }

    public function updatePrivacy(Request $request): RedirectResponse
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

        LegalDocument::updateOrCreate(
            ['key' => 'privacy'],
            [
                'title' => $data['title'],
                'last_updated' => $data['last_updated'] ?: date('F d, Y'),
                'sections' => $sections,
            ]
        );

        return redirect()->route('admin.operations.legal.privacy')
            ->with('success', 'Privacy Policy updated successfully.');
    }

    public function editTerms(): View
    {
        $document = LegalDocument::getByKey('terms');
        return view('admin.operations.legal.terms', compact('document'));
    }

    public function updateTerms(Request $request): RedirectResponse
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

        LegalDocument::updateOrCreate(
            ['key' => 'terms'],
            [
                'title' => $data['title'],
                'last_updated' => $data['last_updated'] ?: date('F d, Y'),
                'sections' => $sections,
            ]
        );

        return redirect()->route('admin.operations.legal.terms')
            ->with('success', 'Terms & Conditions updated successfully.');
    }
}
