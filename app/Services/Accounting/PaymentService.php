<?php

namespace App\Services\Accounting;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

use App\Models\Accounting\Payment;
use App\Models\Accounting\PaymentMethod;
use App\Models\Accounting\PaymentAllocation;
use App\Models\Company\Company;

class PaymentService
{
    /**
     * Create a single payment (money movement).
     * Allocation is OPTIONAL and handled separately.
     */
    public function create(array $data): Payment
    {
        return DB::transaction(function () use ($data) {

            $this->validateCreatePayload($data);

            // Idempotency protection
            if (!empty($data['idempotency_key'])) {
                $existing = Payment::where('company_id', $data['company_id'])
                    ->where('idempotency_key', $data['idempotency_key'])
                    ->first();

                if ($existing) {
                    return $existing;
                }
            }

            $company = Company::findOrFail($data['company_id']);

            $method = PaymentMethod::where('company_id', $company->id)
                ->where('code', strtoupper($data['method_code']))
                ->where('is_active', true)
                ->first();

            if (!$method) {
                throw new \RuntimeException("Payment method not configured: {$data['method_code']}");
            }

            if ($method->requires_reference && empty($data['reference'])) {
                throw new \RuntimeException('Payment reference is required.');
            }

            $paymentCurrencyId = (int) $data['payment_currency_id'];
            $baseCurrencyId    = (int) ($data['base_currency_id'] ?? $company->base_currency_id);

            $exchangeRate = (string) ($data['exchange_rate'] ?? '1.00000000');
            $amount       = (string) $data['amount'];

            $baseAmount = bcmul($amount, $exchangeRate, 6);

            return Payment::create([
                'uuid' => (string) Str::uuid(),

                'company_id' => $company->id,
                'payment_method_id' => $method->id,

                // SOURCE ONLY (NOT accounting logic)
                'payable_type' => $data['payable_type'] ?? null,
                'payable_id'   => $data['payable_id'] ?? null,

                'payment_currency_id' => $paymentCurrencyId,
                'base_currency_id'    => $baseCurrencyId,

                'amount'        => $amount,
                'exchange_rate' => $exchangeRate,
                'base_amount'   => $baseAmount,
                'fx_difference' => '0.000000',

                'direction' => $data['direction'] ?? 'IN',
                'status'    => $data['status'] ?? 'SUCCESS',

                'source'            => $data['source'] ?? null,
                'document_no'       => $data['document_no'] ?? null,
                'reference'         => $data['reference'] ?? null,
                'gateway_reference' => $data['gateway_reference'] ?? null,
                'gateway_payload'   => $data['gateway_payload'] ?? null,

                'idempotency_key' => $data['idempotency_key'] ?? null,
                'paid_at' => $data['paid_at'] ?? Carbon::now(),
            ]);
        });
    }

    /**
     * ✅ CREATE MULTIPLE PAYMENTS (POS / WEB SPLIT PAYMENTS)
     */
    public function createMany(array $payments, array $context): void
    {
        foreach ($payments as $payment) {
            $this->create(array_merge($payment, $context));
        }
    }

    /**
     * Allocate payment to ANY document
     * (Order / Invoice / Credit Note / Multiple Orders)
     */
    public function allocate(Payment $payment, array $data): PaymentAllocation
    {
        return DB::transaction(function () use ($payment, $data) {

            $this->validateAllocationPayload($data);

            $allocatedAmount = (string) $data['allocated_amount'];

            if (bccomp($allocatedAmount, '0', 6) <= 0) {
                throw new \RuntimeException('Allocated amount must be greater than zero.');
            }

            $exchangeRate = (string) ($data['exchange_rate'] ?? $payment->exchange_rate);
            $baseAllocated = bcmul($allocatedAmount, $exchangeRate, 6);

            return PaymentAllocation::create([
                'uuid' => (string) Str::uuid(),

                'company_id' => $payment->company_id,
                'payment_id' => $payment->id,

                'allocatable_type' => $data['allocatable_type'],
                'allocatable_id'   => $data['allocatable_id'],

                'payment_currency_id' => $payment->payment_currency_id,
                'base_currency_id'    => $payment->base_currency_id,

                'allocated_amount'      => $allocatedAmount,
                'exchange_rate'         => $exchangeRate,
                'base_allocated_amount' => $baseAllocated,

                'allocated_at' => $data['allocated_at'] ?? Carbon::now(),
            ]);
        });
    }

    /**
     * Allocate payment to multiple documents
     */
    public function allocateMany(Payment $payment, array $allocations): void
    {
        foreach ($allocations as $allocation) {
            $this->allocate($payment, $allocation);
        }
    }

    /**
     * Refund helper (OUT)
     */
    public function refund(array $data): Payment
    {
        $data['direction'] = 'OUT';
        $data['status']    = $data['status'] ?? 'REFUNDED';

        return $this->create($data);
    }

    protected function validateCreatePayload(array $data): void
    {
        foreach (
            ['company_id', 'method_code', 'payment_currency_id', 'amount'] as $field
        ) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing payment field: {$field}");
            }
        }
    }

    protected function validateAllocationPayload(array $data): void
    {
        foreach (
            ['allocatable_type', 'allocatable_id', 'allocated_amount'] as $field
        ) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("Missing allocation field: {$field}");
            }
        }
    }
}
