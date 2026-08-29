<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('film_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->enum('side', ['front', 'back']);
            // Optional: some designs print a different film per shirt color.
            // NULL = applies to any shirt color for this design/side.
            $table->string('shirt_color')->nullable();

            $table->integer('prints_available')->default(0);
            $table->integer('used_quantity')->default(0);
            $table->decimal('cost_per_print', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // NULL-safe unique: MySQL allows multiple NULLs, so this still lets
            // one "any color" row coexist with several color-specific rows.
            $table->unique(['design_id', 'side', 'shirt_color'], 'film_inventory_design_side_color_unique');
        });
    }

    public function down(): void { Schema::dropIfExists('film_inventory'); }
};
