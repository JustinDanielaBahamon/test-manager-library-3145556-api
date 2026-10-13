<?php

namespace App\Console\Commands;

use App\Application\Services\BootstrapAdminService;
use Illuminate\Console\Command;

/** T-17 / D-10: `php artisan library:bootstrap-admin` — reads ADMIN_USERNAME / ADMIN_PASSWORD from the environment. */
final class BootstrapAdminCommand extends Command
{
    protected $signature = 'library:bootstrap-admin';

    protected $description = 'Create the initial admin user from ADMIN_USERNAME and ADMIN_PASSWORD (idempotent).';

    public function handle(BootstrapAdminService $service): int
    {
        $username = (string) env('ADMIN_USERNAME', '');
        $password = (string) env('ADMIN_PASSWORD', '');
        if ($username === '' || $password === '') {
            $this->error('ADMIN_USERNAME and ADMIN_PASSWORD must be set in the environment.');

            return self::FAILURE;
        }
        $created = $service->execute($username, $password);
        $this->info($created ? 'Admin user created.' : 'Admin user already exists.');

        return self::SUCCESS;
    }
}
