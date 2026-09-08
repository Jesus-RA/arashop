<?php

namespace Tests\Unit;

use App\Pricing\Services\ProfitCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ProfitCalculatorTest extends TestCase
{
    private ProfitCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new ProfitCalculator;
    }

    public function test_it_suggests_a_price_from_the_target_margin(): void
    {
        $result = $this->calculator->calculate([
            'cost_usd' => 10,
            'shipping_usd' => 2,
            'fx_rate' => 18,
            'margin_percent' => 35,
            'platform_fee_percent' => 10,
            'packaging_fee_mxn' => 15,
            'discount_percent' => 0,
        ]);

        $this->assertSame(420.0, $result['suggested_price_mxn']);
        $this->assertSame(216.0, $result['subtotal_mxn']);
        $this->assertSame(42.0, $result['platform_fee_mxn']);
        $this->assertSame(147.0, $result['earnings_mxn']);
    }

    public function test_it_computes_the_breakdown_for_a_custom_price(): void
    {
        $result = $this->calculator->calculate([
            'cost_usd' => 10,
            'shipping_usd' => 2,
            'fx_rate' => 18,
            'margin_percent' => 35,
            'platform_fee_percent' => 10,
            'packaging_fee_mxn' => 15,
            'discount_percent' => 0,
            'selling_price_mxn' => 400,
        ]);

        $this->assertNull($result['suggested_price_mxn']);
        $this->assertSame(400.0, $result['selling_price_mxn']);
        $this->assertSame(40.0, $result['platform_fee_mxn']);
        $this->assertSame(129.0, $result['earnings_mxn']);
        $this->assertSame(7.17, $result['earnings_usd']);
    }

    public function test_it_applies_the_discount_to_price_and_earnings(): void
    {
        $result = $this->calculator->calculate([
            'cost_usd' => 10,
            'shipping_usd' => 2,
            'fx_rate' => 18,
            'margin_percent' => 35,
            'platform_fee_percent' => 10,
            'packaging_fee_mxn' => 15,
            'discount_percent' => 10,
            'selling_price_mxn' => 400,
        ]);

        $this->assertSame(360.0, $result['discounted_price_mxn']);
        $this->assertSame(36.0, $result['discounted_platform_fee_mxn']);
        $this->assertSame(93.0, $result['earnings_with_discount_mxn']);
    }

    public function test_it_never_returns_negative_utility_values_from_guards(): void
    {
        $result = $this->calculator->calculate([
            'cost_usd' => 10,
            'fx_rate' => 18,
            'margin_percent' => 35,
            'platform_fee_percent' => 10,
            'packaging_fee_mxn' => 15,
            'discount_percent' => 0,
        ]);

        $this->assertGreaterThan(0, $result['earnings_mxn']);
    }

    public function test_it_throws_when_fee_plus_margin_reaches_100(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([
            'cost_usd' => 10,
            'shipping_usd' => 2,
            'fx_rate' => 18,
            'margin_percent' => 90,
            'platform_fee_percent' => 10,
            'packaging_fee_mxn' => 15,
            'discount_percent' => 0,
        ]);
    }

    public function test_it_rejects_non_positive_fx_rate(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([
            'cost_usd' => 10,
            'shipping_usd' => 0,
            'fx_rate' => 0,
            'margin_percent' => 35,
            'platform_fee_percent' => 10,
            'packaging_fee_mxn' => 15,
            'discount_percent' => 0,
        ]);
    }

    public function test_it_validates_percentage_ranges(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->calculate([
            'cost_usd' => 10,
            'shipping_usd' => 2,
            'fx_rate' => 18,
            'margin_percent' => 150,
            'platform_fee_percent' => 10,
            'packaging_fee_mxn' => 15,
            'discount_percent' => 0,
        ]);
    }

    public function test_it_rounds_money_to_two_decimals(): void
    {
        $result = $this->calculator->calculate([
            'cost_usd' => 10.33,
            'shipping_usd' => 1.11,
            'fx_rate' => 18.5,
            'margin_percent' => 33,
            'platform_fee_percent' => 7.5,
            'packaging_fee_mxn' => 12.75,
            'discount_percent' => 0,
        ]);

        $this->assertSame(211.64, $result['subtotal_mxn']);
        foreach ($result as $value) {
            if (is_float($value)) {
                $this->assertEqualsWithDelta($value, round($value, 2), 0.001);
            }
        }
    }
}
