<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Dedicated counter per collection. Replaces the old approach of
    // parsing the last order_number string ("SUM-014" -> 14 -> 15), which
    // breaks under concurrent requests and if a number is ever edited by hand.
    public function up(): void
    {
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('next_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('order_sequences'); }
};
