<?php

namespace App\Pricing\Controllers;

use App\Pricing\Requests\CalculatePricingRequest;
use App\Pricing\Services\ProfitCalculator;
use App\Settings\Services\SettingsService;
use Illuminate\Http\JsonResponse;

class PricingController
{
    public function calculate(
        CalculatePricingRequest $request,
        ProfitCalculator $calculator,
        SettingsService $settings,
    ): JsonResponse {
        $values = $settings->all();

        $input = array_merge($values, [
            'cost_usd' => $request->float('cost_usd'),
            'shipping_usd' => $request->float('shipping_usd', 0),
            'selling_price_mxn' => $request->filled('selling_price_mxn')
                ? $request->float('selling_price_mxn')
                : null,
            'margin_percent' => $request->filled('margin_percent')
                ? $request->float('margin_percent')
                : (float) $values['pricing.margin_percent'],
            'platform_fee_percent' => $request->filled('platform_fee_percent')
                ? $request->float('platform_fee_percent')
                : (float) $values['pricing.platform_fee_percent'],
            'packaging_fee_mxn' => $request->filled('packaging_fee_mxn')
                ? $request->float('packaging_fee_mxn')
                : (float) $values['pricing.packaging_fee_mxn'],
            'discount_percent' => $request->filled('discount_percent')
                ? $request->float('discount_percent')
                : (float) $values['pricing.discount_percent'],
            'fx_rate' => $request->filled('fx_rate')
                ? $request->float('fx_rate')
                : (float) $values['fx.usd_to_mxn'],
        ]);

        return response()->json($calculator->calculate($input));
    }
}
