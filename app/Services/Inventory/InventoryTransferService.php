<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Inventory\{
    InventoryTransfer,
    InventoryTransferItem,
    InventoryReason
};

class InventoryTransferService
{
    public function approve(InventoryTransfer $transfer, int $userId): void
    {
        if ($transfer->status !== 'DRAFT') {
            throw new \RuntimeException('Only draft transfers can be approved.');
        }

        $transfer->update([
            'status' => 'APPROVED',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function ship(InventoryTransfer $transfer, int $userId): void
    {
        if (!in_array($transfer->status, ['APPROVED'])) {
            throw new \RuntimeException('Transfer must be approved before shipping.');
        }

        DB::transaction(function () use ($transfer, $userId) {

            foreach ($transfer->items as $item) {
                app(InventoryPostingService::class)->postMovement([
                    'company_id' => $transfer->company_id,
                    'inventory_location_id' => $transfer->from_location_id,
                    'product_variant_id' => $item->product_variant_id,
                    'reason_code' => 'TRANSFER_OUT',
                    'quantity' => $item->quantity,
                    'performed_by' => $userId,
                    'reference_type' => InventoryTransfer::class,
                    'reference_id' => $transfer->id,
                    'idempotency_key' => "transfer:{$transfer->id}:ship:{$item->product_variant_id}",
                ]);
            }

            $transfer->update([
                'status' => 'SHIPPED',
                'shipped_at' => Carbon::now(),
            ]);
        });
    }

    public function receive(
        InventoryTransfer $transfer,
        array $receivedItems,
        int $userId
    ): void {
        if ($transfer->status !== 'SHIPPED') {
            throw new \RuntimeException('Transfer must be shipped before receiving.');
        }

        DB::transaction(function () use ($transfer, $receivedItems, $userId) {

            foreach ($transfer->items as $item) {

                $receivedQty = $receivedItems[$item->product_variant_id] ?? 0;

                if ($receivedQty <= 0) {
                    continue;
                }

                app(InventoryPostingService::class)->postMovement([
                    'company_id' => $transfer->company_id,
                    'inventory_location_id' => $transfer->to_location_id,
                    'product_variant_id' => $item->product_variant_id,
                    'reason_code' => 'TRANSFER_IN',
                    'quantity' => $receivedQty,
                    'performed_by' => $userId,
                    'reference_type' => InventoryTransfer::class,
                    'reference_id' => $transfer->id,
                    'idempotency_key' => "transfer:{$transfer->id}:receive:{$item->product_variant_id}",
                ]);

                $item->increment('received_quantity', $receivedQty);
            }

            $allReceived = $transfer->items
                ->every(fn($i) => $i->received_quantity >= $i->quantity);

            $transfer->update([
                'status' => $allReceived ? 'CLOSED' : 'RECEIVED',
                'received_at' => now(),
            ]);
        });
    }
}
