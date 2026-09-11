<?php

namespace App\Inventory\Requests;

use App\Inventory\Enums\InventoryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'variant_id' => ['required', 'integer', Rule::exists('product_variants', 'id')->whereNull('deleted_at')],
            'quantity_change' => ['required', 'integer'],
            'transaction_type' => ['required', Rule::enum(InventoryTransactionType::class)],
            'reason' => [
                'nullable', 'string', 'max:100',
                'required_if:transaction_type,'.InventoryTransactionType::ManualAdjustment->value,
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
