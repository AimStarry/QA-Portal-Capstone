<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add recommended HTTP security headers to all responses.
     *
     * These headers instruct browsers to apply protective policies.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent browsers from MIME-sniffing a response away from the declared content-type
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking — disallow framing by other sites
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Control how much referrer information is shared
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // XSS protection header for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Permissions policy — disable unused browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy — restrict where resources can load from
        // 'unsafe-inline' is required for Vite/Tailwind inline styles and Chart.js inline scripts
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://fonts.bunny.net; " .
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; " .
            "font-src 'self' https://fonts.bunny.net; " .
            "img-src 'self' data: blob:; " .
            "connect-src 'self'; " .
            "frame-ancestors 'none';"
        );

        return $response;
    }
}
