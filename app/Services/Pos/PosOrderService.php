<?php

namespace App\Services\Pos;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Pos\{
    PosOrder,
    PosOrderItem,
    PosOrderStatus,
    PosPayment
};

use App\Models\Inventory\Store;
use App\Models\Inventory\CompanyInventorySetting;

use App\Services\Pricing\PricingService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Common\DocumentNumberService;

class PosOrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected InventoryPostingService $inventoryService,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Create a draft POS order
     */
    public function createDraft(array $data): PosOrder
    {
        $orderNumber = $this->documentNumberService->generate(
            companyId: $data['company_id'],
            documentType: 'POS_ORDER',
            channel: 'POS',
            year: now()->year
        );

        $draftStatusId = PosOrderStatus::where('code', 'DRAFT')->value('id');

        if (!$draftStatusId) {
            throw new \RuntimeException('POS draft status not configured.');
        }

        return PosOrder::create([
            'uuid' => Str::uuid(),
            'company_id' => $data['company_id'],
            'store_id' => $data['store_id'],
            'user_id' => $data['user_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'order_number' => $orderNumber,
            'status_id' => $draftStatusId,
            'currency_id' => $data['currency_id'],
        ]);
    }

    /**
     * Add item to draft order
     */
    public function addItem(PosOrder $order, int $variantId, int $quantity): PosOrderItem
    {
        $order->loadMissing('status');

        if ($order->status->is_final) {
            throw new \RuntimeException('Cannot modify a finalized order.');
        }

        if ($quantity <= 0) {
            throw new \RuntimeException('Quantity must be greater than zero.');
        }

        $order->loadMissing(['currency', 'store']);

        $priceData = $this->pricingService->getPrice(
            companyId: $order->company_id,
            variantId: $variantId,
            channelCode: 'POS',
            currencyCode: $order->currency->code,
            countryCode: $order->store->country_code ?? null,
            storeId: $order->store_id
        );

        $lineTotal = bcmul($priceData['price'], (string) $quantity, 6);

        return PosOrderItem::create([
            'pos_order_id' => $order->id,
            'product_variant_id' => $variantId,
            'unit_price' => $priceData['price'],
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'tax_amount' => 0,
        ]);
    }

    /**
     * Complete POS order (FINAL SALE)
     */
    public function completeOrder(
        PosOrder $order,
        array $payments = [],
        float $shipping = 0
    ): PosOrder {

        $order->loadMissing('status');

        if ($order->status->is_final) {
            throw new \RuntimeException('Order already finalized.');
        }

        return DB::transaction(function () use ($order, $payments, $shipping) {

            $order->load(['items', 'store']);

            if ($order->items->isEmpty()) {
                throw new \RuntimeException('Cannot complete an empty order.');
            }

            // Totals
            $subtotal   = $order->items->sum('line_total');
            $grandTotal = bcadd((string) $subtotal, (string) $shipping, 6);

            // Payment validation
            $paidAmount = collect($payments)->sum('amount');

            if ($paidAmount > $grandTotal) {
                throw new \RuntimeException('Payment exceeds order total.');
            }

            // Resolve inventory location
            $inventoryLocationId = $this->resolveStoreInventoryLocation($order->store);

            // Load company inventory settings
            $settings = CompanyInventorySetting::where(
                'company_id',
                $order->company_id
            )->first();

            if (!$settings) {
                throw new \RuntimeException('Company inventory settings not configured.');
            }

            /**
             * 🔒 STOCK VALIDATION (CONFIG-AWARE)
             */
            foreach ($order->items as $item) {

                $availableQty = $this->inventoryService->getAvailableStock(
                    companyId: $order->company_id,
                    inventoryLocationId: $inventoryLocationId,
                    variantId: $item->product_variant_id
                );

                if (
                    !$settings->allow_negative_stock &&
                    $settings->block_sale_if_no_stock &&
                    $availableQty < $item->quantity
                ) {
                    throw new \RuntimeException(
                        "Insufficient stock for product variant ID {$item->product_variant_id}. " .
                            "Available: {$availableQty}, Required: {$item->quantity}"
                    );
                }
            }

            /**
             * INVENTORY POSTING (SALE)
             */
            foreach ($order->items as $item) {

                $this->inventoryService->postMovement([
                    'company_id' => $order->company_id,
                    'inventory_location_id' => $inventoryLocationId,
                    'product_variant_id' => $item->product_variant_id,
                    'reason_code' => 'SALE',
                    'quantity' => $item->quantity,
                    'performed_by' => $order->user_id,
                    'reference_type' => PosOrder::class,
                    'reference_id' => $order->id,
                    'idempotency_key' => "pos:{$order->id}:sale:{$item->product_variant_id}",
                ]);
            }

            // Mark completed
            $completedStatusId = PosOrderStatus::where('code', 'COMPLETED')->value('id');

            if (!$completedStatusId) {
                throw new \RuntimeException('POS completed status not configured.');
            }

            $order->update([
                'subtotal'       => $subtotal,
                'shipping_amount' => $shipping,
                'grand_total'    => $grandTotal,
                'status_id'      => $completedStatusId,
                'completed_at'   => Carbon::now(),
            ]);

            // Save payments
            foreach ($payments as $payment) {
                PosPayment::create([
                    'pos_order_id' => $order->id,
                    'method'       => $payment['method'],
                    'amount'       => $payment['amount'],
                    'reference'    => $payment['reference'] ?? null,
                ]);
            }

            return $order;
        });
    }

    /**
     * Resolve store inventory location
     */
    protected function resolveStoreInventoryLocation(Store $store): int
    {
        $store->loadMissing('inventoryLocation');

        if (!$store->inventoryLocation) {
            throw new \RuntimeException('Store inventory location not configured.');
        }

        return $store->inventoryLocation->id;
    }
}
