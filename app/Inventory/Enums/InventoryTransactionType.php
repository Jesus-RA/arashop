<?php

namespace App\Inventory\Enums;

enum InventoryTransactionType: string
{
    case Sale = 'SALE';
    case Restock = 'RESTOCK';
    case ManualAdjustment = 'MANUAL_ADJUSTMENT';
    case OrderCancellation = 'ORDER_CANCELLATION';

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
