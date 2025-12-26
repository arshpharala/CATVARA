<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sales\OrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['code' => 'DRAFT', 'name' => 'Draft'],
            ['code' => 'CONFIRMED', 'name' => 'Confirmed'],
            ['code' => 'PARTIALLY_FULFILLED', 'name' => 'Partially Fulfilled'],
            ['code' => 'FULFILLED', 'name' => 'Fulfilled', 'is_final' => true],
            ['code' => 'CANCELLED', 'name' => 'Cancelled', 'is_final' => true],
        ];

        foreach ($statuses as $s) {
            OrderStatus::updateOrCreate(
                ['code' => $s['code']],
                array_merge(['is_active' => true, 'is_final' => false], $s)
            );
        }
    }
}
