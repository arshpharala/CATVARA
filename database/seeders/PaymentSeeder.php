<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Company\Company;
use App\Models\Accounting\PaymentMethod;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        Company::all()->each(function (Company $company) {

            $methods = [
                ['code' => 'CASH',   'name' => 'Cash',        'type' => 'CASH'],
                ['code' => 'CARD',   'name' => 'Card',        'type' => 'CARD'],
                ['code' => 'BANK',   'name' => 'Bank Transfer', 'type' => 'BANK'],
                ['code' => 'STRIPE', 'name' => 'Stripe',      'type' => 'GATEWAY'],
                ['code' => 'PAYPAL', 'name' => 'PayPal',      'type' => 'GATEWAY'],
                ['code' => 'CREDIT', 'name' => 'On Credit',   'type' => 'CREDIT'],
            ];

            foreach ($methods as $m) {
                PaymentMethod::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'code' => $m['code'],
                    ],
                    [
                        'uuid' => Str::uuid(),
                        'name' => $m['name'],
                        'type' => $m['type'],
                        'is_active' => true,
                        'allow_refund' => true,
                        'requires_reference' => in_array($m['code'], ['BANK', 'CARD']),
                    ]
                );
            }
        });
    }
}
