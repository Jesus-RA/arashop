<?php

namespace App\Settings\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function validationData(): array
    {
        $data = parent::all();
        $nested = [];

        foreach ($data as $key => $value) {
            data_set($nested, $key, $value);
        }

        return $nested;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'fx.usd_to_mxn' => ['required', 'numeric', 'gt:0'],
            'pricing.margin_percent' => ['required', 'numeric', 'between:0,100'],
            'pricing.platform_fee_percent' => ['required', 'numeric', 'between:0,100'],
            'pricing.packaging_fee_mxn' => ['required', 'numeric', 'min:0'],
            'pricing.discount_percent' => ['required', 'numeric', 'between:0,100'],
        ];
    }
}
