<?php

namespace App\Services;

use App\Models\Design;
use App\Models\FilmInventory;
use App\Models\Order;
use App\Models\PrintedShirt;
use App\Models\ShirtInventory;

class InventoryService
{
    // ── Shirts ─────────────────────────────────────────────────────
    public function findShirt(?string $typeName, string $size, string $color): ?ShirtInventory
    {
        return ShirtInventory::where('type', $typeName ?? '')
            ->where('size', $size)
            ->where('color', $color)
            ->first();
    }

    public function reserveShirt(ShirtInventory $shirt): bool
    {
        if ($shirt->quantity < 1) {
            return false;
        }
        $shirt->decrement('quantity');
        $shirt->increment('reserved_quantity');
        return true;
    }

    public function releaseShirt(ShirtInventory $shirt): void
    {
        $shirt->increment('quantity');
        if ($shirt->reserved_quantity > 0) {
            $shirt->decrement('reserved_quantity');
        }
    }

    public function markShirtUsed(ShirtInventory $shirt): void
    {
        if ($shirt->reserved_quantity > 0) {
            $shirt->decrement('reserved_quantity');
        }
        $shirt->increment('used_quantity');
    }

    // ── Film ───────────────────────────────────────────────────────
    public function resolveFilm(Design $design, string $side, ?string $shirtColor = null): ?FilmInventory
    {
        return $design->films()
            ->where('side', $side)
            ->where(fn ($q) => $q->where('shirt_color', $shirtColor)->orWhereNull('shirt_color'))
            ->orderByRaw('shirt_color IS NULL ASC')
            ->first();
    }

    public function filmIsReady(Design $design, ?string $shirtColor = null): bool
    {
        if ($design->has_front) {
            $front = $this->resolveFilm($design, 'front', $shirtColor);
            if (!$front || $front->prints_available < 1) return false;
        }
        if ($design->has_back) {
            $back = $this->resolveFilm($design, 'back', $shirtColor);
            if (!$back || $back->prints_available < 1) return false;
        }
        return true;
    }

    public function reserveFilm(FilmInventory $film): bool
    {
        if ($film->prints_available < 1) {
            return false;
        }
        $film->decrement('prints_available');
        $film->increment('reserved_quantity');
        return true;
    }

    public function releaseFilm(FilmInventory $film): void
    {
        $film->increment('prints_available');
        if ($film->reserved_quantity > 0) {
            $film->decrement('reserved_quantity');
        }
    }

    public function consumeFilm(FilmInventory $film): void
    {
        if ($film->reserved_quantity > 0) {
            $film->decrement('reserved_quantity');
        }
        $film->increment('used_quantity');
    }

    public function reserveFilmForDesign(Design $design, ?string $shirtColor = null): bool
    {
        if (!$this->filmIsReady($design, $shirtColor)) {
            return false;
        }

        if ($design->has_front) {
            $this->reserveFilm($this->resolveFilm($design, 'front', $shirtColor));
        }
        if ($design->has_back) {
            $this->reserveFilm($this->resolveFilm($design, 'back', $shirtColor));
        }

        return true;
    }

    public function releaseFilmForDesign(Design $design, ?string $shirtColor = null): void
    {
        if ($design->has_front) {
            $front = $this->resolveFilm($design, 'front', $shirtColor);
            if ($front) $this->releaseFilm($front);
        }
        if ($design->has_back) {
            $back = $this->resolveFilm($design, 'back', $shirtColor);
            if ($back) $this->releaseFilm($back);
        }
    }

    // ── Printed shirts (pre-made spares) ───────────────────────────
    public function addPrintedShirt(Order $order, string $source): void
    {
        $existing = PrintedShirt::where('design_id', $order->design_id)
            ->where('shirt_type', optional($order->shirtType)->name)
            ->where('size', $order->size)
            ->where('color', $order->color)
            ->first();

        if ($existing) {
            $existing->increment('quantity');
            return;
        }

        PrintedShirt::create([
            'design_id'  => $order->design_id,
            'shirt_type' => optional($order->shirtType)->name,
            'size'       => $order->size,
            'color'      => $order->color,
            'quantity'   => 1,
            'source'     => $source,
            'notes'      => "From order {$order->order_number}",
        ]);
    }

    public function consumePrintedShirt(PrintedShirt $printedShirt): void
    {
    $printedShirt->decrement('quantity');
    }

    public function releaseOrderReservations(Order $order): void
    {
        if ($order->inventory_released_at) {
            return;
        }

        if ($order->printed_shirt_id) {
            $printedShirt = $order->printedShirt;
            if ($printedShirt) {
                $printedShirt->increment('quantity');
            } else {
                $this->addPrintedShirt($order, "Returned — {$order->customer_handle}");
            }
        } elseif (in_array($order->shirt_status, ['Done']) && in_array($order->print_status, ['Printed', 'Done'])) {
            $this->addPrintedShirt($order, "Cancelled Order — {$order->customer_handle}");
        } elseif ($order->shirt_status === 'Bought') {
            $shirt = ShirtInventory::where('size', $order->size)->where('color', $order->color)->first();
            if ($shirt) $this->releaseShirt($shirt);
        }

        if (in_array($order->film_status, ['Have Film']) && $order->print_status === 'Pending') {
            $this->releaseFilmForDesign($order->design, $order->color);
        }

        $order->update(['inventory_released_at' => now()]);
    }
}