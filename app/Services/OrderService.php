<?php

namespace App\Services;

use App\Models\Design;
use App\Models\Order;
use App\Models\PrintedShirt;
use App\Models\ShirtInventory;
use App\Models\ShirtType;
use Illuminate\Support\Facades\DB;

/**
 * Coordinates order creation/update/status-change/cancellation. Every
 * multi-step write (create order + touch inventory, or change status +
 * release inventory) runs inside a DB transaction.
 */
class OrderService
{
    public function __construct(
        private InventoryService $inventory,
        private OrderNumberService $numbers,
    ) {}

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $design = Design::findOrFail($data['design_id']);
            $shirtTypeName = isset($data['shirt_type_id'])
                ? optional(ShirtType::find($data['shirt_type_id']))->name
                : null;

            $data['total_price'] = $data['base_price'] + ($data['delivery_fee'] ?? 0);
            $data['partial_amount'] ??= 0;

            if (!empty($data['printed_shirt_id'])) {
                $this->applyPrintedShirtOrder($data);
            } else {
                $this->applyStandardOrder($data, $design, $shirtTypeName);
            }

            $data['profit'] = $data['total_price'] - $data['shirt_cost'] - $data['film_cost'];

            // --- STRICT SEQUENTIAL (Max + 1) ORDER NUMBER GENERATOR, PER COLLECTION ---
            $prefix = $design->collection->short_code . '-';
            $maxNum = Order::whereHas('design', fn ($q) => $q->where('collection_id', $design->collection_id))
                ->get()
                ->map(function ($order) {
                    preg_match('/(\d+)/', $order->order_number, $matches);
                    return isset($matches[1]) ? (int) $matches[1] : 0;
                })->max() ?? 0;

            $nextNumber = $maxNum + 1;
            $data['order_number'] = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Pre-compute readiness on an in-memory model BEFORE calling Order::create
            $tempOrder = new Order($data);
            $data['readiness'] = $tempOrder->computeReadiness();

            $order = Order::create($data);

