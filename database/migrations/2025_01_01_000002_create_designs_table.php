<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('has_front')->default(true);
            $table->boolean('has_back')->default(true);
            $table->boolean('active')->default(true);
            $table->timestamps();

            // A design can't be nameless-duplicate inside the same collection.
            $table->unique(['collection_id', 'name']);
        });
    }

    public function down(): void { Schema::dropIfExists('designs'); }
};
