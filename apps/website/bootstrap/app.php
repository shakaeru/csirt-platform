<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Di production TLS berakhir di Nginx edge (infra/), jadi Laravel harus mempercayai proxy
        // untuk tahu request aslinya HTTPS dan IP asli pengunjung.
        // - Proxy = range network Docker (pool bawaan: 172.16.0.0/12, lalu 192.168.0.0/16) — Nginx
        //   edge menjangkau container lewat net-website. JANGAN '*': di Laravel 13 artinya semua IP
        //   dipercaya, sehingga IP pengunjung diambil dari entri X-Forwarded-For paling kiri yang
        //   dikendalikan klien (throttle login bisa diakali). Ada tesnya: TrustedProxyTest.
        // - Header HANYA For + Proto: edge menimpa X-Forwarded-Proto dan menambahkan IP asli di ujung
        //   X-Forwarded-For, tetapi meneruskan X-Forwarded-Host/Port dari klien apa adanya —
        //   mempercayainya membuka host header injection (mis. tautan reset password palsu).
        $middleware->trustProxies(
            at: ['172.16.0.0/12', '192.168.0.0/16'],
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
