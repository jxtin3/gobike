<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\JsonResponse;

/**
 * The Privacy Policy, Terms & Conditions and Cookie Policy for the RondaGo app, so the app shows
 * the same text admins publish in Admin → Legal instead of a copy of its own. Public: people read
 * the terms before they have an account.
 */
class LegalDocumentController extends Controller
{
    /** The website page of each document, for "Open on website" in the app. */
    private const PAGES = [
        'privacy' => 'privacy-policy',
        'terms' => 'terms',
        'cookies' => 'cookie-policy',
    ];

    public function show(string $key): JsonResponse
    {
        $document = LegalDocument::getByKey($key);

        $sections = collect($document->sections ?? [])
            ->map(fn ($section) => [
                'title' => trim((string) ($section['title'] ?? '')),
                'content' => LegalDocument::toPlainText($section['content'] ?? ''),
            ])
            ->filter(fn ($section) => $section['title'] !== '' || $section['content'] !== '')
            ->values();

        return response()->json([
            'key' => $key,
            'title' => $document->title,
            'last_updated' => $document->last_updated,
            'url' => route(self::PAGES[$key]),
            'sections' => $sections,
        ]);
    }
}
