<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan dasar untuk semua halaman, termasuk panel admin.
 * HSTS dipasang di Nginx setelah HTTPS aktif (lihat README).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($this->usesContentSecurityPolicy($request, $response)) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        return $response;
    }

    /**
     * CSP hanya untuk halaman publik. Panel admin (Filament/Livewire) butuh inline script
     * dan eval, jadi tidak ikut. Halaman error debug Laravel juga dilewati supaya tetap tampil saat development.
     */
    private function usesContentSecurityPolicy(Request $request, Response $response): bool
    {
        if ($request->is('admin', 'admin/*', 'livewire/*', 'filament/*')) {
            return false;
        }

        return ! (config('app.debug') && $response->getStatusCode() >= 500);
    }

    /**
     * Script dan CSS hanya dari domain sendiri (hasil build Vite), font dari Google Fonts.
     * Atribut style="..." di HTML diizinkan karena dipakai untuk variabel warna, bukan untuk script.
     */
    private function contentSecurityPolicy(): string
    {
        $script = ["'self'"];
        $style = ["'self'", 'https://fonts.googleapis.com'];
        $connect = ["'self'"];

        // Saat `npm run dev`, aset dimuat dari server Vite dan CSS disisipkan lewat tag <style>.
        if (Vite::isRunningHot()) {
            $hot = rtrim(trim((string) file_get_contents(public_path('hot'))), '/');
            $script[] = $hot;
            array_push($style, $hot, "'unsafe-inline'");
            array_push($connect, $hot, preg_replace('#^http#', 'ws', $hot));
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            "style-src-attr 'unsafe-inline'",
            "font-src 'self' https://fonts.gstatic.com",
            "img-src 'self' data:",
            "media-src 'self'",
            'connect-src '.implode(' ', $connect),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'self'",
        ];

        if (app()->isProduction()) {
            $directives[] = 'upgrade-insecure-requests';
        }

        return implode('; ', $directives);
    }
}
