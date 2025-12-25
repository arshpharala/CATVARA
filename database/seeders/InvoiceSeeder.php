<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Accounting\InvoiceStatus;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['code' => 'DRAFT',           'name' => 'Draft',           'is_final' => false],
            ['code' => 'ISSUED',          'name' => 'Issued',          'is_final' => false],
            ['code' => 'PARTIALLY_PAID',  'name' => 'Partially Paid',  'is_final' => false],
            ['code' => 'PAID',            'name' => 'Paid',            'is_final' => true],
            ['code' => 'OVERDUE',         'name' => 'Overdue',         'is_final' => false],
            ['code' => 'VOID',            'name' => 'Void',            'is_final' => true],
        ];

        foreach ($statuses as $s) {
            InvoiceStatus::updateOrCreate(
                ['code' => $s['code']],
                [
                    'name' => $s['name'],
                    'is_final' => $s['is_final'],
                    'is_active' => true,
                ]
            );
        }
    }
}
