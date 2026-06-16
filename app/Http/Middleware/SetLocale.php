<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active locale on every request.
 *
 * Priority (first match wins):
 *   1. ?lang= query string  (handy for the public API + frontend deep-links)
 *   2. Lang header          (X-Locale)
 *   3. Accept-Language      (first acceptable that we support)
 *   4. session('locale')    (set by the Filament language switch for admins)
 *   5. config('app.locale') (project default)
 *
 * Unknown / unsupported locales are silently ignored.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = (array) config('app.available_locales', ['en']);
        $default = (string) config('app.locale', 'en');

        $candidates = [
            (string) $request->query('lang', ''),
            (string) $request->header('X-Locale', ''),
            $this->parseAcceptLanguage($request->header('Accept-Language', ''), $supported),
            // Session is only available on web (admin) routes that hit
            // StartSession before us. On API routes hasSession() returns
            // false, so we skip this candidate gracefully.
            $request->hasSession() ? (string) $request->session()->get('locale', '') : '',
            $default,
        ];

        $locale = $default;
        foreach ($candidates as $candidate) {
            $candidate = strtolower(trim((string) $candidate));
            if ($candidate !== '' && in_array($candidate, $supported, true)) {
                $locale = $candidate;
                break;
            }
        }

        App::setLocale($locale);

        return $next($request);
    }

    /**
     * Pick the highest-q-weighted Accept-Language entry that we actually support.
     */
    protected function parseAcceptLanguage(string $header, array $supported): string
    {
        if ($header === '') {
            return '';
        }

        $entries = [];
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $bits = explode(';', $part);
            $tag = strtolower(trim($bits[0]));
            $q = 1.0;
            foreach (array_slice($bits, 1) as $param) {
                if (str_starts_with(trim($param), 'q=')) {
                    $q = (float) substr(trim($param), 2);
                }
            }
            $entries[] = ['tag' => $tag, 'q' => $q];
        }

        usort($entries, fn ($a, $b) => $b['q'] <=> $a['q']);

        foreach ($entries as $e) {
            $primary = explode('-', $e['tag'])[0];
            if (in_array($primary, $supported, true)) {
                return $primary;
            }
        }

        return '';
    }
}
