<?php

namespace App\Pricing\Services;

use InvalidArgumentException;

final class ProfitCalculator
{
    /**
     * Calculate a cost/earnings breakdown for a product.
     *
     * All percentages are expressed as whole numbers (e.g. 35 for 35%).
     *
     * @param  array<string, int|float|null>  $input
     * @return array<string, float>
     */
    public function calculate(array $input): array
    {
        $costUsd = $this->requireNumber($input['cost_usd'] ?? null, 'cost_usd');
        $shippingUsd = max(0, (float) ($input['shipping_usd'] ?? 0));
        $fxRate = $this->requirePositive($input['fx_rate'] ?? null, 'fx_rate');
        $marginPercent = (float) ($input['margin_percent'] ?? 0);
        $platformFeePercent = max(0, (float) ($input['platform_fee_percent'] ?? 0));
        $packagingFeeMxn = max(0, (float) ($input['packaging_fee_mxn'] ?? 0));
        $discountPercent = max(0, (float) ($input['discount_percent'] ?? 0));
        $sellingPriceMxn = $input['selling_price_mxn'] ?? null;

        $this->assertPercent($marginPercent, 'margin_percent');
        $this->assertPercent($platformFeePercent, 'platform_fee_percent');
        $this->assertPercent($discountPercent, 'discount_percent');

        $subtotalUsd = $costUsd + $shippingUsd;
        $subtotalMxn = $subtotalUsd * $fxRate;

        if ($sellingPriceMxn !== null) {
            $sellingPriceMxn = $this->requirePositive($sellingPriceMxn, 'selling_price_mxn');
            $suggestedPriceMxn = null;
        } else {
            $denominator = 1 - $platformFeePercent / 100 - $marginPercent / 100;

            if ($denominator <= 0) {
                throw new InvalidArgumentException(
                    'The combined platform fee and margin must be less than 100%.',
                );
            }

            $sellingPriceMxn = $this->money(($subtotalMxn + $packagingFeeMxn) / $denominator);
            $suggestedPriceMxn = $sellingPriceMxn;
        }

        $platformFeeMxn = $this->money($sellingPriceMxn * $platformFeePercent / 100);
        $earningsMxn = $sellingPriceMxn - $platformFeeMxn - $subtotalMxn - $packagingFeeMxn;

        $discountedPriceMxn = $this->money($sellingPriceMxn * (1 - $discountPercent / 100));
        $discountedPlatformFeeMxn = $this->money($discountedPriceMxn * $platformFeePercent / 100);
        $earningsWithDiscountMxn = $discountedPriceMxn - $discountedPlatformFeeMxn - $subtotalMxn - $packagingFeeMxn;

        return [
            'cost_usd' => $this->money($costUsd),
            'shipping_usd' => $this->money($shippingUsd),
            'fx_rate' => $fxRate,
            'subtotal_usd' => $this->money($subtotalUsd),
            'subtotal_mxn' => $this->money($subtotalMxn),
            'packaging_fee_mxn' => $this->money($packagingFeeMxn),
            'platform_fee_percent' => $platformFeePercent,
            'margin_percent' => $marginPercent,
            'discount_percent' => $discountPercent,
            'selling_price_mxn' => $this->money($sellingPriceMxn),
            'suggested_price_mxn' => $suggestedPriceMxn !== null ? $this->money($suggestedPriceMxn) : null,
            'platform_fee_mxn' => $platformFeeMxn,
            'earnings_mxn' => $this->money($earningsMxn),
            'earnings_usd' => $this->money($earningsMxn / $fxRate),
            'discounted_price_mxn' => $discountedPriceMxn,
            'discounted_platform_fee_mxn' => $discountedPlatformFeeMxn,
            'earnings_with_discount_mxn' => $this->money($earningsWithDiscountMxn),
            'earnings_with_discount_usd' => $this->money($earningsWithDiscountMxn / $fxRate),
        ];
    }

    private function requireNumber(mixed $value, string $key): float
    {
        if ($value === null || ! is_numeric($value)) {
            throw new InvalidArgumentException("The \"{$key}\" value is required.");
        }

        return (float) $value;
    }

    private function requirePositive(mixed $value, string $key): float
    {
        $value = $this->requireNumber($value, $key);

        if ($value <= 0) {
            throw new InvalidArgumentException("The \"{$key}\" value must be greater than zero.");
        }

        return $value;
    }

    private function assertPercent(float $value, string $key): void
    {
        if ($value < 0 || $value > 100) {
            throw new InvalidArgumentException("The \"{$key}\" value must be between 0 and 100.");
        }
    }

    private function money(float $value): float
    {
        return round($value, 2);
    }
}
