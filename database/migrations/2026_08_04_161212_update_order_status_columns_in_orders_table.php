<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('film_status', 50)->default('No Film')->change();
            $table->string('shirt_status', 50)->default('Not Yet')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('film_status')->change();
            $table->string('shirt_status')->change();
        });
    }
};