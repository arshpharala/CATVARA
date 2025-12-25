<?php

namespace App\Services\Customer;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

use App\Models\Customer\Customer;
use App\Models\Customer\CustomerAddress;
use App\Models\Pos\PosOrder;

class CustomerService
{
    /**
     * Quick create customer (POS use-case)
     * - phone/email optional, but at least display_name is required
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {

            $customer = Customer::create([
                'uuid' => Str::uuid(),
                'company_id' => $data['company_id'],
                'type' => $data['type'] ?? 'INDIVIDUAL',
                'display_name' => $data['display_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'legal_name' => $data['legal_name'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            if (!empty($data['address'])) {
                $this->addAddress($customer, $data['address']);
            }

            return $customer;
        });
    }

    /**
     * Add address (billing/shipping)
     */
    public function addAddress(Customer $customer, array $address): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $address) {

            // If setting default = true, unset other defaults for same type
            $isDefault = (bool)($address['is_default'] ?? false);
            $type = $address['type'] ?? 'SHIPPING';

            if ($isDefault) {
                CustomerAddress::where('customer_id', $customer->id)
                    ->where('type', $type)
                    ->update(['is_default' => false]);
            }

            return CustomerAddress::create([
                'uuid' => Str::uuid(),
                'company_id' => $customer->company_id,
                'customer_id' => $customer->id,
                'type' => $type,
                'is_default' => $isDefault,

                'contact_name' => $address['contact_name'] ?? null,
                'phone' => $address['phone'] ?? null,

                'address_line_1' => $address['address_line_1'],
                'address_line_2' => $address['address_line_2'] ?? null,
                'city' => $address['city'] ?? null,
                'state' => $address['state'] ?? null,
                'postal_code' => $address['postal_code'] ?? null,
                'country_code' => $address['country_code'] ?? null,
            ]);
        });
    }

    /**
     * Attach customer to a POS order
     * - Allowed only while order is DRAFT
     * - After invoice issuance later, we will lock it (in invoice rules)
     */
    public function attachCustomerToPosOrder(PosOrder $order, Customer $customer): PosOrder
    {
        if ($order->status !== 'DRAFT') {
            throw new \RuntimeException('Customer can only be attached while order is DRAFT.');
        }

        if ($order->company_id !== $customer->company_id) {
            throw new \RuntimeException('Customer does not belong to the same company.');
        }

        $order->update([
            'customer_id' => $customer->id,
        ]);

        return $order;
    }

    /**
     * Find customer by phone/email inside a company (POS lookup)
     */
    public function findByContact(int $companyId, ?string $phone = null, ?string $email = null): ?Customer
    {
        $q = Customer::query()->where('company_id', $companyId)->where('is_active', true);

        if ($phone) {
            $q->where('phone', $phone);
        }

        if ($email) {
            $q->where('email', $email);
        }

        return $q->first();
    }
}
