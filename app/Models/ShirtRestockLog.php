<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShirtRestockLog extends Model
{
    protected $table = 'shirt_restock_logs';

    protected $fillable = [
        'shirt_inventory_id', 'quantity_added', 'cost_per_unit',
        'total_cost', 'restock_date', 'vendor', 'notes',
    ];

    protected $casts = ['restock_date' => 'date'];

    public function shirt(): BelongsTo
    {
        return $this->belongsTo(ShirtInventory::class, 'shirt_inventory_id');
    }
}
