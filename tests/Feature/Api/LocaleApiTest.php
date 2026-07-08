<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_maps_country_headers_to_a_supported_locale(): void
    {
        $this->getJson('/api/v1/locale', ['CF-IPCountry' => 'PL'])
            ->assertOk()->assertJson(['country' => 'PL', 'locale' => 'pl']);

        $this->getJson('/api/v1/locale', ['CloudFront-Viewer-Country' => 'SA'])
            ->assertOk()->assertJson(['country' => 'SA', 'locale' => 'ar']);

        $this->getJson('/api/v1/locale', ['CF-IPCountry' => 'KZ'])
            ->assertOk()->assertJson(['country' => 'KZ', 'locale' => 'ru']);
    }

    public function test_unmapped_country_yields_no_locale(): void
    {
        $this->getJson('/api/v1/locale', ['CF-IPCountry' => 'FR'])
            ->assertOk()->assertJson(['country' => 'FR', 'locale' => null]);
    }

    public function test_missing_or_unknown_geo_header_yields_nulls(): void
    {
        $this->getJson('/api/v1/locale')
            ->assertOk()->assertJson(['country' => null, 'locale' => null]);

        $this->getJson('/api/v1/locale', ['CF-IPCountry' => 'XX'])
            ->assertOk()->assertJson(['country' => null, 'locale' => null]);
    }

    public function test_it_is_public_and_needs_no_auth(): void
    {
        $this->getJson('/api/v1/locale')->assertOk();
    }
}
