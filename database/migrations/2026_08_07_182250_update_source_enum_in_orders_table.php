<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Directly update the MySQL column enum definition
        DB::statement("ALTER TABLE orders MODIFY COLUMN source ENUM('TikTok', 'Instagram', 'Facebook', 'Telegram', 'Website', 'Walk-in', 'Other') DEFAULT 'TikTok'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN source ENUM('TikTok', 'Instagram', 'Website', 'Walk-in', 'Other') DEFAULT 'TikTok'");
    }
};