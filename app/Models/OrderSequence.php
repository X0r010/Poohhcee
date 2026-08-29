<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderSequence extends Model
{
    protected $fillable = ['collection_id', 'next_number'];

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
