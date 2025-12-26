<?php

namespace App\Services\Web;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Web\{
    WebOrder,
    WebOrderItem,
    WebOrderStatus
};

use App\Models\Customer\Customer;
use App\Models\Accounting\PaymentTerm;

use App\Services\Pricing\PricingService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Common\DocumentNumberService;
use App\Services\Accounting\PaymentService;

class WebOrderService
{
    public function __construct(
        protected PricingService $pricingService,
        protected InventoryPostingService $inventoryService,
        protected DocumentNumberService $documentNumberService,
        protected PaymentService $paymentService
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

        $termSnapshot = $this->resolvePaymentTermSnapshot(
            companyId: (int) $data['company_id'],
            customerId: (int) $data['customer_id']
        );

        return WebOrder::create([
            'uuid' => Str::uuid(),
            'company_id' => $data['company_id'],
            'customer_id' => $data['customer_id'],
            'status_id' => $draftStatusId,
            'order_number' => $orderNumber,
            'currency_id' => $data['currency_id'],

            'payment_term_id' => $termSnapshot['id'],
            'payment_term_code' => $termSnapshot['code'],
            'payment_term_name' => $termSnapshot['name'],
            'payment_due_days' => $termSnapshot['due_days'],
        ]);
    }

    /**
     * Add item to web order
     */
    public function addItem(WebOrder $order, int $variantId, int $quantity): WebOrderItem
    {
        $order->loadMissing(['status', 'currency']);

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
            'product_name' => $priceData['product_name'] ?? 'Product',
            'variant_description' => $priceData['variant_description'] ?? null,
            'unit_price' => $priceData['price'],
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'tax_amount' => 0,
        ]);
    }

    /**
     * Place order (checkout initiated)
     */
    public function placeOrder(WebOrder $order, ?string $checkoutMethod = null): WebOrder
    {
        $order->load(['items', 'status']);

        if ($order->items->isEmpty()) {
            throw new \RuntimeException('Cannot place empty order.');
        }

        if ($order->status->is_final) {
            throw new \RuntimeException('Order already finalized.');
        }

        $subtotal   = $order->items->sum('line_total');
        $taxTotal   = $order->items->sum('tax_amount');
        $grandTotal = bcadd((string) $subtotal, (string) $taxTotal, 6);

        $pendingStatusId = WebOrderStatus::where('code', 'PENDING_PAYMENT')->value('id');
        if (!$pendingStatusId) {
            throw new \RuntimeException('Web pending payment status not configured.');
        }

        $placedAt = Carbon::now();
        $dueDays  = (int) ($order->payment_due_days ?? 0);
        $dueDate  = $dueDays > 0 ? (clone $placedAt)->addDays($dueDays) : $placedAt;

        // COD = credit sale
        $isCreditSale = strtoupper((string) $checkoutMethod) === 'COD';

        $order->update([
            'subtotal' => $subtotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'status_id' => $pendingStatusId,
            'placed_at' => $placedAt,
            'due_date' => $dueDate,
            'is_credit_sale' => $isCreditSale,
        ]);

        return $order;
    }

    /**
     * Mark order as PAID (uses PaymentService)
     *
     * $payments example:
     * [
     *   [
     *     'method_code' => 'STRIPE',
     *     'amount' => '100.00',
     *     'payment_currency_id' => 1,
     *     'exchange_rate' => '1.00000000',
     *     'reference' => 'pi_xxx',
     *     'gateway_reference' => 'pi_xxx'
     *   ]
     * ]
     */
    public function markPaid(WebOrder $order, array $payments): WebOrder
    {
        $order->load(['items', 'status']);

        if ($order->status->is_final) {
            throw new \RuntimeException('Order already finalized.');
        }

        if ($order->items->isEmpty()) {
            throw new \RuntimeException('Cannot mark paid: order has no items.');
        }

        return DB::transaction(function () use ($order, $payments) {

            // Ensure totals exist (if somehow markPaid called before placeOrder)
            $subtotal   = (string) ($order->subtotal ?? $order->items->sum('line_total'));
            $taxTotal   = (string) ($order->tax_total ?? $order->items->sum('tax_amount'));
            $grandTotal = (string) ($order->grand_total ?? bcadd($subtotal, $taxTotal, 6));

            /**
             * ✅ BC-safe paid amount validation
             */
            $paidAmount = '0.000000';
            foreach ($payments as $p) {
                $paidAmount = bcadd($paidAmount, (string) ($p['amount'] ?? '0'), 6);
            }

            if (bccomp($paidAmount, '0', 6) <= 0) {
                throw new \RuntimeException('Cannot mark paid with zero payment.');
            }

            if (bccomp($paidAmount, $grandTotal, 6) === 1) {
                throw new \RuntimeException('Payment exceeds order total.');
            }

            $paidStatusId = WebOrderStatus::where('code', 'PAID')->value('id');
            if (!$paidStatusId) {
                throw new \RuntimeException('Web PAID status not configured.');
            }

            /**
             * ✅ Record payments via centralized PaymentService
             */
            $normalizedPayments = array_map(function ($p) use ($order) {
                return [
                    'method_code' => $p['method_code'] ?? null,
                    'amount' => $p['amount'] ?? '0',
                    'reference' => $p['reference'] ?? null,
                    'document_no' => $p['document_no'] ?? null,
                    'gateway_reference' => $p['gateway_reference'] ?? null,
                    'gateway_payload' => $p['gateway_payload'] ?? null,
                    'payment_currency_id' => $p['payment_currency_id']
                        ?? ($p['currency_id'] ?? $order->currency_id),
                    'exchange_rate' => $p['exchange_rate'] ?? '1.00000000',
                    'idempotency_key' => $p['idempotency_key'] ?? null,
                    'paid_at' => $p['paid_at'] ?? now(),
                    'status' => $p['status'] ?? 'SUCCESS',
                    'direction' => $p['direction'] ?? 'IN',
                ];
            }, $payments);

            $this->paymentService->createMany(
                $normalizedPayments,
                [
                    'company_id' => $order->company_id,
                    'payable_type' => WebOrder::class,
                    'payable_id' => $order->id,
                    'source' => 'WEB',
                ]
            );

            /**
             * IMPORTANT:
             * Web inventory posting should happen at fulfillment/shipping, not here.
             * So we DO NOT post movements in markPaid.
             */

            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'status_id' => $paidStatusId,
            ]);

            return $order->refresh();
        });
    }

    /**
     * Resolve payment term snapshot
     */
    protected function resolvePaymentTermSnapshot(int $companyId, int $customerId): array
    {
        $term = null;

        $customer = Customer::where('company_id', $companyId)->find($customerId);
        if ($customer?->payment_term_id) {
            $term = PaymentTerm::find($customer->payment_term_id);
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
