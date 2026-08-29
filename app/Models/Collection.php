<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Collection extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'active', 'short_code'];

    protected $casts = ['active' => 'boolean'];

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(Order::class, Design::class);
    }

    public function orderSequence(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(OrderSequence::class);
    }
}
