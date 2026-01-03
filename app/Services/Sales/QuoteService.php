<?php

namespace App\Services\Sales;

use App\Models\Sales\{Quote, QuoteItem, QuoteStatus};
use App\Services\Common\DocumentNumberService;
use Illuminate\Support\Str;
use Carbon\Carbon;

class QuoteService
{
    public function __construct(
        protected DocumentNumberService $docService
    ) {}

    public function createDraft(array $data): Quote
    {
        $statusId = QuoteStatus::where('code', 'DRAFT')->value('id');

        return Quote::create([
            'uuid' => Str::uuid(),
            'company_id' => $data['company_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'status_id' => $statusId,
            'quote_number' => Str::uuid(), // Temporary UUID as quote number
            // 'quote_number' => $this->docService->generate(
            //     companyId: $data['company_id'],
            //     documentType: 'QUOTE',
            //     channel: 'SALES',
            //     year: now()->year
            // ),
            'currency_id' => $data['currency_id'],
            'payment_term_id' => $data['payment_term_id'],
            'payment_term_name' => $data['payment_term_name'],
            'payment_due_days' => $data['payment_due_days'],
            'created_by' => $data['user_id'],
            'valid_until' => Carbon::now()->addDays(15),
        ]);
    }

    public function addItem(Quote $quote, array $item): QuoteItem
    {
        if ($quote->status->is_final) {
            throw new \RuntimeException('Cannot modify finalized quote.');
        }

        return QuoteItem::create([
            'quote_id' => $quote->id,
            'product_variant_id' => $item['product_variant_id'],
            'product_name' => $item['product_name'],
            'variant_description' => $item['variant_description'] ?? null,
            'unit_price' => $item['unit_price'],
            'quantity' => $item['quantity'],
            'line_total' => bcmul($item['unit_price'], $item['quantity'], 6),
            'tax_amount' => $item['tax_amount'] ?? 0,
        ]);
    }
}
