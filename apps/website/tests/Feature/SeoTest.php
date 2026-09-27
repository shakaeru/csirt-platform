<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Support\Seo;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Meta SEO, data terstruktur, sitemap, robots.txt, dan favicon situs publik. */
class SeoTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = ['/', '/tentang', '/struktur-organisasi', '/berita', '/galeri', '/prestasi', '/kontak'];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake(Post::COVER_DISK);
        Storage::fake(Album::DISK);
    }

    public function test_halaman_utama_punya_judul_dan_deskripsi_unik_satu_h1_canonical_og_dan_alt(): void
    {
        Post::factory()->create(['cover_path' => 'berita/sampul.jpg']);
        Album::factory()->withPhotos()->create();

        $titles = [];
        $descriptions = [];
        foreach (self::PAGES as $path) {
            $xpath = $this->page($path);
            $title = trim($xpath->evaluate('string(//title)'));
            $canonical = $this->attr($xpath, '//link[@rel="canonical"]', 'href');

            $this->assertMatchesRegularExpression('/CSIRT (PCR|Politeknik Caltex Riau)/', $title, "{$path}: judul");
            $this->assertNotSame('', $this->meta($xpath, 'name', 'description'), "{$path}: deskripsi");
            $this->assertSame(1, $xpath->query('//h1')->length, "{$path}: jumlah h1");
            $this->assertSame(Seo::url($path), $canonical, "{$path}: canonical");
            $this->assertSame($canonical, $this->meta($xpath, 'property', 'og:url'));
            $this->assertStringStartsWith(config('app.url'), $this->meta($xpath, 'property', 'og:image'));
            $this->assertNotSame('', $this->meta($xpath, 'property', 'og:title'));
            $this->assertSame('summary_large_image', $this->meta($xpath, 'name', 'twitter:card'));
            $this->assertSame('id', $this->attr($xpath, '//html', 'lang'));
            $this->assertSame(0, $xpath->query('//meta[@name="robots"]')->length, "{$path}: tidak boleh noindex");
            foreach ($xpath->query('//img') as $img) {
                $this->assertTrue($img->hasAttribute('alt'), "{$path}: <img> tanpa alt");
            }

            $titles[$path] = $title;
            $descriptions[$path] = $this->meta($xpath, 'name', 'description');
        }

        $this->assertSame($titles, array_unique($titles), 'judul antarhalaman harus unik');
        $this->assertSame($descriptions, array_unique($descriptions), 'deskripsi antarhalaman harus unik');
    }

    public function test_canonical_hanya_mempertahankan_filter_dan_halaman(): void
    {
        $kegiatan = Category::query()->where('slug', 'kegiatan')->sole();
        Post::factory()->for($kegiatan)->create();

        $filtered = $this->page('/berita?utm_source=instagram&kategori=kegiatan');
        $this->assertSame(Seo::url('berita').'?kategori=kegiatan', $this->attr($filtered, '//link[@rel="canonical"]', 'href'));
        $this->assertSame('Berita & Kegiatan: Kegiatan | CSIRT PCR', trim($filtered->evaluate('string(//title)')));

        $paged = $this->page('/berita?page=2');
        $this->assertSame(Seo::url('berita').'?page=2', $this->attr($paged, '//link[@rel="canonical"]', 'href'));
        $this->assertStringContainsString('(Halaman 2)', $paged->evaluate('string(//title)'));

        // Hasil pencarian: tidak diindeks, canonical ke daftar tanpa kata kunci.
        $search = $this->page('/berita?q=forensik');
        $this->assertSame(Seo::url('berita'), $this->attr($search, '//link[@rel="canonical"]', 'href'));
        $this->assertSame('noindex, follow', $this->meta($search, 'name', 'robots'));

        $this->assertSame(Seo::url('prestasi').'?jenis=ctf', $this->attr($this->page('/prestasi?jenis=ctf'), '//link[@rel="canonical"]', 'href'));
        $this->assertSame(Seo::url('prestasi'), $this->attr($this->page('/prestasi?jenis=ngawur'), '//link[@rel="canonical"]', 'href'));
    }

    public function test_canonical_memakai_app_url_bukan_host_request(): void
    {
        config(['app.url' => 'https://csirt.pcr.ac.id']);

        $xpath = $this->page('http://160.187.144.194/tentang');

        $this->assertSame('https://csirt.pcr.ac.id/tentang', $this->attr($xpath, '//link[@rel="canonical"]', 'href'));
    }

    public function test_tulisan_memakai_og_article_dan_sampulnya(): void
    {
        $post = Post::factory()->create(['title' => 'Workshop Forensik Digital', 'cover_path' => 'berita/sampul.jpg']);
        $post->tags()->attach(Tag::factory()->create(['name' => 'forensik']));

        $xpath = $this->page('/berita/'.$post->slug);

        $this->assertSame('Workshop Forensik Digital | CSIRT PCR', trim($xpath->evaluate('string(//title)')));
        $this->assertSame('article', $this->meta($xpath, 'property', 'og:type'));
        $this->assertSame($post->cover_url, $this->meta($xpath, 'property', 'og:image'));
        $this->assertSame($post->published_at->toIso8601String(), $this->meta($xpath, 'property', 'article:published_time'));
        $this->assertSame('forensik', $this->meta($xpath, 'property', 'article:tag'));
        $this->assertSame('Sampul tulisan: Workshop Forensik Digital', $this->attr($xpath, '//article//img', 'alt'));
    }

    public function test_beranda_memuat_json_ld_organization_dan_website(): void
    {
        $xpath = $this->page('/');
        $data = json_decode($xpath->evaluate('string(//script[@type="application/ld+json"])'), true, flags: JSON_THROW_ON_ERROR);
        [$organization, $website] = $data['@graph'];

        $this->assertSame('https://schema.org', $data['@context']);
        $this->assertSame('Organization', $organization['@type']);
        $this->assertSame('UKM CSIRT Politeknik Caltex Riau', $organization['name']);
        $this->assertContains('CSIRT PCR', $organization['alternateName']);
        $this->assertSame(Seo::url(), $organization['url']);
        $this->assertContains(config('kontak.instagram.url'), $organization['sameAs']);
        $this->assertSame('28265', $organization['address']['postalCode']);
        $this->assertFileExists(public_path('android-chrome-512x512.png'));
        $this->assertSame(Seo::url('android-chrome-512x512.png'), $organization['logo']['url']);
        $this->assertSame('WebSite', $website['@type']);
        $this->assertSame('CSIRT PCR', $website['name']);
        $this->assertSame($organization['@id'], $website['publisher']['@id']);
    }

    public function test_sitemap_hanya_memuat_halaman_yang_terbit(): void
    {
        $published = Post::factory()->create();
        $draft = Post::factory()->draft()->create();
        $album = Album::factory()->withPhotos()->create();
        $emptyAlbum = Album::factory()->create();
        $draftAlbum = Album::factory()->draft()->withPhotos()->create();

        $response = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringStartsWith('application/xml', $response->headers->get('Content-Type'));

        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml, 'XML sitemap tidak valid');
        $locs = array_map('strval', iterator_to_array($xml->xpath('//*[local-name()="loc"]'), false));

        foreach (self::PAGES as $path) {
            $this->assertContains(Seo::url($path), $locs);
        }
        $this->assertContains(Seo::url('berita/'.$published->slug), $locs);
        $this->assertContains(Seo::url('galeri/'.$album->slug), $locs);
        $this->assertNotContains(Seo::url('berita/'.$draft->slug), $locs);
        $this->assertNotContains(Seo::url('galeri/'.$emptyAlbum->slug), $locs);
        $this->assertNotContains(Seo::url('galeri/'.$draftAlbum->slug), $locs);
        $this->assertCount(7 + 2, $locs);
    }

    public function test_robots_txt_tidak_memblokir_halaman_publik_dan_menunjuk_sitemap(): void
    {
        $robots = (string) file_get_contents(public_path('robots.txt'));
        preg_match_all('/^Disallow:\s*(\S*)\s*$/m', $robots, $matches);

        $this->assertStringContainsString('Sitemap: https://csirt.pcr.ac.id/sitemap.xml', $robots);
        foreach ($matches[1] as $blocked) {
            $this->assertNotSame('/', $blocked);
            foreach ([...self::PAGES, '/sitemap.xml', '/berita/contoh', '/galeri/contoh'] as $path) {
                $this->assertFalse($blocked !== '' && $path !== '/' && str_starts_with($path, $blocked), "{$path} terblokir oleh {$blocked}");
            }
        }
    }

    public function test_favicon_dan_ikon_dirujuk_dengan_versi_dan_filenya_ada(): void
    {
        $xpath = $this->page('/');
        $version = '?v='.config('seo.icon_version');

        $this->assertStringEndsWith('favicon.ico'.$version, $this->attr($xpath, '//link[@rel="icon" and contains(@href, "favicon.ico")]', 'href'));
        $this->assertStringEndsWith('apple-touch-icon.png'.$version, $this->attr($xpath, '//link[@rel="apple-touch-icon"]', 'href'));
        $this->assertStringEndsWith('site.webmanifest'.$version, $this->attr($xpath, '//link[@rel="manifest"]', 'href'));

        foreach (['favicon-16x16.png' => 16, 'favicon-32x32.png' => 32, 'apple-touch-icon.png' => 180, 'android-chrome-192x192.png' => 192, 'android-chrome-512x512.png' => 512] as $file => $size) {
            $this->assertSame([$size, $size], array_slice(getimagesize(public_path($file)), 0, 2), $file);
        }

        // favicon.ico: header ICO (reserved 0, type 1) berisi 3 gambar (16, 32, 48).
        $ico = unpack('vreserved/vtype/vcount', (string) file_get_contents(public_path('favicon.ico'), length: 6));
        $this->assertSame(['reserved' => 0, 'type' => 1, 'count' => 3], $ico);

        $manifest = json_decode((string) file_get_contents(public_path('site.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(strtok(ltrim($icon['src'], '/'), '?')));
        }
    }

    public function test_tag_verifikasi_google_search_console(): void
    {
        config(['seo.google_site_verification' => '']);
        $this->assertSame(0, $this->page('/')->query('//meta[@name="google-site-verification"]')->length);

        config(['seo.google_site_verification' => 'kodeContoh_123-abc']);
        $this->assertSame('kodeContoh_123-abc', $this->meta($this->page('/'), 'name', 'google-site-verification'));
    }

    private function page(string $url): DOMXPath
    {
        $html = $this->get($url)->assertOk()->getContent();
        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // tag HTML5 (nav, section, dll.) memicu peringatan libxml
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    private function meta(DOMXPath $xpath, string $attribute, string $name): string
    {
        return $this->attr($xpath, "//meta[@{$attribute}=\"{$name}\"]", 'content');
    }

    private function attr(DOMXPath $xpath, string $query, string $attribute): string
    {
        $node = $xpath->query($query)->item(0);
        $this->assertNotNull($node, "tidak ditemukan: {$query}");

        return $node->getAttribute($attribute);
    }
}
