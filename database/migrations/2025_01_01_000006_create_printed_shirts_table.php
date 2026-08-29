<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('printed_shirts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->string('shirt_type')->nullable();
            $table->string('size');
            $table->string('color');
            $table->integer('quantity')->default(1);
            $table->string('source')->default('Cancelled Order');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['design_id', 'shirt_type', 'size', 'color'], 'printed_shirts_variant_unique');
        });
    }

    public function down(): void { Schema::dropIfExists('printed_shirts'); }
};
