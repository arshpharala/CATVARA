<?php

namespace App\Services\Returns;

use App\Models\Inventory\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use App\Models\Returns\CreditNote;
use App\Models\Returns\CreditNoteItem;

use App\Services\Inventory\InventoryPostingService;
use App\Services\Common\DocumentNumberService;
use App\Services\Accounting\PaymentService;

class CreditNoteService
{
    public function __construct(
        protected InventoryPostingService $inventoryPostingService,
        protected DocumentNumberService $documentNumberService,
        protected PaymentService $paymentService
    ) {}

    public function createDraft(array $data): CreditNote
    {
        $creditNumber = $this->documentNumberService->generate(
            companyId: $data['company_id'],
            documentType: 'CREDIT_NOTE',
            year: now()->year
        );

        return CreditNote::create([
            'uuid' => Str::uuid(),
            'company_id' => $data['company_id'],
            'store_id' => $data['store_id'] ?? null,

            'creditable_type' => $data['creditable_type'],
            'creditable_id' => $data['creditable_id'],

            'user_id' => $data['user_id'] ?? null,

            'credit_number' => $creditNumber,
            'status' => 'DRAFT',

            'currency_id' => $data['currency_id'],
            'reason' => $data['reason'] ?? null,
        ]);
    }

    public function addItem(CreditNote $note, array $item): CreditNoteItem
    {
        if ($note->status !== 'DRAFT') {
            throw new \RuntimeException('Cannot modify non-draft credit note.');
        }

        $qty = (int) $item['quantity'];
        if ($qty <= 0) {
            throw new \RuntimeException('Quantity must be greater than zero.');
        }

        $unitPrice = (string) $item['unit_price'];
        $lineTotal = bcmul($unitPrice, (string) $qty, 6);

        return CreditNoteItem::updateOrCreate(
            [
                'credit_note_id' => $note->id,
                'product_variant_id' => $item['product_variant_id'],
            ],
            [
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'tax_amount' => $item['tax_amount'] ?? 0,
                'source_item_type' => $item['source_item_type'] ?? null,
                'source_item_id' => $item['source_item_id'] ?? null,
            ]
        );
    }

    /**
     * Issue Credit Note (inventory impact only)
     */
    public function issue(CreditNote $note): CreditNote
    {
        if ($note->status !== 'DRAFT') {
            throw new \RuntimeException('Only draft credit notes can be issued.');
        }

        return DB::transaction(function () use ($note) {

            $note->load('items');

            if ($note->items->isEmpty()) {
                throw new \RuntimeException('Credit note must have at least one item.');
            }

            $subtotal   = $note->items->sum('line_total');
            $taxTotal   = $note->items->sum('tax_amount');
            $grandTotal = bcadd(
                bcadd((string) $subtotal, (string) $taxTotal, 6),
                (string) $note->shipping_refund,
                6
            );

            /**
             * INVENTORY RETURN
             */
            foreach ($note->items as $item) {
                $this->inventoryPostingService->postMovement([
                    'company_id' => $note->company_id,
                    'inventory_location_id' => $this->resolveReturnLocationId($note),
                    'product_variant_id' => $item->product_variant_id,
                    'reason_code' => 'RETURN_IN',
                    'quantity' => $item->quantity,
                    'performed_by' => $note->user_id,
                    'reference_type' => CreditNote::class,
                    'reference_id' => $note->id,
                    'idempotency_key' => "creditnote:{$note->id}:returnin:{$item->product_variant_id}",
                ]);
            }

            $note->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'status' => 'ISSUED',
                'issued_at' => Carbon::now(),
            ]);

            return $note;
        });
    }

    /**
     * Refund money against credit note (OUT payment)
     * Allocation is OPTIONAL and done separately.
     */
    public function refund(CreditNote $note, array $refund)
    {
        if ($note->status !== 'ISSUED') {
            throw new \RuntimeException('Refund allowed only for issued credit notes.');
        }

        return $this->paymentService->create([
            'company_id' => $note->company_id,
            'method_code' => $refund['method_code'],
            'amount' => $refund['amount'],

            'payment_currency_id' => $refund['payment_currency_id'] ?? $note->currency_id,
            'exchange_rate' => $refund['exchange_rate'] ?? '1.00000000',

            'direction' => 'OUT',
            'status' => 'SUCCESS',
            'source' => 'CREDIT_NOTE',

            // SOURCE REFERENCE ONLY
            'payable_type' => CreditNote::class,
            'payable_id' => $note->id,

            'reference' => $refund['reference'] ?? null,
            'paid_at' => $refund['paid_at'] ?? now(),
        ]);
    }

    protected function resolveReturnLocationId(CreditNote $note): int
    {
        if (!$note->store_id) {
            throw new \RuntimeException('Return inventory location not resolved.');
        }

        $store = Store::with('inventoryLocation')
            ->findOrFail($note->store_id);

        if (!$store->inventoryLocation) {
            throw new \RuntimeException('Store inventory location not configured.');
        }

        return $store->inventoryLocation->id;
    }
}
