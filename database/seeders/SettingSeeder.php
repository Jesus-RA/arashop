<?php

namespace Database\Seeders;

use App\Settings\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'fx.usd_to_mxn' => config('pricing.fx.usd_to_mxn'),
            'pricing.margin_percent' => config('pricing.margin_percent'),
            'pricing.platform_fee_percent' => config('pricing.platform_fee_percent'),
            'pricing.packaging_fee_mxn' => config('pricing.packaging_fee_mxn'),
            'pricing.discount_percent' => config('pricing.discount_percent'),
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
