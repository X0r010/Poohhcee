<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'order_date', 'customer_handle', 'customer_phone', 'customer_location', 'source',
        'design_id', 'size', 'color', 'shirt_type_id',
        'base_price', 'delivery_fee', 'total_price', 'shirt_cost', 'film_cost', 'profit',
        'payment_status', 'payment_method', 'partial_amount',
        'shirt_status', 'film_status', 'print_status', 'delivery_status',
        'readiness', 'notes', 'printed_shirt_id', 'inventory_released_at',
    ];

    protected $casts = [
        'order_date' => 'date',
        'inventory_released_at' => 'datetime',
    ];

    /**
     * Automatically sort orders by order_number descending globally.
     */
    protected static function booted()
    {
        static::addGlobalScope('default_sort', function (Builder $builder) {
            $builder->orderBy('order_number', 'desc');
        });
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function shirtType(): BelongsTo
    {
        return $this->belongsTo(ShirtType::class, 'shirt_type_id');
    }

    public function printedShirt(): BelongsTo
    {
        return $this->belongsTo(PrintedShirt::class);
    }

    /**
     * Recognized Revenue calculation based on payment status and cancellations.
     */
    public function getRecognizedRevenueAttribute(): float
    {
        if (in_array($this->payment_status, ['Unpaid', 'Refunded'])) {
            return 0.0;
        }

        if ($this->payment_status === 'Partial') {
            return (float) ($this->partial_amount ?? 0);
        }

        if ($this->payment_status === 'Paid') {
            return (float) $this->total_price;
        }

        return 0.0;
    }

    /**
     * Recognized Cost of Goods Sold (COGS).
     * Materials are only counted ONCE the shirt has actually been printed/burned.
     */
    public function getRecognizedCogsAttribute(): float
    {
        $wasPrinted = in_array($this->print_status, ['Printed', 'Done']);

        // 1. If material hasn't been printed/burned yet, COGS is $0.
        if (!$wasPrinted) {
            return 0.0;
        }

        // 2. Material was printed: count shirt + film cost stored on the order.
        $shirtCost = (float) ($this->shirt_cost > 0 ? $this->shirt_cost : ($this->shirtType->cost ?? 0));
        $filmCost  = (float) ($this->film_cost ?? 0);

        return $shirtCost + $filmCost;
    }

    public function getNeededFilmSides(): array
    {
        if (!$this->design) {
            return [];
        }

        /** @var \App\Services\InventoryService $inventory */
        $inventory = app(\App\Services\InventoryService::class);
        $needed = [];

        if ($this->design->has_front) {
            $front = $inventory->resolveFilm($this->design, 'front', $this->color);
            if (!$front || $front->prints_available < 1) {
                $needed[] = 'Front';
            }
        }

        if ($this->design->has_back) {
            $back = $inventory->resolveFilm($this->design, 'back', $this->color);
            if (!$back || $back->prints_available < 1) {
                $needed[] = 'Back';
            }
        }

        return $needed;
    }

    public function computeReadiness(): string
    {
        if (in_array($this->print_status, ['Printed', 'Done'])) {
            return 'printed';
        }

        $shirtReady = in_array($this->shirt_status, ['Bought', 'Done']);
        $filmReady  = in_array($this->film_status, ['Have Film', 'Printed', 'Done', 'In Stock', 'Ready']);

        return match (true) {
            $shirtReady && $filmReady   => 'ready',
            $shirtReady && !$filmReady  => 'missing_film',
            !$shirtReady && $filmReady  => 'missing_shirt',
            default                     => 'missing_both',
        };
    }

    public function getReadinessAttribute(): string
    {
        return $this->computeReadiness();
    }

    public function getReadinessBadge(): array
    {
        return match ($this->computeReadiness()) {
            'ready'         => ['label' => 'Ready to Print', 'class' => 'badge-ready'],
            'missing_shirt' => ['label' => 'No Shirt',       'class' => 'badge-missing-shirt'],
            'missing_film'  => ['label' => 'No Film',        'class' => 'badge-missing-film'],
            'missing_both'  => ['label' => 'Missing Both',   'class' => 'badge-missing-both'],
            'printed'       => ['label' => 'Printed',        'class' => 'status-printed'],
            default         => ['label' => 'Unknown',        'class' => 'badge-unknown'],
        };
    }

    public function getReadinessBadgeAttribute(): array
    {
        return $this->getReadinessBadge();
    }

    public function getDeliveryBadgeClass(): string
    {
        return match ($this->delivery_status) {
            'Delivered'  => 'status-delivered',
            'Delivering' => 'status-delivering',
            'Packaging'  => 'status-packaging',
            'Cancelled'  => 'status-cancelled',
            default      => 'status-pending',
        };
    }

    public function getPaymentBadgeClass(): string
    {
        return match ($this->payment_status) {
            'Paid'    => 'payment-paid',
            'Partial' => 'payment-partial',
            default   => 'payment-unpaid',
        };
    }

    public function isCancelled(): bool
    {
        return $this->delivery_status === 'Cancelled';
    }

    public function getCogsAttribute()
    {
        if (isset($this->attributes['cogs']) && $this->attributes['cogs'] > 0) {
            return $this->attributes['cogs'];
        }
        
        return (float) ($this->shirt_cost ?? 0) + (float) ($this->film_cost ?? 0);
    }   
}