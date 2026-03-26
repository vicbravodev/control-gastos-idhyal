<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TrustedProxyConfigurationTest extends TestCase
{
    public function test_trusted_proxy_config_defines_proxies(): void
    {
        $this->assertIsArray(config('trustedproxy'));
        $this->assertArrayHasKey('proxies', config('trustedproxy'));
        $this->assertTrue(
            config('trustedproxy.proxies') === '*' || is_string(config('trustedproxy.proxies')),
        );
    }

    public function test_x_forwarded_proto_https_is_honored_when_proxies_are_trusted(): void
    {
        config(['trustedproxy.proxies' => '*']);

        Route::get('__proxy_test', function (Request $request) {
            return response()->json([
                'secure' => $request->secure(),
                'scheme' => $request->getScheme(),
            ]);
        });

        $this->withHeaders([
            'X-Forwarded-Proto' => 'https',
        ])->get('/__proxy_test')
            ->assertOk()
            ->assertJson([
                'secure' => true,
                'scheme' => 'https',
            ]);
    }
}
