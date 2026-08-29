<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    // shirt_status already has 'Buying' as its "actively being sourced" state.
    // film_status was missing the equivalent -- this adds 'Ordering' so Pipeline
    // can distinguish "flagged for sourcing" from plain "No Film".
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY film_status ENUM('No Film','Ordering','Have Film','Printed','Done') NOT NULL DEFAULT 'No Film'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY film_status ENUM('No Film','Have Film','Printed','Done') NOT NULL DEFAULT 'No Film'");
    }
};
