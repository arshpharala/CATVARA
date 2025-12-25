<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pos\PosOrderStatus;

class PosSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            [
                'code' => 'DRAFT',
                'name' => 'Draft',
                'is_final' => false,
                'is_active' => true,
            ],
            [
                'code' => 'COMPLETED',
                'name' => 'Completed',
                'is_final' => true,
                'is_active' => true,
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'is_final' => true,
                'is_active' => true,
            ],
        ];

        foreach ($statuses as $status) {
            PosOrderStatus::updateOrCreate(
                ['code' => $status['code']],
                $status
            );
        }
    }
}
