<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Safely update designs table if print_artwork_id doesn't exist yet
        if (!Schema::hasColumn('designs', 'print_artwork_id')) {
            Schema::table('designs', function (Blueprint $table) {
                $table->foreignId('print_artwork_id')->nullable()->after('collection_id')
                    ->constrained()->nullOnDelete();
            });
        }

        // 2. Fix film_inventory foreign key & indexes
        Schema::table('film_inventory', function (Blueprint $table) {
            // Unlink FK on design_id so MySQL allows dropping the unique index
            $table->dropForeign(['design_id']);

            // Drop old unique index
            $table->dropUnique('film_inventory_design_side_color_unique');

            // Re-create dedicated index and foreign key on design_id
            $table->index('design_id');
            $table->foreign('design_id')->references('id')->on('designs')->cascadeOnDelete();

            // Add new print_artwork_id column if not present
            if (!Schema::hasColumn('film_inventory', 'print_artwork_id')) {
                $table->foreignId('print_artwork_id')->nullable()->after('design_id')
                    ->constrained()->cascadeOnDelete();
            }

            // Add reserved_quantity column if not present
            if (!Schema::hasColumn('film_inventory', 'reserved_quantity')) {
                $table->integer('reserved_quantity')->default(0)->after('prints_available');
            }

            // Set new unique constraint on print_artwork_id
            $table->unique(['print_artwork_id', 'side', 'shirt_color'], 'film_inventory_pa_side_color_unique');
        });
    }

    public function down(): void
    {
        Schema::table('film_inventory', function (Blueprint $table) {
            $table->dropUnique('film_inventory_pa_side_color_unique');
            
            if (Schema::hasColumn('film_inventory', 'print_artwork_id')) {
                $table->dropForeign(['print_artwork_id']);
                $table->dropColumn('print_artwork_id');
            }
            if (Schema::hasColumn('film_inventory', 'reserved_quantity')) {
                $table->dropColumn('reserved_quantity');
            }
            
            $table->unique(['design_id', 'side', 'shirt_color'], 'film_inventory_design_side_color_unique');
        });

        if (Schema::hasColumn('designs', 'print_artwork_id')) {
            Schema::table('designs', function (Blueprint $table) {
                $table->dropForeign(['print_artwork_id']);
                $table->dropColumn('print_artwork_id');
            });
        }
    }
};