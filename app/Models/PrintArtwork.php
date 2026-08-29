<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintArtwork extends Model
{
    protected $fillable = ['collection_id', 'name', 'has_front', 'has_back'];

    protected $casts = ['has_front' => 'boolean', 'has_back' => 'boolean'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function films(): HasMany
    {
        return $this->hasMany(FilmInventory::class);
    }
}