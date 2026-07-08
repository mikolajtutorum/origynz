<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Supported UI locales. Anything else falls back to English client-side.
     */
    private const SUPPORTED = ['en', 'pl', 'ru', 'ar'];

    /**
     * Country (ISO 3166-1 alpha-2) → suggested UI locale. Countries not listed
     * fall through to English.
     *
     * @var array<string, string>
     */
    private const COUNTRY_LOCALE = [
        // Polish
        'PL' => 'pl',
        // Russian (Russia + neighbours where Russian is the lingua franca)
        'RU' => 'ru', 'BY' => 'ru', 'KZ' => 'ru', 'KG' => 'ru', 'TJ' => 'ru', 'TM' => 'ru', 'UZ' => 'ru',
        // Arabic (Arab League)
        'SA' => 'ar', 'AE' => 'ar', 'EG' => 'ar', 'DZ' => 'ar', 'IQ' => 'ar', 'JO' => 'ar', 'KW' => 'ar',
        'LB' => 'ar', 'LY' => 'ar', 'MA' => 'ar', 'OM' => 'ar', 'PS' => 'ar', 'QA' => 'ar', 'SY' => 'ar',
        'TN' => 'ar', 'YE' => 'ar', 'BH' => 'ar', 'SD' => 'ar', 'MR' => 'ar', 'SO' => 'ar', 'DJ' => 'ar',
        'KM' => 'ar',
    ];

    /**
     * Suggest a UI locale from the caller's IP (via edge/CDN geo headers).
     * Public + unauthenticated so the SPA can call it before login.
     */
    public function suggest(Request $request): JsonResponse
    {
        $country = $this->countryFromRequest($request);
        $locale = $country ? (self::COUNTRY_LOCALE[$country] ?? null) : null;

        return response()->json([
            'country' => $country,
            'locale' => in_array($locale, self::SUPPORTED, true) ? $locale : null,
        ]);
    }

    /**
     * Read the ISO country code from whichever geo header the edge set.
     * Returns null on local/unknown IPs (dev, or no CDN in front).
     */
    private function countryFromRequest(Request $request): ?string
    {
        $headers = [
            'CF-IPCountry',              // Cloudflare
            'CloudFront-Viewer-Country', // AWS CloudFront
            'X-AppEngine-Country',       // Google App Engine
            'X-Country-Code',            // generic / nginx geoip
            'X-Geo-Country',
        ];

        foreach ($headers as $header) {
            $value = $request->header($header);
            if (! $value) {
                continue;
            }

            $code = strtoupper(substr(trim($value), 0, 2));
            // 'XX'/'T1' are the "unknown"/Tor sentinels edges emit.
            if (preg_match('/^[A-Z]{2}$/', $code) && ! in_array($code, ['XX', 'T1'], true)) {
                return $code;
            }
        }

        return null;
    }
}
