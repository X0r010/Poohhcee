<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FilmName extends Model
{
    protected $fillable = ['film_collection_id', 'name', 'side'];

    public function filmCollection(): BelongsTo
    {
        return $this->belongsTo(FilmCollection::class);
    }
}
