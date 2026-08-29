<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->enum('category', ['Shirts', 'DTF Film', 'Packaging', 'Delivery', 'Marketing', 'Equipment', 'Other']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('amount', 8, 2);
            $table->string('reference')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('expenses'); }
};
