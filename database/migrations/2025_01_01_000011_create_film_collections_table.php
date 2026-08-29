<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // Reference catalog of film names/sides an admin can pick from when
    // labeling film stock. Not inventory itself -- film_inventory is.
    public function up(): void
    {
        Schema::create('film_collections', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('film_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('film_collection_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('side', ['front', 'back'])->default('front');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('film_names');
        Schema::dropIfExists('film_collections');
    }
};
