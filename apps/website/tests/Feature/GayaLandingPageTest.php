<?php

namespace Tests\Feature;

use App\Models\Album;
use DOMDocument;
use DOMXPath;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTestImages;
use Tests\TestCase;

/**
 * Pola "AI-design-slop" yang sudah dibersihkan dari landing page (tugas 27 September 2026):
 * font bawaan template AI, hero rata tengah, dan label section huruf kapital berjarak huruf.
 */
class GayaLandingPageTest extends TestCase
{
    use CreatesTestImages, RefreshDatabase;

    private const SLOP_FONTS = '/\b(Inter|Geist|Space Grotesk)\b/i';

    public function test_font_stack_tidak_memakai_font_bawaan_template_ai(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));
        preg_match('/--font-sans:([^;]+);/', $css, $sans);
        preg_match('/--font-display:([^;]+);/', $css, $display);

        $this->assertStringStartsWith('-apple-system', trim($sans[1] ?? ''), 'teks memakai font sistem');
        $this->assertStringContainsString('IBM Plex Sans Condensed', $display[1] ?? '');
        $this->assertDoesNotMatchRegularExpression(self::SLOP_FONTS, $sans[1].$display[1]);
        $this->assertDoesNotMatchRegularExpression(self::SLOP_FONTS, Filament::getPanel('admin')->getFontFamily());

        // Hasil build (bila ada): tema Flowbite tidak boleh lagi menyisipkan Inter.
        foreach (glob(public_path('build/assets/app-*.css')) ?: [] as $built) {
            $this->assertDoesNotMatchRegularExpression(self::SLOP_FONTS, (string) file_get_contents($built), basename($built));
        }
    }

    /** Filament core selalu mendaftarkan font Inter; AssetManagerWithoutInter menyaringnya. */
    public function test_panel_admin_tidak_memasang_font_inter(): void
    {
        $html = $this->get('/admin/login')->assertOk()->getContent();

        $this->assertStringNotContainsString('fonts/filament/filament/inter', $html);
        $this->assertDoesNotMatchRegularExpression(self::SLOP_FONTS, $html);
        $this->assertStringContainsString("--font-family: 'Segoe UI'", $html);
        $this->assertStringContainsString('css/filament/filament/app.css', $html, 'tema Filament tetap dimuat');
    }

    public function test_hero_beranda_rata_kiri_dan_tanpa_label_huruf_kapital(): void
    {
        // Dengan jumbotron foto album (tugas 3) supaya layout dua kolomnya ikut diperiksa.
        Storage::fake(Album::DISK);
        $path = tempnam(sys_get_temp_dir(), 'gaya-test-');
        file_put_contents($path, $this->plainJpeg(1200, 800));
        Album::factory()->create()->addPhoto($path);

        $html = $this->get('/')->assertOk()->assertSee('<figure>', false)->getContent();
        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $h1 = $xpath->query('//h1')->item(0);
        $this->assertNotNull($h1);
        for ($node = $h1; $node instanceof \DOMElement; $node = $node->parentNode) {
            $this->assertDoesNotMatchRegularExpression('/\btext-center\b/', $node->getAttribute('class'), 'H1 hero dan pembungkusnya tidak boleh rata tengah');
        }

        // Tidak ada label uppercase + tracking di mana pun di halaman (termasuk footer).
        $this->assertDoesNotMatchRegularExpression('/class="[^"]*\buppercase\b[^"]*"/', $html);
    }
}
