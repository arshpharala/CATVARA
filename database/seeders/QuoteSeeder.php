<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sales\QuoteStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class QuoteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['code' => 'DRAFT', 'name' => 'Draft'],
            ['code' => 'SENT', 'name' => 'Sent'],
            ['code' => 'ACCEPTED', 'name' => 'Accepted', 'is_final' => true],
            ['code' => 'EXPIRED', 'name' => 'Expired', 'is_final' => true],
            ['code' => 'CANCELLED', 'name' => 'Cancelled', 'is_final' => true],
        ];

        foreach ($statuses as $s) {
            QuoteStatus::updateOrCreate(
                ['code' => $s['code']],
                array_merge(['is_active' => true, 'is_final' => false], $s)
            );
        }
    }
}
