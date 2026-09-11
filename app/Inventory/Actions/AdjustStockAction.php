<?php

namespace App\Inventory\Actions;

use App\Inventory\Enums\InventoryTransactionType;
use App\Inventory\Models\InventoryTransaction;
use App\Models\User;
use App\Products\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdjustStockAction
{
    public function handle(
        ProductVariant $variant,
        int $quantityChange,
        InventoryTransactionType $type,
        ?string $reason = null,
        ?string $notes = null,
        ?User $user = null,
        ?int $referenceId = null,
    ): InventoryTransaction {
        if ($quantityChange === 0) {
            throw ValidationException::withMessages([
                'quantity_change' => ['The quantity change must not be zero.'],
            ]);
        }

        if ($type === InventoryTransactionType::ManualAdjustment && $reason === null) {
            throw ValidationException::withMessages([
                'reason' => ['The reason field is required for manual adjustments.'],
            ]);
        }

        return DB::transaction(function () use ($variant, $quantityChange, $type, $reason, $notes, $user, $referenceId) {
            $newQuantity = $variant->stock_quantity + $quantityChange;

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity_change' => [
                        "Insufficient stock: the variant only has {$variant->stock_quantity} units.",
                    ],
                ]);
            }

            $transaction = InventoryTransaction::create([
                'variant_id' => $variant->id,
                'quantity_change' => $quantityChange,
                'transaction_type' => $type,
                'reference_id' => $referenceId,
                'reason' => $reason,
                'notes' => $notes,
                'user_id' => $user?->id,
            ]);

            $variant->update(['stock_quantity' => $newQuantity]);

            return $transaction;
        });
    }
}
