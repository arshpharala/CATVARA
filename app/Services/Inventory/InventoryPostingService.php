<?php

namespace App\Services\Inventory;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\InventoryReason;

class InventoryPostingService
{
    /**
     * Get available stock for a variant at a specific inventory location
     */
    public function getAvailableStock(
        int $companyId,
        int $inventoryLocationId,
        int $productVariantId
    ): float {
        return (float) InventoryBalance::where('company_id', $companyId)
            ->where('inventory_location_id', $inventoryLocationId)
            ->where('product_variant_id', $productVariantId)
            ->value('quantity') ?? 0;
    }

    /**
     * Post a single inventory movement safely (LEDGER + BALANCE).
     */
    public function postMovement(array $data): InventoryMovement
    {
        return DB::transaction(function () use ($data) {

            /**
             * 1️⃣ Resolve inventory reason
             */
            $reason = InventoryReason::where('code', $data['reason_code'])
                ->where('is_active', true)
                ->firstOrFail();

            /**
             * 2️⃣ Determine signed quantity
             */
            $signedQty = $reason->is_increase
                ? abs($data['quantity'])
                : -abs($data['quantity']);

            /**
             * 3️⃣ Idempotency check (SAFE RETRY)
             */
            if (!empty($data['idempotency_key'])) {
                $existing = InventoryMovement::where('company_id', $data['company_id'])
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            /**
             * 4️⃣ Lock or create inventory balance
             */
            $balance = InventoryBalance::where('company_id', $data['company_id'])
                ->where('inventory_location_id', $data['inventory_location_id'])
                ->where('product_variant_id', $data['product_variant_id'])
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                $balance = InventoryBalance::create([
                    'uuid' => Str::uuid(),
                    'company_id' => $data['company_id'],
                    'inventory_location_id' => $data['inventory_location_id'],
                    'product_variant_id' => $data['product_variant_id'],
                    'quantity' => 0,
                ]);
            }

            /**
             * 5️⃣ Negative stock enforcement (FINAL AUTHORITY)
             */
            $newQty = bcadd((string) $balance->quantity, (string) $signedQty, 6);

            $allowNegative = (bool) ($data['allow_negative'] ?? false);

            if (!$allowNegative && bccomp($newQty, '0', 6) === -1) {
                throw new \RuntimeException('Insufficient stock for this operation.');
            }

            /**
             * 6️⃣ Create inventory movement (LEDGER)
             */
            $movement = InventoryMovement::create([
                'uuid' => Str::uuid(),
                'company_id' => $data['company_id'],
                'inventory_location_id' => $data['inventory_location_id'],
                'product_variant_id' => $data['product_variant_id'],
                'inventory_reason_id' => $reason->id,
                'quantity' => $signedQty,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'performed_by' => $data['performed_by'] ?? null,
                'idempotency_key' => $data['idempotency_key'] ?? null,
                'occurred_at' => $data['occurred_at'] ?? Carbon::now(),
                'posted_at' => Carbon::now(),
            ]);

            /**
             * 7️⃣ Update balance cache
             */
            $balance->update([
                'quantity' => $newQty,
                'last_movement_at' => Carbon::now(),
            ]);

            return $movement;
        });
    }

    /**
     * Post inventory transfer (OUT + IN movements)
     */
    public function postTransfer(array $data): void
    {
        /**
         * Required:
         * - company_id
         * - from_location_id
         * - to_location_id
         * - product_variant_id
         * - quantity
         * - performed_by
         * - transfer_id
         */

        // OUT
        $this->postMovement([
            'company_id' => $data['company_id'],
            'inventory_location_id' => $data['from_location_id'],
            'product_variant_id' => $data['product_variant_id'],
            'reason_code' => 'TRANSFER_OUT',
            'quantity' => $data['quantity'],
            'reference_type' => 'inventory_transfer',
            'reference_id' => $data['transfer_id'],
            'performed_by' => $data['performed_by'],
            'idempotency_key' => "transfer:{$data['transfer_id']}:out:{$data['product_variant_id']}",
            'allow_negative' => false,
        ]);

        // IN
        $this->postMovement([
            'company_id' => $data['company_id'],
            'inventory_location_id' => $data['to_location_id'],
            'product_variant_id' => $data['product_variant_id'],
            'reason_code' => 'TRANSFER_IN',
            'quantity' => $data['quantity'],
            'reference_type' => 'inventory_transfer',
            'reference_id' => $data['transfer_id'],
            'performed_by' => $data['performed_by'],
            'idempotency_key' => "transfer:{$data['transfer_id']}:in:{$data['product_variant_id']}",
            'allow_negative' => true,
        ]);
    }
}
