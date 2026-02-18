<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the super admin account.
     *
     * This creates a protected superadmin user that cannot be deleted,
     * revoked, or have its password changed by other administrators.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'ali@wprobo.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('@@Ali1122$'),
                'role' => 'superadmin',
                'status' => 'active',
                'company_name' => 'WProbo',
            ]
        );
    }
}
