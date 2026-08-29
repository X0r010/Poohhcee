<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();

            $table->date('order_date');
            $table->string('customer_handle');
            $table->string('customer_phone')->nullable();
            $table->string('customer_location')->nullable();
            $table->enum('source', ['TikTok', 'Instagram', 'Facebook', 'Telegram', 'Website', 'Walk-in', 'Other'])->default('TikTok');

            $table->foreignId('design_id')->constrained();
            $table->string('size');
            $table->string('color');
            $table->foreignId('shirt_type_id')->nullable()->constrained('shirt_types')->nullOnDelete();

            $table->decimal('base_price', 8, 2)->default(0);
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('total_price', 8, 2)->default(0);
            $table->decimal('shirt_cost', 8, 2)->default(0);
            $table->decimal('film_cost', 8, 2)->default(0);
            $table->decimal('profit', 8, 2)->default(0);

            $table->enum('payment_status', ['Paid', 'Not Yet', 'Partial'])->default('Not Yet');
            $table->string('payment_method')->nullable();
            $table->decimal('partial_amount', 8, 2)->default(0);

            $table->enum('shirt_status', ['Not Yet', 'Buying', 'Bought', 'Done'])->default('Not Yet');
            $table->enum('film_status', ['No Film', 'Have Film', 'Printed', 'Done'])->default('No Film');
            $table->enum('print_status', ['Pending', 'Printing', 'Printed', 'Done'])->default('Pending');
            $table->enum('delivery_status', ['Pending', 'Packaging', 'Delivering', 'Delivered', 'Cancelled'])->default('Pending');
            $table->enum('readiness', ['ready', 'missing_shirt', 'missing_film', 'missing_both', 'printed'])->default('missing_both');

            // Set once, at the moment an order is cancelled, so we never run
            // the "return inventory" logic twice for the same order.
            $table->timestamp('inventory_released_at')->nullable();

            $table->foreignId('printed_shirt_id')->nullable()->constrained('printed_shirts')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['delivery_status', 'print_status']);
            $table->index('readiness');
        });
    }

    public function down(): void { Schema::dropIfExists('orders'); }
};
