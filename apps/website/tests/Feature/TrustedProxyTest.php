<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Di production TLS berakhir di Nginx edge (infra/), yang menjangkau container lewat network
 * Docker. Lihat trustProxies() di bootstrap/app.php.
 */
class TrustedProxyTest extends TestCase
{
    /** IP Nginx edge di net-website (pool alamat Docker bawaan). */
    private const EDGE_IP = '172.18.0.2';

    private const URL = 'http://csirt.pcr.ac.id/_uji-proxy';

    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/_uji-proxy', fn (Request $request): array => [$request->getScheme(), $request->ip(), url('/berita')]);
    }

    public function test_https_dan_ip_asli_dibaca_dari_header_nginx_edge(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::EDGE_IP])
            ->get(self::URL, [
                'X-Forwarded-Proto' => 'https',
                // 198.51.100.66 dipalsukan klien; 203.0.113.7 = IP asli yang ditambahkan edge di ujung.
                'X-Forwarded-For' => '198.51.100.66, 203.0.113.7',
            ])
            ->assertExactJson(['https', '203.0.113.7', 'https://csirt.pcr.ac.id/berita']);
    }

    /** Edge meneruskan header ini dari klien apa adanya — tidak boleh dipercaya (host header injection). */
    public function test_x_forwarded_host_dan_port_dari_klien_diabaikan(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => self::EDGE_IP])
            ->get(self::URL, [
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-For' => '203.0.113.7',
                'X-Forwarded-Host' => 'penyerang.example',
                'X-Forwarded-Port' => '8443',
            ])
            ->assertExactJson(['https', '203.0.113.7', 'https://csirt.pcr.ac.id/berita']);
    }

    /** Request yang tidak datang dari network Docker (mis. langsung/lokal): header proxy diabaikan. */
    public function test_header_proxy_dari_luar_network_docker_diabaikan(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
            ->get(self::URL, ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '198.51.100.66'])
            ->assertExactJson(['http', '203.0.113.9', 'http://csirt.pcr.ac.id/berita']);
    }
}
