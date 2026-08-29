<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ShirtInventory extends Model
{
    protected $table = 'shirt_inventory';

    protected $fillable = [
        'type', 'size', 'color', 'quantity', 'reserved_quantity',
        'used_quantity', 'printed_available', 'cost_per_unit', 'vendor', 'notes',
    ];

    protected $appends = ['stock_value', 'total_spend'];

    /**
     * Value of all shirts currently on hand (Available + Reserved).
     */
    protected function stockValue(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->quantity + $this->reserved_quantity) * ($this->cost_per_unit ?? 0),
        );
    }

    /**
     * Total lifetime spend on this shirt batch (Available + Reserved + Used).
     */
    protected function totalSpend(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->quantity + $this->reserved_quantity + $this->used_quantity) * ($this->cost_per_unit ?? 0),
        );
    }

    public function restockLogs(): HasMany
    {
        return $this->hasMany(ShirtRestockLog::class, 'shirt_inventory_id');
    }

    public function scopeMatching($query, string $type, string $size, string $color)
    {
        return $query->where('type', $type)->where('size', $size)->where('color', $color);
    }
}