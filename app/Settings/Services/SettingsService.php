<?php

namespace App\Settings\Services;

use App\Settings\Models\Setting;

final class SettingsService
{
    /**
     * Known settings keys mapped to their config defaults.
     *
     * @var array<string, string>
     */
    private const DEFAULTS = [
        'fx.usd_to_mxn' => 'pricing.fx.usd_to_mxn',
        'pricing.margin_percent' => 'pricing.margin_percent',
        'pricing.platform_fee_percent' => 'pricing.platform_fee_percent',
        'pricing.packaging_fee_mxn' => 'pricing.packaging_fee_mxn',
        'pricing.discount_percent' => 'pricing.discount_percent',
    ];

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $settings = Setting::whereIn('key', array_keys(self::DEFAULTS))
            ->get()
            ->pluck('value', 'key');

        $result = [];

        foreach (self::DEFAULTS as $key => $configPath) {
            $result[$key] = $settings[$key] ?? config($configPath);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(array $values): void
    {
        foreach (array_intersect_key($values, self::DEFAULTS) as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
