<?php

namespace App\Services\Pos;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Pos\{
    PosOrder,
    PosOrderItem,
    PosOrderStatus
};

use App\Models\Inventory\Store;
use App\Models\Inventory\CompanyInventorySetting;

use App\Models\Customer\Customer;
use App\Models\Accounting\PaymentTerm;

use App\Services\Pricing\PricingService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Common\DocumentNumberService;
use App\Services\Accounting\PaymentService;

class PosOrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected InventoryPostingService $inventoryService,
        protected DocumentNumberService $documentNumberService,
        protected PaymentService $paymentService
    ) {}

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

        $termSnapshot = $this->resolvePaymentTermSnapshot(
            companyId: (int) $data['company_id'],
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null
        );

        return PosOrder::create([
            'uuid' => Str::uuid(),
            'company_id' => $data['company_id'],
            'store_id' => $data['store_id'],
            'user_id' => $data['user_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'order_number' => $orderNumber,
            'status_id' => $draftStatusId,
            'currency_id' => $data['currency_id'],

            'payment_term_id' => $termSnapshot['id'],
            'payment_term_code' => $termSnapshot['code'],
            'payment_term_name' => $termSnapshot['name'],
            'payment_due_days' => $termSnapshot['due_days'],
        ]);
    }

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
    public function completeOrder(PosOrder $order, array $payments = [], float $shipping = 0): PosOrder
    {
        $order->loadMissing(['status', 'items', 'store']);

        if ($order->status->is_final) {
            throw new \RuntimeException('Order already finalized.');
        }

        if ($order->items->isEmpty()) {
            throw new \RuntimeException('Cannot complete an empty order.');
        }

        return DB::transaction(function () use ($order, $payments, $shipping) {

            $subtotal   = $order->items->sum('line_total');
            $grandTotal = bcadd((string) $subtotal, (string) $shipping, 6);

            /**
             * ✅ BC-safe paid amount calculation
             */
            $paidAmount = '0.000000';
            foreach ($payments as $p) {
                $paidAmount = bcadd(
                    $paidAmount,
                    (string) ($p['amount'] ?? '0'),
                    6
                );
            }

            if (bccomp($paidAmount, (string) $grandTotal, 6) === 1) {
                throw new \RuntimeException('Payment exceeds order total.');
            }

            $inventoryLocationId = $this->resolveStoreInventoryLocation($order->store);

            $settings = CompanyInventorySetting::where('company_id', $order->company_id)->first();
            if (!$settings) {
                throw new \RuntimeException('Company inventory settings not configured.');
            }

            foreach ($order->items as $item) {
                $availableQty = $this->inventoryService->getAvailableStock(
                    $order->company_id,
                    $inventoryLocationId,
                    $item->product_variant_id
                );

                if (
                    !$settings->allow_negative_stock &&
                    $settings->block_sale_if_no_stock &&
                    $availableQty < $item->quantity
                ) {
                    throw new \RuntimeException(
                        "Insufficient stock for variant {$item->product_variant_id}. Available: {$availableQty}, Required: {$item->quantity}"
                    );
                }
            }

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

            $completedStatusId = PosOrderStatus::where('code', 'COMPLETED')->value('id');
            if (!$completedStatusId) {
                throw new \RuntimeException('POS completed status not configured.');
            }

            $hasCreditMethod = collect($payments)->contains(
                fn($p) => strtoupper($p['method_code'] ?? '') === 'CREDIT'
            );

            $isCreditSale = $hasCreditMethod
                || bccomp($paidAmount, (string) $grandTotal, 6) === -1;

            $completedAt = Carbon::now();
            $dueDays = (int) ($order->payment_due_days ?? 0);
            $dueDate = $dueDays > 0 ? (clone $completedAt)->addDays($dueDays) : $completedAt;

            $order->update([
                'subtotal' => $subtotal,
                'shipping_amount' => $shipping,
                'grand_total' => $grandTotal,
                'status_id' => $completedStatusId,
                'completed_at' => $completedAt,
                'is_credit_sale' => $isCreditSale,
                'due_date' => $dueDate,
            ]);

            /**
             * ✅ Centralized payment recording
             */
            if (!empty($payments)) {
                $normalizedPayments = array_map(function ($p) use ($order, $completedAt) {
                    return [
                        'method_code' => $p['method_code'] ?? null,
                        'amount' => $p['amount'] ?? '0',
                        'reference' => $p['reference'] ?? null,
                        'document_no' => $p['document_no'] ?? null,
                        'gateway_reference' => $p['gateway_reference'] ?? null,
                        'gateway_payload' => $p['gateway_payload'] ?? null,
                        'payment_currency_id' => $p['payment_currency_id'] ?? $order->currency_id,
                        'exchange_rate' => $p['exchange_rate'] ?? '1.00000000',
                        'idempotency_key' => $p['idempotency_key'] ?? null,
                        'paid_at' => $p['paid_at'] ?? $completedAt,
                    ];
                }, $payments);

                $this->paymentService->createMany(
                    $normalizedPayments,
                    [
                        'company_id'   => $order->company_id,
                        'payable_type' => PosOrder::class,
                        'payable_id'   => $order->id,
                        'source'       => 'POS',
                    ]
                );
            }

            return $order->refresh();
        });
    }

    protected function resolveStoreInventoryLocation(Store $store): int
    {
        $store->loadMissing('inventoryLocation');

        if (!$store->inventoryLocation) {
            throw new \RuntimeException('Store inventory location not configured.');
        }

        return $store->inventoryLocation->id;
    }

    protected function resolvePaymentTermSnapshot(int $companyId, ?int $customerId): array
    {
        $term = null;

        if ($customerId) {
            $customer = Customer::where('company_id', $companyId)->find($customerId);
            if ($customer?->payment_term_id) {
                $term = PaymentTerm::find($customer->payment_term_id);
            }
        }

        if (!$term) {
            $term = PaymentTerm::query()
                ->select('payment_terms.*')
                ->join('company_payment_terms', 'company_payment_terms.payment_term_id', '=', 'payment_terms.id')
                ->where('company_payment_terms.company_id', $companyId)
                ->where('company_payment_terms.is_default', 1)
                ->where('payment_terms.is_active', 1)
                ->first();
        }

        return [
            'id' => $term?->id,
            'code' => $term?->code,
            'name' => $term?->name,
            'due_days' => (int) ($term?->due_days ?? 0),
        ];
    }
}