            return $order->fresh();
        });
    }

    private function applyPrintedShirtOrder(array &$data): void
    {
        // Fulfilled instantly from a pre-printed spare -- no shirt/film cost, no reservation.
        $data['shirt_cost']   = 0;
        $data['film_cost']    = 0;
        $data['shirt_status'] = 'Done';
        $data['film_status']  = 'Done';
        $data['print_status'] = 'Printed';

        $printedShirt = PrintedShirt::find($data['printed_shirt_id']);
        if ($printedShirt) {
            // Decrement quantity directly without deleting the row so FK remains intact
            $printedShirt->decrement('quantity', 1);
        }
    }

    private function applyStandardOrder(array &$data, Design $design, ?string $shirtTypeName): void
    {
        $shirt = $this->inventory->findShirt($shirtTypeName, $data['size'], $data['color']);
        $data['shirt_cost'] = $shirt->cost_per_unit ?? 0;

        $front = $design->has_front ? $this->inventory->resolveFilm($design, 'front', $data['color']) : null;
        $back  = $design->has_back  ? $this->inventory->resolveFilm($design, 'back', $data['color'])  : null;
        $data['film_cost'] = ($design->has_front ? optional($front)->cost_per_print ?? 0 : 0)
                            + ($design->has_back  ? optional($back)->cost_per_print  ?? 0 : 0);

        if (($data['film_status'] ?? null) === 'Have Film') {
            if ($front) $this->inventory->reserveFilm($front);
            if ($back)  $this->inventory->reserveFilm($back);
        }

        if (($data['shirt_status'] ?? null) === 'Bought' && $shirt) {
            $this->inventory->reserveShirt($shirt);
        }
    }

    /**
     * Change exactly one pipeline field. Inventory is only ever released
     * here when the change is a cancellation.
     */
    public function updateStatusField(Order $order, string $field, string $value): Order
    {
        return DB::transaction(function () use ($order, $field, $value) {
            $becomingCancelled = $field === 'delivery_status' && $value === 'Cancelled' && !$order->isCancelled();

            if ($field === 'shirt_status') {
                $this->applyShirtStatusTransition($order, $value);
            } elseif ($field === 'film_status') {
                $this->applyFilmStatusTransition($order, $value);
            } else {
                $order->update([$field => $value]);
            }

            $order->refresh();

            if ($becomingCancelled) {
                if ($order->printed_shirt_id) {
                    $this->releasePrintedShirt($order);
                } else {
                    $this->inventory->releaseOrderReservations($order);
                }
            }

            $order->update(['readiness' => $order->computeReadiness()]);

            return $order->fresh();
        });
    }

    private function releasePrintedShirt(Order $order): void
    {
        if ($order->printed_shirt_id) {
            $printedShirt = PrintedShirt::find($order->printed_shirt_id);
            if ($printedShirt) {
                $printedShirt->increment('quantity', 1);
                return;
            }
        }

        // Fallback: search or create printed_shirts row if ID missing/legacy
        $printedShirt = PrintedShirt::firstOrCreate(
            [
                'design_id'     => $order->design_id,
                'shirt_type_id' => $order->shirt_type_id,
                'size'          => $order->size,
                'color'         => $order->color,
            ],
            ['quantity' => 0]
        );
        $printedShirt->increment('quantity', 1);
    }

    private function applyShirtStatusTransition(Order $order, string $newValue): void
    {
        $current = $order->shirt_status;
        if ($current === $newValue) return;

        $sequence = ['Not Yet', 'Buying', 'Bought', 'Done'];
        $currentIndex = array_search($current, $sequence);
        $newIndex = array_search($newValue, $sequence);

        if ($newIndex === false || $currentIndex === false) {
            throw new \RuntimeException('Invalid shirt status.');
        }
        if ($newValue === 'Done' || $current === 'Done') {
            throw new \RuntimeException("Shirt status can't be changed manually once printing has started.");
        }
        if (abs($newIndex - $currentIndex) !== 1) {
            throw new \RuntimeException('Can\'t skip stages — move to "Buying" before "Bought".');
        }

        $shirtTypeName = optional($order->shirtType)->name;
        $shirt = $this->inventory->findShirt($shirtTypeName, $order->size, $order->color);

        if ($current === 'Buying' && $newValue === 'Bought') {
            if (!$shirt || !$this->inventory->reserveShirt($shirt)) {
                throw new \RuntimeException('No stock available to reserve — restock this shirt first.');
            }
        }

        if ($current === 'Bought' && $newValue === 'Buying') {
            if ($shirt) $this->inventory->releaseShirt($shirt);
        }

        $order->update(['shirt_status' => $newValue]);
    }

    private function applyFilmStatusTransition(Order $order, string $newValue): void
    {
        $current = $order->film_status;
        if ($current === $newValue) return;

        $sequence = ['No Film', 'Ordering', 'Have Film', 'Printed', 'Done'];
        $currentIndex = array_search($current, $sequence);
        $newIndex = array_search($newValue, $sequence);

        if ($newIndex === false || $currentIndex === false) {
            throw new \RuntimeException('Invalid film status.');
        }
        if (in_array($newValue, ['Printed', 'Done']) || in_array($current, ['Printed', 'Done'])) {
            throw new \RuntimeException("Film status can't be changed manually once printing has started.");
        }
        if (abs($newIndex - $currentIndex) !== 1) {
            throw new \RuntimeException('Can\'t skip stages — move to "Ordering" before "Have Film".');
        }

        $design = $order->design;

        if ($current === 'Ordering' && $newValue === 'Have Film') {
            if (!$this->inventory->filmIsReady($design, $order->color) || !$this->inventory->reserveFilmForDesign($design, $order->color)) {
                throw new \RuntimeException("Film isn't fully stocked yet (front and/or back missing).");
            }
        }

        if ($current === 'Have Film' && $newValue === 'Ordering') {
            $this->inventory->releaseFilmForDesign($design, $order->color);
        }

        $order->update(['film_status' => $newValue]);
    }

    public function delete(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if (!$order->isCancelled()) {
                if ($order->printed_shirt_id) {
                    $this->releasePrintedShirt($order);
                } else {
                    if ($order->shirt_status === 'Bought') {
                        $typeName = optional(ShirtType::find($order->shirt_type_id))->name ?? $order->shirt_type_id;
                        $stock = ShirtInventory::where(function ($q) use ($typeName, $order) {
                                $q->where('type', $typeName)->orWhere('type', $order->shirt_type_id);
                            })
                            ->where('size', $order->size)
                            ->where('color', $order->color)
                            ->first();

                        if ($stock) {
                            $stock->increment('quantity', 1);
                            $stock->decrement('reserved_quantity', 1);
                        }
                    }

                    $this->inventory->releaseOrderReservations($order);
                }
            }

            preg_match('/(\d+)/', $order->order_number, $matches);
            $deletedNum = isset($matches[1]) ? (int) $matches[1] : null;
            $deletedCollectionId = $order->design->collection_id;

            $order->delete();

            if ($deletedNum !== null) {
                $subsequentOrders = Order::whereHas('design', fn ($q) => $q->where('collection_id', $deletedCollectionId))
                    ->get()
                    ->map(function ($o) {
                        preg_match('/^([A-Z\-]+)(\d+)$/', $o->order_number, $m);
                        if (empty($m)) {
                            preg_match('/^([^\d]+)(\d+)$/', $o->order_number, $m);
                        }
                        return [
                            'model'  => $o,
                            'prefix' => $m[1] ?? 'CLA-',
                            'num'    => isset($m[2]) ? (int) $m[2] : null,
                            'pad'    => isset($m[2]) ? strlen($m[2]) : 3,
                        ];
                    })->filter(fn ($item) => $item['num'] !== null && $item['num'] > $deletedNum)
                      ->sortBy(fn ($item) => $item['num']);

                foreach ($subsequentOrders as $item) {
                    $item['model']->update(['order_number' => $item['prefix'] . 'TEMP-' . uniqid()]);
                }

                foreach ($subsequentOrders as $item) {
                    $newNum = $item['num'] - 1;
                    $item['model']->update([
                        'order_number' => $item['prefix'] . str_pad($newNum, $item['pad'], '0', STR_PAD_LEFT),
                    ]);
                }
            }
        });
    }

    public function advancePrint(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            if ($order->print_status === 'Pending') {
                $order->update(['print_status' => 'Printing']);
                return $order;
            }

            if ($order->print_status === 'Printing') {
                if (!$order->printed_shirt_id) {
                    $shirtTypeName = $order->shirt_type_id ? optional(ShirtType::find($order->shirt_type_id))->name : null;
                    $shirt = $this->inventory->findShirt($shirtTypeName, $order->size, $order->color);
                    if ($shirt) $this->inventory->markShirtUsed($shirt);

                    $design = $order->design;
                    if ($design->has_front) {
                        $front = $this->inventory->resolveFilm($design, 'front', $order->color);
                        if ($front) $this->inventory->consumeFilm($front);
                    }
                    if ($design->has_back) {
                        $back = $this->inventory->resolveFilm($design, 'back', $order->color);
                        if ($back) $this->inventory->consumeFilm($back);
                    }
                }

                $order->update([
                    'print_status' => 'Printed',
                    'shirt_status' => 'Done',
                    'film_status'  => $order->printed_shirt_id ? $order->film_status : 'Printed',
                    'readiness'    => 'printed',
                ]);
            }

            return $order->fresh();
        });
    }

    public function autoMatchBuyingOrders(ShirtInventory $shirt): int
    {
        $orders = Order::where('shirt_status', 'Buying')
            ->where('size', $shirt->size)
            ->where('color', $shirt->color)
            ->whereHas('shirtType', fn ($q) => $q->where('name', $shirt->type))
            ->where('delivery_status', '!=', 'Cancelled')
            ->orderBy('id')
            ->get();

        $matched = 0;
        foreach ($orders as $order) {
            if (!$this->inventory->reserveShirt($shirt)) break;
            $order->update(['shirt_status' => 'Bought']);
            $order->update(['readiness' => $order->fresh()->computeReadiness()]);
            $matched++;
        }
        return $matched;
    }

    public function autoMatchOrderingOrders(Design $design, ?string $shirtColor = null): int
    {
        if (!$design->print_artwork_id) return 0;

        $siblingDesignIds = Design::where('print_artwork_id', $design->print_artwork_id)->pluck('id');

        $orders = Order::where('film_status', 'Ordering')
            ->whereIn('design_id', $siblingDesignIds)
            ->where('delivery_status', '!=', 'Cancelled')
            ->when($shirtColor, fn ($q) => $q->where('color', $shirtColor))
            ->orderBy('id')
            ->get();

        $matched = 0;
        foreach ($orders as $order) {
            $orderDesign = $order->design;
            if (!$this->inventory->filmIsReady($orderDesign, $order->color)) continue;
            if (!$this->inventory->reserveFilmForDesign($orderDesign, $order->color)) continue;
            $order->update(['film_status' => 'Have Film']);
            $order->update(['readiness' => $order->fresh()->computeReadiness()]);
            $matched++;
        }
        return $matched;
    }
}