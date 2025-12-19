<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Auth\Role;
use Illuminate\Support\Str;
use App\Models\Company\Company;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * 1️⃣ SUPER ADMIN (GLOBAL)
         */
        User::updateOrCreate(
            ['email' => 'superadmin@system.com'],
            [
                'uuid' => Str::uuid(),
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }
}
