<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Inventory\{
    InventoryTransfer,
    InventoryTransferItem,
    InventoryTransferStatus
};

class InventoryTransferService
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService
    ) {}

    /**
     * Approve transfer
     */
    public function approve(InventoryTransfer $transfer, int $userId): void
    {
        $draftStatusId = InventoryTransferStatus::where('code', 'DRAFT')->value('id');
        $approvedStatusId = InventoryTransferStatus::where('code', 'APPROVED')->value('id');

        if ($transfer->status_id !== $draftStatusId) {
            throw new \RuntimeException('Only draft transfers can be approved.');
        }

        $transfer->update([
            'status_id' => $approvedStatusId,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Ship transfer (OUT movements)
     */
    public function ship(InventoryTransfer $transfer, int $userId): void
    {
        $approvedStatusId = InventoryTransferStatus::where('code', 'APPROVED')->value('id');
        $shippedStatusId = InventoryTransferStatus::where('code', 'SHIPPED')->value('id');

        if ($transfer->status_id !== $approvedStatusId) {
            throw new \RuntimeException('Transfer must be approved before shipping.');
        }

        DB::transaction(function () use ($transfer, $userId, $shippedStatusId) {

            $transfer->load('items');

            foreach ($transfer->items as $item) {

                $this->inventoryPostingService->postMovement([
                    'company_id' => $transfer->company_id,
                    'inventory_location_id' => $transfer->from_location_id,
                    'product_variant_id' => $item->product_variant_id,
                    'reason_code' => 'TRANSFER_OUT',
                    'quantity' => $item->quantity,
                    'performed_by' => $userId,
                    'reference_type' => InventoryTransfer::class,
                    'reference_id' => $transfer->id,
                    'idempotency_key' => "transfer:{$transfer->id}:out:{$item->product_variant_id}",
                    'allow_negative' => false, // ❗ NEVER allow negative on OUT
                ]);
            }

            $transfer->update([
                'status_id' => $shippedStatusId,
                'shipped_at' => Carbon::now(),
            ]);
        });
    }

    /**
     * Receive transfer (IN movements)
     */
    public function receive(
        InventoryTransfer $transfer,
        array $receivedItems,
        int $userId
    ): void {
        $shippedStatusId = InventoryTransferStatus::where('code', 'SHIPPED')->value('id');
        $receivedStatusId = InventoryTransferStatus::where('code', 'RECEIVED')->value('id');
        $closedStatusId = InventoryTransferStatus::where('code', 'CLOSED')->value('id');

        if ($transfer->status_id !== $shippedStatusId) {
            throw new \RuntimeException('Transfer must be shipped before receiving.');
        }

        DB::transaction(function () use (
            $transfer,
            $receivedItems,
            $userId,
            $receivedStatusId,
            $closedStatusId
        ) {

            $transfer->load('items');

            foreach ($transfer->items as $item) {

                $receivedQty = (int) ($receivedItems[$item->product_variant_id] ?? 0);

                if ($receivedQty <= 0) {
                    continue;
                }

                $this->inventoryPostingService->postMovement([
                    'company_id' => $transfer->company_id,
                    'inventory_location_id' => $transfer->to_location_id,
                    'product_variant_id' => $item->product_variant_id,
                    'reason_code' => 'TRANSFER_IN',
                    'quantity' => $receivedQty,
                    'performed_by' => $userId,
                    'reference_type' => InventoryTransfer::class,
                    'reference_id' => $transfer->id,
                    'idempotency_key' => "transfer:{$transfer->id}:in:{$item->product_variant_id}",
                    'allow_negative' => true, // IN is always safe
                ]);

                $item->increment('received_quantity', $receivedQty);
            }

            $allReceived = $transfer->items
                ->every(fn($i) => $i->received_quantity >= $i->quantity);

            $transfer->update([
                'status_id' => $allReceived ? $closedStatusId : $receivedStatusId,
                'received_at' => now(),
            ]);
        });
    }
}
