<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);
        app()->setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }

    private function supported(): array
    {
        return array_keys(config('app.locales', ['en' => []]));
    }

    private function resolveLocale(Request $request): string
    {
        // 1. User explicitly chose a locale (stored in session)
        $session = session('locale');
        if ($session && in_array($session, $this->supported(), true)) {
            return $session;
        }

        // 2. Browser Accept-Language header
        $fromBrowser = $this->detectFromBrowser($request);
        if ($fromBrowser) {
            return $fromBrowser;
        }

        // 3. IP geolocation (cached per IP, 24 h)
        $fromIp = $this->detectFromIp($request->ip());
        if ($fromIp) {
            return $fromIp;
        }

        return config('app.locale', 'en');
    }

    private function detectFromBrowser(Request $request): ?string
    {
        $header = $request->header('Accept-Language', '');
        if (empty($header)) {
            return null;
        }

        // Parse "pl-PL,pl;q=0.9,en-US;q=0.8,en;q=0.7" into ordered list
        $tags = [];
        foreach (explode(',', $header) as $part) {
            [$tag, $q] = array_pad(explode(';q=', trim($part)), 2, '1');
            $tags[trim($tag)] = (float) $q;
        }
        arsort($tags);

        foreach (array_keys($tags) as $tag) {
            $mapped = $this->mapBrowserTag(strtolower($tag));
            if ($mapped) {
                return $mapped;
            }
        }

        return null;
    }

    private function mapBrowserTag(string $tag): ?string
    {
        foreach ($this->supported() as $locale) {
            if (str_starts_with($tag, $locale)) {
                return $locale;
            }
        }

        return null;
    }

    private function detectFromIp(string $ip): ?string
    {
        if (in_array($ip, ['127.0.0.1', '::1'], true)) {
            return null;
        }

        return Cache::remember("locale_ip_{$ip}", now()->addDay(), function () use ($ip) {
            try {
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}", [
                    'fields' => 'countryCode',
                ]);

                $countryCode = $response->json('countryCode');

                foreach (config('app.locales', []) as $locale => $meta) {
                    if (in_array($countryCode, $meta['countries'] ?? [], true)) {
                        return $locale;
                    }
                }

                return config('app.locale', 'en');
            } catch (\Throwable) {
                return null;
            }
        });
    }
}
