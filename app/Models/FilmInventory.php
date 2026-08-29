<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilmInventory extends Model
{
    protected $table = 'film_inventory';

    protected $fillable = [
        'design_id', 'print_artwork_id', 'side', 'shirt_color',
        'prints_available', 'reserved_quantity', 'used_quantity', 'cost_per_print', 'notes',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function printArtwork(): BelongsTo
    {
        return $this->belongsTo(PrintArtwork::class);
    }
}