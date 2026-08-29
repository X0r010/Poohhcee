<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shirt_inventory', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('size');
            $table->string('color');

            // quantity        = free stock, sellable right now
            // reserved_quantity = allocated to an order, not yet printed
            // used_quantity    = physically printed and consumed
            // printed_available = pre-printed spares (e.g. from a cancelled order),
            //                     ready to ship immediately with zero print step
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('used_quantity')->default(0);
            $table->integer('printed_available')->default(0);

            $table->decimal('cost_per_unit', 8, 2)->default(0);
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['type', 'size', 'color']);
        });

        Schema::create('shirt_restock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shirt_inventory_id')->constrained('shirt_inventory')->cascadeOnDelete();
            $table->integer('quantity_added');
            $table->decimal('cost_per_unit', 8, 2);
            $table->decimal('total_cost', 8, 2);
            $table->date('restock_date');
            $table->string('vendor')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shirt_restock_logs');
        Schema::dropIfExists('shirt_inventory');
    }
};
