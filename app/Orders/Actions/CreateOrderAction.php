<?php

namespace App\Orders\Actions;

use App\Inventory\Enums\InventoryTransactionType;
use App\Inventory\Models\InventoryTransaction;
use App\Models\User;
use App\Orders\Models\Order;
use App\Orders\Models\OrderItem;
use App\Products\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateOrderAction
{
    /**
     * @param  array<int, array{variant_id: int, quantity: int}>  $items
     */
    public function handle(?User $user, array $items): Order
    {
        if (count($items) === 0) {
            throw ValidationException::withMessages([
                'items' => ['At least one item is required.'],
            ]);
        }

        $quantities = [];

        foreach ($items as $item) {
            $quantities[$item['variant_id']] = ($quantities[$item['variant_id']] ?? 0) + $item['quantity'];
        }

        return DB::transaction(function () use ($user, $items, $quantities) {
            $variantIds = array_keys($quantities);

            /** @var array<int, ProductVariant> $variants */
            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($quantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                if ($variant === null) {
                    throw ValidationException::withMessages([
                        'items.*.variant_id' => ['The selected variant is invalid.'],
                    ]);
                }

                if ($variant->stock_quantity < $quantity) {
                    throw ValidationException::withMessages([
                        'items.*.quantity' => [
                            "Insufficient stock for \"{$variant->name}\": only {$variant->stock_quantity} units available.",
                        ],
                    ]);
                }
            }

            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'status' => 'pending',
                'total_amount' => 0,
                'user_id' => $user?->id,
            ]);

            $total = 0;

            foreach ($items as $item) {
                $variant = $variants->get($item['variant_id']);
                $subtotal = round($variant->price_mxn * $item['quantity'], 2);
                $total += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'unit_price_mxn' => $variant->price_mxn,
                    'unit_cost_usd' => $variant->cost_usd,
                    'subtotal' => $subtotal,
                ]);
            }

            foreach ($quantities as $variantId => $quantity) {
                $variant = $variants->get($variantId);

                $variant->update(['stock_quantity' => $variant->stock_quantity - $quantity]);

                InventoryTransaction::create([
                    'variant_id' => $variant->id,
                    'quantity_change' => -$quantity,
                    'transaction_type' => InventoryTransactionType::Sale,
                    'reference_id' => $order->id,
                    'reason' => null,
                    'notes' => "Order {$order->order_number}",
                    'user_id' => $user?->id,
                ]);
            }

            $order->update(['total_amount' => round($total, 2)]);

            return $order;
        });
    }

    private function nextOrderNumber(): string
    {
        $maxId = Order::withTrashed()->max('id') ?? 0;

        return 'ORD-'.str_pad((string) ($maxId + 1), 4, '0', STR_PAD_LEFT);
    }
}
