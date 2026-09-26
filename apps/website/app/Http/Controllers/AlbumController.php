<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\View\View;

class AlbumController extends Controller
{
    /** Galeri: album terbit yang sudah berisi foto, kegiatan terbaru dulu. */
    public function index(): View
    {
        $albums = Album::query()
            ->published()
            ->has('photos')
            ->with('cover')
            ->withCount('photos')
            ->latest('event_date')
            ->latest('published_at')
            ->paginate(12);

        return view('galeri.index', ['albums' => $albums]);
    }

    public function show(Album $album): View
    {
        // Draft dan terjadwal tidak boleh bisa diintip lewat alamatnya.
        abort_unless($album->isPublished(), 404);

        $album->load(['photos' => fn ($query) => $query->ordered(), 'post']);

        return view('galeri.show', [
            'album' => $album,
            // Tautan ke tulisan hanya bila tulisannya juga sudah terbit.
            'post' => $album->post?->isPublished() ? $album->post : null,
        ]);
    }
}
