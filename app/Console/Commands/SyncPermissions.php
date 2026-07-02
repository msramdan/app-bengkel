<?php

namespace App\Console\Commands;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Console\Command;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sinkronkan permission baru ke database dan perbarui role default';

    public function handle(): int
    {
        (new RoleAndPermissionSeeder)->syncPermissionsAndRoles();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('Permission berhasil disinkronkan.');

        return self::SUCCESS;
    }
}
