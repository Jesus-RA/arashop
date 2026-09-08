<?php

namespace App\Inventory\Actions;

use App\Inventory\Models\StockAdjustment;
use App\Models\User;
use App\Products\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ApplyStockAdjustment
{
    public function handle(
        ProductVariant $variant,
        int $quantityChange,
        string $reason,
        ?string $notes = null,
        ?User $user = null,
    ): StockAdjustment {
        if ($quantityChange === 0) {
            throw ValidationException::withMessages([
                'quantity_change' => ['The quantity change must not be zero.'],
            ]);
        }

        return DB::transaction(function () use ($variant, $quantityChange, $reason, $notes, $user) {
            $newQuantity = $variant->stock_quantity + $quantityChange;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => [
                        "Insufficient stock: the variant only has {$variant->stock_quantity} units.",
                    ],
                ]);
            }

            $adjustment = StockAdjustment::create([
                'variant_id' => $variant->id,
                'quantity_change' => $quantityChange,
                'reason' => $reason,
                'notes' => $notes,
                'user_id' => $user?->id,
            ]);

            $variant->update(['stock_quantity' => $newQuantity]);

            return $adjustment;
        });
    }
}
