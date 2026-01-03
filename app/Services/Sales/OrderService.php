<?php

namespace App\Services\Sales;

use App\Models\Sales\{
    Order,
    OrderItem,
    OrderStatus,
    Quote
};
use App\Services\Common\DocumentNumberService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderService
{
    public function __construct(
        protected DocumentNumberService $docService
    ) {}

    /**
     * Create order from Quote / POS / Web
     */
    public function create(array $data): Order
    {
        $statusId = OrderStatus::where('code', 'DRAFT')->value('id');

        $dueDate = !empty($data['payment_due_days'])
            ? Carbon::now()->addDays($data['payment_due_days'])
            : null;

        return Order::create([
            'uuid' => Str::uuid(),
            'company_id' => $data['company_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'status_id' => $statusId,

            'source' => $data['source'] ?? null,
            'source_id' => $data['source_id'] ?? null,

            'order_number' => $this->docService->generate(
                companyId: $data['company_id'],
                documentType: 'ORDER',
                channel: 'SALES',
                year: now()->year
            ),

            'currency_id' => $data['currency_id'],

            // Payment term snapshot
            'payment_term_id' => $data['payment_term_id'] ?? null,
            'payment_term_name' => $data['payment_term_name'] ?? null,
            'payment_due_days' => $data['payment_due_days'] ?? null,
            'due_date' => $dueDate,

            'created_by' => $data['user_id'] ?? null,
        ]);
    }

    /**
     * Create order from an accepted Quote
     */
    public function createFromQuote(Quote $quote): Order
    {
        return DB::transaction(function () use ($quote) {

            $quote->loadMissing('items');

            // Create the order
            $order = $this->create([
                'company_id' => $quote->company_id,
                'customer_id' => $quote->customer_id,
                'source' => 'QUOTE',
                'source_id' => $quote->id,
                'currency_id' => $quote->currency_id,
                'payment_term_id' => $quote->payment_term_id,
                'payment_term_name' => $quote->payment_term_name,
                'payment_due_days' => $quote->payment_due_days,
                'user_id' => auth()->id(),
            ]);

            // Copy quote items to order items
            foreach ($quote->items as $quoteItem) {
                $this->addItem($order, [
                    'product_variant_id' => $quoteItem->product_variant_id,
                    'product_name' => $quoteItem->product_name,
                    'variant_description' => $quoteItem->variant_description,
                    'unit_price' => $quoteItem->unit_price,
                    'quantity' => $quoteItem->quantity,
                    'tax_amount' => $quoteItem->tax_amount,
                ]);
            }

            // Calculate totals
            $order->load('items');
            $subtotal = $order->items->sum('line_total');
            $taxTotal = $order->items->sum('tax_amount');
            $grandTotal = bcadd($subtotal, $taxTotal, 6);

            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
            ]);

            return $order;
        });
    }

    public function addItem(Order $order, array $item): OrderItem
    {
        $order->loadMissing('status');

        if ($order->status->is_final) {
            throw new \RuntimeException('Cannot modify finalized order.');
        }

        return OrderItem::updateOrCreate(
            [
                'order_id' => $order->id,
                'product_variant_id' => $item['product_variant_id'],
            ],
            [
                'product_name' => $item['product_name'],
                'variant_description' => $item['variant_description'] ?? null,
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'line_total' => bcmul($item['unit_price'], $item['quantity'], 6),
                'tax_amount' => $item['tax_amount'] ?? 0,
            ]
        );
    }

    /**
     * Confirm order (locks pricing + totals)
     */
    public function confirm(Order $order): Order
    {
        $confirmedStatusId = OrderStatus::where('code', 'CONFIRMED')->value('id');

        return DB::transaction(function () use ($order, $confirmedStatusId) {

            $order->load('items');

            if ($order->items->isEmpty()) {
                throw new \RuntimeException('Cannot confirm empty order.');
            }

            $subtotal = $order->items->sum('line_total');
            $taxTotal = $order->items->sum('tax_amount');
            $grandTotal = bcadd($subtotal, $taxTotal, 6);

            $order->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'status_id' => $confirmedStatusId,
                'confirmed_at' => now(),
            ]);

            return $order;
        });
    }
}
