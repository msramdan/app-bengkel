<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FreshMigrationTest extends TestCase
{
    #[Test]
    public function migrate_fresh_with_seed_runs_without_errors(): void
    {
        $this->assertSame(24, count(File::files(database_path('migrations'))));

        $exitCode = Artisan::call('migrate:fresh', [
            '--seed' => true,
            '--force' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertTrue(Schema::hasTable('transactions'));
        $this->assertTrue(Schema::hasTable('stock_movements'));
        $this->assertTrue(Schema::hasTable('suppliers'));
        $this->assertTrue(Schema::hasColumn('stock_movements', 'batch_no'));
        $this->assertTrue(Schema::hasColumn('transactions', 'cash_received'));
        $this->assertNotNull(User::where('email', 'saepulramdan244@gmail.com')->first());
    }
}
