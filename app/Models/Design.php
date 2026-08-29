<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Design extends Model
{
    protected $fillable = ['collection_id', 'print_artwork_id', 'name', 'active'];

    protected $casts = ['active' => 'boolean'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function printArtwork(): BelongsTo
    {
        return $this->belongsTo(PrintArtwork::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Proxies to the shared PrintArtwork's film stock. Multiple Designs
     * (e.g. "Who Knows" and "Who Knows Long") can point at the same artwork,
     * so this always resolves to one shared pool of film -- never a
     * per-Design split. Falls back to an empty query if no artwork is linked.
     */
    public function films()
    {
        return $this->printArtwork
            ? $this->printArtwork->films()
            : FilmInventory::query()->whereRaw('1 = 0');
    }

    public function getHasFrontAttribute(): bool
    {
        return (bool) optional($this->printArtwork)->has_front;
    }

    public function getHasBackAttribute(): bool
    {
        return (bool) optional($this->printArtwork)->has_back;
    }

    public function frontFilms(): HasMany
    {
        return $this->films()->where('side', 'front');
    }

    public function backFilms(): HasMany
    {
        return $this->films()->where('side', 'back');
    }
}