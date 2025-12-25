<?php

namespace Database\Seeders;

use App\Models\Web\WebOrderStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WebOrderStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['code' => 'DRAFT', 'name' => 'Draft'],
            ['code' => 'PENDING_PAYMENT', 'name' => 'Pending Payment'],
            ['code' => 'PAID', 'name' => 'Paid', 'is_final' => true],
            ['code' => 'CANCELLED', 'name' => 'Cancelled', 'is_final' => true],
            ['code' => 'FULFILLED', 'name' => 'Fulfilled', 'is_final' => true],
        ];

        foreach ($statuses as $status) {
            WebOrderStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
