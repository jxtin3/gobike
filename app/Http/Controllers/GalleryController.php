<?php

namespace App\Http\Controllers;

use App\Models\Picture;
use Illuminate\Contracts\View\View;

class GalleryController extends Controller
{
    public function __invoke(): View
    {
        $pictures = Picture::latest()->paginate(12);

        return view('join-the-movement.gallery', compact('pictures'));
    }
}
