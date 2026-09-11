<?php

namespace App\Orders\Actions;

use App\Inventory\Enums\InventoryTransactionType;
use App\Inventory\Models\InventoryTransaction;
use App\Models\User;
use App\Orders\Enums\OrderStatus;
use App\Orders\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CancelOrderAction
{
    public function handle(Order $order, ?User $user = null): Order
    {
        if ($order->status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'status' => ['This order is already cancelled.'],
            ]);
        }

        return DB::transaction(function () use ($order, $user) {
            $order->update(['status' => OrderStatus::Cancelled]);

            foreach ($order->items as $item) {
                $variant = $item->variant;

                $variant->update(['stock_quantity' => $variant->stock_quantity + $item->quantity]);

                InventoryTransaction::create([
                    'variant_id' => $variant->id,
                    'quantity_change' => $item->quantity,
                    'transaction_type' => InventoryTransactionType::OrderCancellation,
                    'reference_id' => $order->id,
                    'reason' => null,
                    'notes' => "Cancellation of {$order->order_number}",
                    'user_id' => $user?->id,
                ]);
            }

            return $order;
        });
    }
}
