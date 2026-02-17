<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Alishan',
            'email' => 'alishanvr@gmail.com',
            'password' => 'alishan',
            'company_name' => 'Maimaar Group',
        ]);

        $this->call([
            ReferenceDataSeeder::class,
            PdfSettingsSeeder::class,
            AppSettingsSeeder::class,
            EnvironmentSettingsSeeder::class,
        ]);
    }
}
