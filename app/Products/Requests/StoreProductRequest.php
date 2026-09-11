<?php

namespace App\Products\Requests;

use App\Products\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
            'image_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
