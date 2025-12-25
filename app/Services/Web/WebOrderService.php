<?php

namespace App\Services\Web;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Web\{
    WebOrder,
    WebOrderItem,
    WebOrderStatus,
    WebPayment
};

use App\Services\Pricing\PricingService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Common\DocumentNumberService;

class WebOrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected InventoryPostingService $inventoryService,
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Create web order draft
     */
    public function createDraft(array $data): WebOrder
    {
        $orderNumber = $this->documentNumberService->generate(
            companyId: $data['company_id'],
            documentType: 'WEB_ORDER',
            channel: 'WEB',
            year: now()->year
        );

        $draftStatusId = WebOrderStatus::where('code', 'DRAFT')->value('id');

        if (!$draftStatusId) {
            throw new \RuntimeException('Web draft status not configured.');
        }

        return WebOrder::create([
            'uuid' => Str::uuid(),
            'company_id' => $data['company_id'],
            'customer_id' => $data['customer_id'],
            'status_id' => $draftStatusId,
            'order_number' => $orderNumber,
            'currency_id' => $data['currency_id'],
        ]);
    }

    /**
     * Add item to web order
     */
    public function addItem(WebOrder $order, int $variantId, int $quantity): WebOrderItem
    {
        $order->loadMissing('status');

        if ($order->status->is_final) {
            throw new \RuntimeException('Cannot modify finalized order.');
        }

        if ($quantity <= 0) {
            throw new \RuntimeException('Quantity must be greater than zero.');
        }

        $priceData = $this->pricingService->getPrice(
            companyId: $order->company_id,
            variantId: $variantId,
            channelCode: 'WEB',
            currencyCode: $order->currency->code,
            countryCode: null
        );

        $lineTotal = bcmul($priceData['price'], (string) $quantity, 6);

        return WebOrderItem::create([
            'web_order_id' => $order->id,
            'product_variant_id' => $variantId,
            'product_name' => $priceData['product_name'],
            'variant_description' => $priceData['variant_description'] ?? null,
            'unit_price' => $priceData['price'],
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'tax_amount' => 0,
        ]);
    }

    /**
     * Place order (payment initiated)
     */
    public function placeOrder(WebOrder $order): WebOrder
    {
        $order->load(['items', 'status']);

        if ($order->items->isEmpty()) {
            throw new \RuntimeException('Cannot place empty order.');
        }

        if ($order->status->is_final) {
            throw new \RuntimeException('Order already finalized.');
        }

        $subtotal = $order->items->sum('line_total');
        $taxTotal = $order->items->sum('tax_amount');
        $grandTotal = bcadd($subtotal, $taxTotal, 6);

        $pendingStatusId = WebOrderStatus::where('code', 'PENDING_PAYMENT')->value('id');

        $order->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'status_id' => $pendingStatusId,
            'placed_at' => Carbon::now(),
        ]);

        return $order;
    }

    /**
     * Confirm payment success
     */
    public function markPaid(WebOrder $order): WebOrder
    {
        $paidStatusId = WebOrderStatus::where('code', 'PAID')->value('id');

        if (!$paidStatusId) {
            throw new \RuntimeException('Paid status not configured.');
        }

        return DB::transaction(function () use ($order, $paidStatusId) {

            // Inventory posting (after payment)
            foreach ($order->items as $item) {
                $this->inventoryService->postMovement([
                    'company_id' => $order->company_id,
                    'inventory_location_id' => null, // resolved later (warehouse/store fulfillment)
                    'product_variant_id' => $item->product_variant_id,
                    'reason_code' => 'SALE',
                    'quantity' => $item->quantity,
                    'reference_type' => WebOrder::class,
                    'reference_id' => $order->id,
                    'idempotency_key' => "web:{$order->id}:sale:{$item->product_variant_id}",
                ]);
            }

            $order->update([
                'status_id' => $paidStatusId,
            ]);

            return $order;
        });
    }
}
