<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\Accounting\{Invoice, InvoiceItem, InvoiceAddress, InvoiceStatus};
use App\Services\Common\DocumentNumberService;

class InvoiceService
{
    public function __construct(
        protected DocumentNumberService $documentNumberService
    ) {}

    /**
     * Create invoice from any source order (POS/Web/B2B later).
     * - stores snapshot for audit (names, sku, prices)
     * - stores payment term snapshot (due days + due date)
     */
    public function createFromSource(object $order, array $options = []): Invoice
    {
        return DB::transaction(function () use ($order, $options) {

            $issuedStatusId = InvoiceStatus::where('code', 'ISSUED')->value('id');
            if (!$issuedStatusId) {
                throw new \RuntimeException('Invoice statuses not seeded (ISSUED missing).');
            }

            $invoiceNumber = $this->documentNumberService->generate(
                companyId: $order->company_id,
                documentType: 'INVOICE',
                channel: $options['channel'] ?? 'SYSTEM',
                year: now()->year
            );

            // Payment term snapshot (order should already have resolved term, but we safely fallback)
            $dueDays = (int) ($order->payment_due_days ?? 0);
            $dueDate = $order->placed_at ?? now();
            $dueDate = \Carbon\Carbon::parse($dueDate)->addDays($dueDays)->toDateString();

            $invoice = Invoice::create([
                'uuid' => Str::uuid(),
                'company_id' => $order->company_id,
                'store_id' => $order->store_id ?? null,
                'customer_id' => $order->customer_id ?? null,

                'status_id' => $issuedStatusId,
                'invoice_number' => $invoiceNumber,

                'source_type' => get_class($order),
                'source_id' => $order->id,

                'currency_id' => $order->currency_id,

                'payment_term_id' => $order->payment_term_id ?? null,
                'payment_due_days' => $dueDays,
                'due_date' => $dueDate,

                'subtotal' => $order->subtotal ?? 0,
                'tax_total' => $order->tax_total ?? 0,
                'discount_total' => $order->discount_total ?? 0,
                'shipping_amount' => $order->shipping_amount ?? 0,
                'grand_total' => $order->grand_total ?? 0,

                'exchange_rate' => $order->exchange_rate ?? null,

                'issued_at' => now(),
                'created_by' => $options['created_by'] ?? null,
            ]);

            // Items snapshot
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_variant_id' => $item->product_variant_id ?? null,
                    'product_name' => $item->product_name ?? ($item->variant?->product?->name ?? 'Item'),
                    'variant_description' => $item->variant_description ?? null,
                    'sku' => $item->sku ?? null,
                    'unit_price' => $item->unit_price,
                    'quantity' => $item->quantity,
                    'line_total' => $item->line_total,
                    'tax_amount' => $item->tax_amount ?? 0,
                    'discount_amount' => $item->discount_amount ?? 0,
                ]);
            }

            // Address snapshot (for web orders if you have them)
            if (method_exists($order, 'addresses')) {
                $order->loadMissing('addresses');

                foreach ($order->addresses as $addr) {
                    InvoiceAddress::create([
                        'invoice_id' => $invoice->id,
                        'type' => $addr->type,
                        'contact_name' => $addr->contact_name ?? null,
                        'email' => $addr->email ?? null,
                        'phone' => $addr->phone ?? null,
                        'address_line_1' => $addr->address_line_1 ?? null,
                        'address_line_2' => $addr->address_line_2 ?? null,
                        'city' => $addr->city ?? null,
                        'state' => $addr->state ?? null,
                        'postal_code' => $addr->postal_code ?? null,
                        'country_code' => $addr->country_code ?? null,
                    ]);
                }
            }

            return $invoice;
        });
    }
}
