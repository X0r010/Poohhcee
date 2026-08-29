<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\ShirtInventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetImportedOrders extends Command
{
    protected $signature = 'orders:reset-imported';
    protected $description = 'Safely remove imported orders and reset inventory counters without dropping tables';

    public function handle(): void
    {
        if (!$this->confirm('This will delete ALL orders in the database and reset shirt/film used counters. Continue?')) {
            $this->info('Operation cancelled.');
            return;
        }

        // Disable foreign key checks to prevent cascade errors
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Truncate / Clear Orders
        Order::truncate();
        $this->info('Orders table cleared.');

        // 2. Reset Inventory Stock Counts (Optional clean slate for stock tracking)
        if (Schema::hasTable('shirt_inventories')) {
            ShirtInventory::query()->update([
                'used_quantity' => 0,
            ]);
            $this->info('Shirt inventory used_quantity reset to 0.');
        }

        if (Schema::hasTable('films')) {
            DB::table('films')->update([
                'used_quantity' => 0,
            ]);
            $this->info('Film used_quantity reset to 0.');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Database ready for a clean re-import!');
    }
}