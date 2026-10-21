<?php

namespace App\Http\Controllers;

use App\Models\LegalDocument;
use Illuminate\Contracts\View\View;

class LegalDocumentController extends Controller
{
    public function privacy(): View
    {
        $document = LegalDocument::getByKey('privacy');

        return view('pages.privacy', compact('document'));
    }

    public function terms(): View
    {
        $document = LegalDocument::getByKey('terms');

        return view('pages.terms', compact('document'));
    }
}
