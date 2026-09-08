<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_it_uses_settings_as_defaults(): void
    {
        Setting::create(['key' => 'fx.usd_to_mxn', 'value' => 20]);
        Setting::create(['key' => 'pricing.margin_percent', 'value' => 25]);
        Setting::create(['key' => 'pricing.platform_fee_percent', 'value' => 10]);
        Setting::create(['key' => 'pricing.packaging_fee_mxn', 'value' => 15]);
        Setting::create(['key' => 'pricing.discount_percent', 'value' => 0]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/pricing/calculate', [
                'cost_usd' => 10,
                'shipping_usd' => 2,
            ])
            ->assertOk();

        $this->assertEquals(20.0, $response->json('fx_rate'));
        $this->assertEquals(240.0, $response->json('subtotal_mxn'));
        $this->assertEquals(392.31, $response->json('suggested_price_mxn'));
        $this->assertEquals(25.0, $response->json('margin_percent'));
        $this->assertEquals(10.0, $response->json('platform_fee_percent'));
    }

    public function test_it_suggests_a_price_from_margin(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/pricing/calculate', [
                'cost_usd' => 10,
                'shipping_usd' => 2,
                'fx_rate' => 18,
                'margin_percent' => 35,
                'platform_fee_percent' => 10,
                'packaging_fee_mxn' => 15,
                'discount_percent' => 0,
            ])
            ->assertOk();

        // ((10 + 2) * 18 + 15) / (1 - 0.10 - 0.35) = 231 / 0.55 = 420
        $this->assertEquals(420.0, $response->json('suggested_price_mxn'));
        $this->assertEquals(42.0, $response->json('platform_fee_mxn'));
        $this->assertEquals(147.0, $response->json('earnings_mxn'));
    }

    public function test_it_computes_a_breakdown_for_a_custom_price(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/pricing/calculate', [
                'cost_usd' => 10,
                'shipping_usd' => 2,
                'fx_rate' => 18,
                'margin_percent' => 35,
                'platform_fee_percent' => 10,
                'packaging_fee_mxn' => 15,
                'discount_percent' => 0,
                'selling_price_mxn' => 400,
            ])
            ->assertOk();

        $this->assertNull($response->json('suggested_price_mxn'));
        $this->assertEquals(129.0, $response->json('earnings_mxn'));
        $this->assertEquals(7.17, $response->json('earnings_usd'));
    }

    public function test_it_requires_cost_usd(): void
    {
        $this->actingAs($this->user)
            ->postJson('/api/pricing/calculate', ['shipping_usd' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cost_usd']);
    }
}
