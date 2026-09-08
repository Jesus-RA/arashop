<?php

namespace Tests\Feature;

use App\Models\User;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_settings_require_authentication(): void
    {
        $this->getJson('/api/settings')->assertUnauthorized();
    }

    public function test_it_returns_settings_falling_back_to_config(): void
    {
        $data = $this->actingAs($this->user)
            ->getJson('/api/settings')
            ->assertOk()
            ->json();

        $this->assertEquals(config('pricing.fx.usd_to_mxn'), $data['fx.usd_to_mxn']);
        $this->assertEquals(config('pricing.margin_percent'), $data['pricing.margin_percent']);
    }

    public function test_it_returns_stored_settings_over_config(): void
    {
        Setting::create(['key' => 'fx.usd_to_mxn', 'value' => 19.5]);

        $data = $this->actingAs($this->user)
            ->getJson('/api/settings')
            ->assertOk()
            ->json();

        $this->assertEquals(19.5, $data['fx.usd_to_mxn']);
    }

    public function test_it_updates_settings(): void
    {
        $data = $this->actingAs($this->user)
            ->putJson('/api/settings', [
                'fx.usd_to_mxn' => 18.4,
                'pricing.margin_percent' => 30,
                'pricing.platform_fee_percent' => 12,
                'pricing.packaging_fee_mxn' => 20,
                'pricing.discount_percent' => 5,
            ])
            ->assertOk()
            ->json();

        $this->assertEquals(18.4, $data['fx.usd_to_mxn']);
        $this->assertEquals(30, $data['pricing.margin_percent']);

        $this->assertSame(18.4, Setting::where('key', 'fx.usd_to_mxn')->first()->value);
    }

    public function test_it_validates_settings_values(): void
    {
        $this->actingAs($this->user)
            ->putJson('/api/settings', [
                'fx.usd_to_mxn' => 0,
                'pricing.margin_percent' => 150,
                'pricing.platform_fee_percent' => 12,
                'pricing.packaging_fee_mxn' => 20,
                'pricing.discount_percent' => 5,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fx.usd_to_mxn', 'pricing.margin_percent']);
    }
}
