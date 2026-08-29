<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FilmCollection extends Model
{
    protected $fillable = ['name'];

    public function filmNames(): HasMany
    {
        return $this->hasMany(FilmName::class);
    }
}
