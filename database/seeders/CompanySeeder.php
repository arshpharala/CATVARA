<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\Auth\Role;
use App\Models\Company\Company;
use App\Models\Company\CompanyDetail;
use App\Models\Company\CompanyStatus;

// ✅ PAYMENT TERMS
use App\Models\Accounting\PaymentTerm;
use App\Models\Accounting\CompanyPaymentTerm;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        /**
         * COMPANY STATUS
         */
        $activeStatus = CompanyStatus::updateOrCreate(
            ['code' => 'ACTIVE'],
            ['name' => 'Active', 'is_active' => true]
        );

        /**
         * PAYMENT TERMS (MASTER)
         * Safe to run multiple times
         */
        $paymentTerms = [
            ['code' => 'IMMEDIATE', 'name' => 'Immediate Payment', 'due_days' => 0],
            ['code' => 'NET_7',     'name' => 'Net 7 Days',        'due_days' => 7],
            ['code' => 'NET_15',    'name' => 'Net 15 Days',       'due_days' => 15],
            ['code' => 'NET_30',    'name' => 'Net 30 Days',       'due_days' => 30],
            ['code' => 'NET_60',    'name' => 'Net 60 Days',       'due_days' => 60],
        ];

        foreach ($paymentTerms as $term) {
            PaymentTerm::updateOrCreate(
                ['code' => $term['code']],
                array_merge($term, ['is_active' => true])
            );
        }

        /**
         * FULL COMPANY DEFINITION
         */
        $companies = [
            [
                'company' => [
                    'name' => 'London Trade',
                    'legal_name' => 'London Trade Limited',
                    'code' => 'UK-TRADE',
                    'website_url' => 'https://londontrade.co.uk',
                ],

                'details' => [
                    'address' => '221B Baker Street, London, UK',
                    'tax_number' => 'GB123456789',
                    'invoice_prefix' => 'UK',
                    'quote_prefix' => 'QT',
                ],

                'roles' => [
                    'admin'   => 'Admin',
                    'manager' => 'Manager',
                    'staff'   => 'Staff',
                ],

                // 🔹 DEFAULT PAYMENT TERM FOR COMPANY
                'default_payment_term' => 'NET_30',

                'users' => [
                    [
                        'name' => 'UK Admin',
                        'email' => 'admin@uk-trade.com',
                        'role' => 'admin',
                        'is_owner' => true,
                    ],
                    [
                        'name' => 'UK Manager',
                        'email' => 'manager@uk-trade.com',
                        'role' => 'manager',
                        'is_owner' => false,
                    ],
                ],
            ],

            [
                'company' => [
                    'name' => 'California Wholesale',
                    'legal_name' => 'California Wholesale Inc',
                    'code' => 'US-WHOLE',
                    'website_url' => 'https://calwholesale.com',
                ],

                'details' => [
                    'address' => '500 Market Street, San Francisco, USA',
                    'tax_number' => 'US98-7654321',
                    'invoice_prefix' => 'US',
                    'quote_prefix' => 'QT',
                ],

                'roles' => [
                    'admin'   => 'Admin',
                    'manager' => 'Manager',
                    'staff'   => 'Staff',
                ],

                'default_payment_term' => 'IMMEDIATE',

                'users' => [
                    [
                        'name' => 'US Admin',
                        'email' => 'admin@us-whole.com',
                        'role' => 'admin',
                        'is_owner' => true,
                    ],
                    [
                        'name' => 'US Staff',
                        'email' => 'staff@us-whole.com',
                        'role' => 'staff',
                        'is_owner' => false,
                    ],
                ],
            ],
        ];

        /**
         * PROCESS EACH COMPANY
         */
        foreach ($companies as $entry) {

            /**
             * COMPANY
             */
            $company = Company::updateOrCreate(
                ['code' => $entry['company']['code']],
                array_merge($entry['company'], [
                    'uuid' => Str::uuid(),
                    'company_status_id' => $activeStatus->id,
                ])
            );

            /**
             * COMPANY DETAILS
             */
            CompanyDetail::updateOrCreate(
                ['company_id' => $company->id],
                array_merge($entry['details'], [
                    'invoice_postfix' => date('Y'),
                    'quote_postfix' => date('Y'),
                ])
            );

            /**
             * COMPANY PAYMENT TERMS
             */
            $defaultTerm = PaymentTerm::where('code', $entry['default_payment_term'])->firstOrFail();

            PaymentTerm::all()->each(function (PaymentTerm $term) use ($company, $defaultTerm) {
                CompanyPaymentTerm::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'payment_term_id' => $term->id,
                    ],
                    [
                        'is_default' => $term->id === $defaultTerm->id,
                    ]
                );
            });

            /**
             * ROLES
             */
            $roleMap = [];

            foreach ($entry['roles'] as $slug => $name) {
                $roleMap[$slug] = Role::updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'slug' => $slug,
                    ],
                    [
                        'name' => $name,
                        'is_active' => true,
                    ]
                );
            }

            /**
             * USERS + ASSIGNMENTS
             */
            foreach ($entry['users'] as $userData) {

                $user = User::updateOrCreate(
                    ['email' => $userData['email']],
                    [
                        'uuid' => Str::uuid(),
                        'name' => $userData['name'],
                        'password' => Hash::make('password'),
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]
                );

                DB::table('company_user')->updateOrInsert(
                    [
                        'company_id' => $company->id,
                        'user_id' => $user->id,
                    ],
                    [
                        'is_owner' => $userData['is_owner'],
                        'is_active' => true,
                        'created_at' => now(),
                    ]
                );

                DB::table('company_user_role')->updateOrInsert(
                    [
                        'company_id' => $company->id,
                        'user_id' => $user->id,
                        'role_id' => $roleMap[$userData['role']]->id,
                    ],
                    [
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
