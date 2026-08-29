<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    // A Design is what a customer orders (e.g. "Who Knows" vs "Who Knows Long").
    // A PrintArtwork is the actual film stock. Multiple Designs can point at the
    // same PrintArtwork when they use identical film, so stock isn't split
    // across near-duplicate garment cuts.
    public function up(): void
    {
        Schema::create('print_artworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('has_front')->default(true);
            $table->boolean('has_back')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('print_artworks'); }
};