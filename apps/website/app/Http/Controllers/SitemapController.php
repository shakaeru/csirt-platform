<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Post;
use App\Support\Seo;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use XMLWriter;

class SitemapController extends Controller
{
    /**
     * /sitemap.xml untuk mesin pencari: halaman utama, tulisan terbit, dan album terbit yang
     * berisi foto (kriteria yang sama dengan halaman publiknya). URL dari APP_URL, sama dengan
     * canonical. Halaman filter dan pencarian sengaja tidak dimasukkan.
     */
    public function __invoke(): Response
    {
        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $latestPost = Post::query()->published()->max('updated_at');
        $latestAlbum = Album::query()->published()->has('photos')->max('updated_at');

        $pages = [
            '/' => null,
            'tentang' => null,
            'struktur-organisasi' => null,
            'berita' => $latestPost,
            'galeri' => $latestAlbum,
            'prestasi' => null,
            'kontak' => null,
        ];
        foreach ($pages as $path => $lastModified) {
            $this->url($xml, Seo::url($path), $lastModified);
        }

        Post::query()->published()->latest('published_at')->select(['slug', 'published_at', 'updated_at'])
            ->each(fn (Post $post) => $this->url($xml, Seo::url('berita/'.$post->slug), max($post->updated_at, $post->published_at)));

        Album::query()->published()->has('photos')->latest('event_date')->select(['id', 'slug', 'published_at', 'updated_at'])
            ->each(fn (Album $album) => $this->url($xml, Seo::url('galeri/'.$album->slug), max($album->updated_at, $album->published_at)));

        $xml->endElement();
        $xml->endDocument();

        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function url(XMLWriter $xml, string $loc, Carbon|string|null $lastModified): void
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $loc);
        if ($lastModified !== null) {
            $xml->writeElement('lastmod', Carbon::parse($lastModified)->toAtomString());
        }
        $xml->endElement();
    }
}
