<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintedShirt extends Model
{
    protected $fillable = ['design_id', 'shirt_type', 'size', 'color', 'quantity', 'source', 'notes'];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }
}
