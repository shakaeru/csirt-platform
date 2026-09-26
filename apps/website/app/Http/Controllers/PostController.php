<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    /**
     * Daftar Berita & Kegiatan — hanya yang sudah terbit. Filter: ?kategori=<slug>, ?tag=<slug>, ?q=<kata kunci>.
     */
    public function index(Request $request): View
    {
        $search = Str::limit(trim((string) $request->query('q', '')), 100, '');
        $category = $request->filled('kategori') ? Category::query()->where('slug', $request->query('kategori'))->first() : null;
        $tag = $request->filled('tag') ? Tag::query()->where('slug', $request->query('tag'))->first() : null;

        $posts = Post::query()
            ->published()
            ->with(['category', 'tags'])
            ->when($category, fn ($query) => $query->whereBelongsTo($category))
            ->when($tag, fn ($query) => $query->whereHas('tags', fn ($tags) => $tags->whereKey($tag->getKey())))
            ->when($search !== '', fn ($query) => $query->whereAny(['title', 'excerpt', 'content'], 'like', "%{$search}%"))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return view('berita.index', [
            'posts' => $posts,
            // Hanya kategori yang punya tulisan terbit, supaya filter tidak berujung halaman kosong.
            'categories' => Category::query()->whereHas('posts', fn ($query) => $query->published())->orderBy('sort_order')->get(),
            'category' => $category,
            'tag' => $tag,
            'search' => $search,
        ]);
    }

    public function show(Post $post): View
    {
        // Draft dan terjadwal tidak boleh bisa diintip lewat alamatnya.
        abort_unless($post->isPublished(), 404);

        return view('berita.show', ['post' => $post->load(['category', 'tags'])]);
    }
}
