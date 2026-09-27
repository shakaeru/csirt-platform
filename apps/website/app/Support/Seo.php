<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Meta SEO halaman publik (dipakai layouts/app.blade.php). Semua URL absolut dibangun dari
 * APP_URL, bukan dari host request, supaya canonical/og:url selalu https://csirt.pcr.ac.id/…
 * walaupun situs dibuka lewat IP atau host lain.
 */
final class Seo
{
    /** "<judul> | CSIRT PCR"; tanpa judul = judul Beranda. Halaman 2 dst. diberi keterangan. */
    public static function title(?string $title, int $page = 1): string
    {
        if ($title === null) {
            return config('seo.home_title');
        }

        return $title.($page > 1 ? " (Halaman {$page})" : '').' | '.config('seo.site_name');
    }

    public static function description(?string $description, int $page = 1): string
    {
        $text = Str::squish($description ?? config('seo.description'));

        return $page > 1 ? "{$text} Halaman {$page}." : $text;
    }

    /**
     * URL kanonik halaman ini. Hanya parameter yang membedakan isi yang dipertahankan (filter
     * kategori/tag/jenis dan nomor halaman). Parameter lain (pencarian, utm_*, dsb.) dibuang.
     *
     * @param  array<string, scalar|null>  $query
     */
    public static function canonical(array $query = []): string
    {
        $query = array_filter($query, fn (mixed $value): bool => $value !== null && $value !== '');
        ksort($query);
        $url = self::url(request()->path());

        return $query === [] ? $url : $url.'?'.http_build_query($query);
    }

    /** URL absolut dari APP_URL, mis. url('images/og-default.jpg'). Path "/" = Beranda. */
    public static function url(string $path = '/'): string
    {
        $path = trim($path, '/');

        return rtrim(config('app.url'), '/').'/'.$path;
    }

    /**
     * JSON-LD Organization + WebSite untuk Beranda. Nama situs di hasil Google diambil dari
     * WebSite.name/alternateName.
     *
     * @return array<string, mixed>
     */
    public static function organizationSchema(): array
    {
        $home = self::url();
        $kontak = config('kontak');

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $home.'#organization',
                    'name' => config('seo.organization'),
                    'alternateName' => [config('seo.site_name'), ...config('seo.alternate_names')],
                    'url' => $home,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => self::url('android-chrome-512x512.png'),
                        'width' => 512,
                        'height' => 512,
                    ],
                    'image' => self::url(config('seo.image')),
                    'email' => $kontak['email'],
                    'foundingDate' => (string) config('profil.tahun_ukm'),
                    'sameAs' => [$kontak['instagram']['url'], $kontak['linkedin']['url']],
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => $kontak['alamat']['jalan'],
                        'addressLocality' => $kontak['alamat']['kota'],
                        'addressRegion' => $kontak['alamat']['provinsi'],
                        'postalCode' => $kontak['alamat']['kode_pos'],
                        'addressCountry' => 'ID',
                    ],
                    'parentOrganization' => [
                        '@type' => 'CollegeOrUniversity',
                        'name' => config('seo.parent_organization.name'),
                        'url' => config('seo.parent_organization.url'),
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#website',
                    'name' => config('seo.site_name'),
                    'alternateName' => [config('seo.organization'), ...config('seo.alternate_names')],
                    'url' => $home,
                    'inLanguage' => 'id-ID',
                    'publisher' => ['@id' => $home.'#organization'],
                ],
            ],
        ];
    }

    /**
     * JSON untuk <script type="application/ld+json">. JSON_HEX_TAG mencegah "</script>" di dalam
     * data menutup tag lebih awal.
     *
     * @param  array<string, mixed>  $data
     */
    public static function jsonLd(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
    }
}
