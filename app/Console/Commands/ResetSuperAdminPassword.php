<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;

class ResetSuperAdminPassword extends Command
{
    protected $signature = 'app:reset-superadmin-password';

    protected $description = 'Reset the super admin password via the terminal';

    public function handle(): int
    {
        $superAdmin = User::query()->where('role', 'superadmin')->first();

        if (! $superAdmin) {
            $this->error('No super admin account found. Run the SuperAdminSeeder first.');

            return self::FAILURE;
        }

        $this->info("Super admin account: {$superAdmin->email}");

        $newPassword = password(
            label: 'Enter new password (min 8 characters)',
            required: true,
            validate: fn (string $value) => strlen($value) < 8
                ? 'Password must be at least 8 characters.'
                : null,
        );

        $confirmPassword = password(
            label: 'Confirm new password',
            required: true,
        );

        if ($newPassword !== $confirmPassword) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        // Bypass model events to allow direct password update
        User::query()
            ->where('id', $superAdmin->id)
            ->update(['password' => Hash::make($newPassword)]);

        $this->info('Super admin password has been reset successfully.');

        return self::SUCCESS;
    }
}
