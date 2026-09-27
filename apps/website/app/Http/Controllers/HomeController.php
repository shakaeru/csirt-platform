<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Beranda. Foto besar di hero = sampul album galeri terbit terbaru (kriteria sama dengan
     * halaman Galeri); tanpa album, hero tampil satu kolom.
     */
    public function __invoke(): View
    {
        return view('home', [
            'featuredAlbum' => Album::query()
                ->published()
                ->has('photos')
                ->with('cover')
                ->withCount('photos')
                ->latest('event_date')
                ->latest('published_at')
                ->first(),
        ]);
    }
}
