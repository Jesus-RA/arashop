<?php

namespace App\Orders\Controllers;

use App\Orders\Actions\CancelOrderAction;
use App\Orders\Actions\CreateOrderAction;
use App\Orders\Enums\OrderStatus;
use App\Orders\Models\Order;
use App\Orders\Models\OrderItem;
use App\Orders\Requests\StoreOrderRequest;
use App\Orders\Requests\UpdateOrderRequest;
use App\Orders\Resources\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class OrderController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = Order::query()
            ->with(['items.variant.product', 'user:id,name'])
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->string('status'));
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim($request->string('search'));

                $query->where(function ($query) use ($search) {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate((int) $request->integer('per_page', 15));

        return OrderResource::collection($orders);
    }

    public function summary(): JsonResponse
    {
        $placedOrders = Order::query()->where('status', '!=', OrderStatus::Cancelled);

        $totals = OrderItem::query()
            ->whereIn('order_id', (clone $placedOrders)->select('id'))
            ->selectRaw('COALESCE(SUM(quantity), 0) AS units_sold')
            ->selectRaw('COALESCE(SUM(subtotal), 0) AS revenue_mxn')
            ->selectRaw('COALESCE(SUM(unit_cost_usd * quantity), 0) AS cost_usd')
            ->first();

        return response()->json([
            'data' => [
                'orders_count' => (clone $placedOrders)->count(),
                'units_sold' => (int) $totals->units_sold,
                'revenue_mxn' => number_format((float) $totals->revenue_mxn, 2, '.', ''),
                'cost_usd' => number_format((float) $totals->cost_usd, 2, '.', ''),
            ],
        ]);
    }

    public function store(StoreOrderRequest $request, CreateOrderAction $action): JsonResponse
    {
        $order = $action->handle($request->user(), $request->validated('items'));

        return (new OrderResource($order->load(['items.variant.product', 'user:id,name'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['items.variant.product', 'user:id,name']));
    }

    public function update(UpdateOrderRequest $request, Order $order, CancelOrderAction $cancelAction): OrderResource
    {
        $status = OrderStatus::from($request->string('status'));

        if ($status === OrderStatus::Cancelled) {
            $cancelAction->handle($order, $request->user());
        } else {
            if ($order->status === OrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'status' => ['Cancelled orders cannot be reopened.'],
                ]);
            }

            $order->update(['status' => $status]);
        }

        return new OrderResource($order->load(['items.variant.product', 'user:id,name']));
    }
}
