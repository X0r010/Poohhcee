<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Design;
use App\Models\FilmInventory;
use App\Models\Order;
use App\Models\PrintedShirt;
use App\Models\ShirtInventory;
use App\Models\ShirtRestockLog;
use App\Models\ShirtType;
use App\Models\Color;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(private \App\Services\OrderService $orders) {}

    private const LOW_STOCK_THRESHOLD = 2;

    // ── Shirts ─────────────────────────────────────────────────────
    public function shirts()
    {
    $shirtTypes = ShirtType::orderBy('name')->get();
    $colors = ShirtInventory::distinct()->orderBy('color')->pluck('color');

    $shirts = ShirtInventory::orderBy('type')->orderByRaw(
        "FIELD(size, 'XS','S','M','L','XL','2XL','3XL')"
    )->orderBy('color')->get();

    // Map ShirtType names to IDs for fast lookup
    $typeIdMap = $shirtTypes->pluck('id', 'name');

    // Count orders that consumed material:
    // 1. All non-cancelled orders
    // 2. Cancelled orders that were ALREADY printed (print_status = 'Done')
    $usedCounts = Order::whereNotNull('shirt_type_id')
        ->where(function ($query) {
            $query->where('delivery_status', '!=', 'Cancelled')
                  ->orWhere('print_status', 'Done');
        })
        ->selectRaw('shirt_type_id, LOWER(size) as size, LOWER(color) as color, COUNT(*) as total')
        ->groupBy('shirt_type_id', 'size', 'color')
        ->get()
        ->keyBy(fn ($item) => $item->shirt_type_id . '|' . $item->size . '|' . $item->color);

    // Dynamically update used_quantity on each shirt
    $shirts->transform(function ($shirt) use ($typeIdMap, $usedCounts) {
        $typeId = $typeIdMap[$shirt->type] ?? null;
        $key = $typeId . '|' . strtolower($shirt->size) . '|' . strtolower($shirt->color);

        $shirt->used_quantity = $usedCounts->get($key)->total ?? 0;
        return $shirt;
    });

    $printedShirts = PrintedShirt::with('design.collection')->orderByDesc('created_at')->get();
    $recentRestocks = ShirtRestockLog::with('shirt')->orderByDesc('restock_date')->take(6)->get();

    $summary = [
        'in_stock' => $shirts->sum('quantity'),
        'reserved' => $shirts->sum('reserved_quantity'),
        'used' => $shirts->sum('used_quantity'),
        'printed_available' => $printedShirts->sum('quantity'),
        'stock_value' => $shirts->sum(fn ($s) => (($s->quantity ?? 0) + ($s->reserved_quantity ?? 0) + ($s->used_quantity ?? 0)) * ($s->cost_per_unit ?? 0)),
        'low_stock_count' => $shirts->where('quantity', '<=', self::LOW_STOCK_THRESHOLD)->count(),
    ];

    return view('inventory.shirts', compact('shirtTypes', 'colors', 'shirts', 'printedShirts', 'recentRestocks', 'summary'));
    }

    public function addShirt(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|string|max:50',
            'size' => 'required|string|max:10',
            'color' => 'required|string|max:30',
            'quantity' => 'required|integer|min:1',
            'cost_per_unit' => 'required|numeric|min:0',
            'vendor' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $shirt = DB::transaction(function () use ($data) {
            $shirt = ShirtInventory::firstOrNew([
                'type' => $data['type'], 'size' => $data['size'], 'color' => $data['color'],
            ]);
            $shirt->quantity = ($shirt->exists ? $shirt->quantity : 0) + $data['quantity'];
            $shirt->cost_per_unit = $data['cost_per_unit'];
            $shirt->vendor = $data['vendor'] ?? $shirt->vendor;
            $shirt->notes = $data['notes'] ?? $shirt->notes;
            $shirt->save();

            ShirtRestockLog::create([
                'shirt_inventory_id' => $shirt->id,
                'quantity_added' => $data['quantity'],
                'cost_per_unit' => $data['cost_per_unit'],
                'total_cost' => $data['quantity'] * $data['cost_per_unit'],
                'restock_date' => now(),
                'vendor' => $data['vendor'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            return $shirt;
        });

        $matched = $this->orders->autoMatchBuyingOrders($shirt);

        $msg = "Added {$data['quantity']} × {$data['type']} {$data['size']}/{$data['color']} to inventory!";
        if ($matched > 0) $msg .= " Auto-matched {$matched} order(s) waiting in Buying.";
        return back()->with('success', $msg);
    }

    public function updateShirt(Request $request, ShirtInventory $shirt)
    {
        $data = $request->validate([
            'cost_per_unit' => 'required|numeric|min:0',
            'vendor' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);
        $shirt->update($data);
        return back()->with('success', 'Shirt updated.');
    }

    public function removeShirtUnit(ShirtInventory $shirt)
    {
        if ($shirt->quantity > 0) {
            $shirt->decrement('quantity');

            if ($shirt->quantity === 0) {
                $shirtTypeId = ShirtType::where('name', $shirt->type)->value('id') ?? $shirt->type;

                Order::where(function ($q) use ($shirtTypeId, $shirt) {
                        $q->where('shirt_type_id', $shirtTypeId)
                          ->orWhere('shirt_type_id', $shirt->type);
                    })
                    ->where('size', $shirt->size)
                    ->where('color', $shirt->color)
                    ->whereIn('shirt_status', ['Bought', 'bought'])
                    ->whereNotIn('delivery_status', ['Delivered', 'delivered'])
                    ->update(['shirt_status' => 'Not Yet']);
            }

            return back()->with('success', 'Subtracted 1 shirt from inventory stock.');
        }

        return back()->with('error', 'Shirt stock is already 0.');
    }

    public function deleteShirt(ShirtInventory $shirt)
    {
        $shirtTypeId = ShirtType::where('name', $shirt->type)->value('id') ?? $shirt->type;

        $ordersToReset = Order::where(function ($q) use ($shirtTypeId, $shirt) {
                $q->where('shirt_type_id', $shirtTypeId)
                  ->orWhere('shirt_type_id', $shirt->type);
            })
            ->where('size', $shirt->size)
            ->where('color', $shirt->color)
            ->whereIn('shirt_status', ['Bought', 'bought'])
            ->whereNotIn('delivery_status', ['Delivered', 'delivered'])
            ->get();

        foreach ($ordersToReset as $order) {
            $order->update(['shirt_status' => 'Not Yet']);
        }

        $resetCount = $ordersToReset->count();
        $shirt->delete();

        $msg = 'Shirt removed from inventory.';
        if ($resetCount > 0) {
            $msg .= " Reset {$resetCount} order(s) back to 'Not Yet'.";
        }

        return back()->with('success', $msg);
    }

    public function deletePrintedShirt(PrintedShirt $printedShirt)
    {
        $printedShirt->delete();
        return back()->with('success', 'Printed shirt removed.');
    }

    // ── Films ──────────────────────────────────────────────────────
    public function films()
    {
    $collections = Collection::with([
        'designs' => fn ($q) => $q->where('active', true),
        'designs.printArtwork.films'
    ])
    ->where('active', true)
    ->orderBy('name')
    ->get();

    // 1. Get active design IDs
    $activeDesignIds = Design::where('active', true)->pluck('id');

    // 2. Fetch ONLY unprinted orders that have film matched/ready ('Have Film')
    $activeOrdersByDesign = Order::whereNull('printed_shirt_id')
        ->whereIn('design_id', $activeDesignIds)
        ->whereIn('film_status', ['Have Film', 'have_film'])
        ->whereNotIn('delivery_status', ['Cancelled', 'cancelled', 'Delivered', 'delivered'])
        ->get()
        ->groupBy('design_id');

    // 3. Calculate actual consumed film counts directly from orders:
    // - All non-cancelled orders
    // - Cancelled orders that were already printed (print_status = 'Done')
    $usedOrdersByArtwork = Order::whereNotNull('design_id')
        ->where(function ($query) {
            $query->whereNotIn('delivery_status', ['Cancelled', 'cancelled'])
                  ->orWhereIn('print_status', ['Done', 'done']);
        })
        ->join('designs', 'orders.design_id', '=', 'designs.id')
        ->selectRaw('COALESCE(designs.print_artwork_id, designs.id) as artwork_key, COUNT(*) as total')
        ->groupBy('artwork_key')
        ->pluck('total', 'artwork_key');

    // 4. Group & deduplicate designs per collection and attach dynamic used counts
    foreach ($collections as $collection) {
        $uniqueDesigns = $collection->designs->unique(function ($design) {
            return $design->print_artwork_id ?? $design->id;
        });

        foreach ($uniqueDesigns as $uniqueDesign) {
            $artworkId = $uniqueDesign->print_artwork_id ?? $uniqueDesign->id;

            $uniqueDesign->shared_designs = $collection->designs->filter(function ($d) use ($artworkId) {
                $dArtworkId = $d->print_artwork_id ?? $d->id;
                return $dArtworkId == $artworkId;
            });

            // Calculate reserved shirt count per design group (only orders with film ready)
            $sharedDesignIds = $uniqueDesign->shared_designs->pluck('id')->toArray();
            $reservedOrdersCount = 0;

            foreach ($sharedDesignIds as $dId) {
                if (isset($activeOrdersByDesign[$dId])) {
                    $reservedOrdersCount += $activeOrdersByDesign[$dId]->count();
                }
            }

            // Get total used orders for this artwork/design
            $usedCount = $usedOrdersByArtwork->get($artworkId) ?? 0;

            // Attach dynamic properties directly to the design card object
            $uniqueDesign->reserved_shirts = $reservedOrdersCount;
            $uniqueDesign->used_shirts = $usedCount;
            $uniqueDesign->used_count = $usedCount;

            // Sync dynamic used_quantity on the nested artwork film models
            if ($uniqueDesign->printArtwork && $uniqueDesign->printArtwork->films) {
                foreach ($uniqueDesign->printArtwork->films as $film) {
                    $film->used_quantity = $usedCount;
                }
            }
        }

        $collection->setRelation('unique_film_designs', $uniqueDesigns);
    }

    // 5. Fetch low-stock films, grouped by artwork/design and side
    $lowStockFilms = FilmInventory::with(['design.collection'])
        ->where('prints_available', '<=', self::LOW_STOCK_THRESHOLD)
        ->get()
        ->unique(function ($film) {
            return ($film->print_artwork_id ?? $film->design_id) . '-' . $film->side;
        });

    $allFilms = FilmInventory::all();

    // Dynamically assign used_quantity to every film record in $allFilms for summary
    $allFilms->transform(function ($film) use ($usedOrdersByArtwork) {
        $key = $film->print_artwork_id ?? $film->design_id;
        $film->used_quantity = $usedOrdersByArtwork->get($key) ?? 0;
        return $film;
    });

    // Avoids doubling front + back sides while still giving total printed count
    $usedFilmCount = $allFilms->groupBy(fn ($f) => $f->print_artwork_id ?? $f->design_id)
        ->sum(fn ($group) => $group->max('used_quantity'));

    $summary = [
        'available'         => $allFilms->sum('prints_available'),
        'in_stock'          => $allFilms->sum('prints_available'),
        'reserved'          => $allFilms->sum('reserved_quantity'),
        'used'              => $usedFilmCount,
        'printed_available' => 0, 
        'stock_value'       => $allFilms->sum(fn ($f) => (($f->prints_available ?? 0) + ($f->reserved_quantity ?? 0) + ($f->used_quantity ?? 0)) * ($f->cost_per_print ?? $f->cost ?? 0)),
        'low_stock_count'   => $allFilms->where('prints_available', '<=', self::LOW_STOCK_THRESHOLD)->count(),
    ];

    return view('inventory.films', compact('collections', 'summary', 'lowStockFilms', 'allFilms'));
    }

    public function addFilm(Request $request)
    {
        $data = $request->validate([
            'design_id' => 'required|exists:designs,id',
            'side' => 'required|in:front,back',
            'shirt_color' => 'nullable|string|max:30',
            'prints_available' => 'required|integer|min:1',
            'cost_per_print' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $design = Design::findOrFail($data['design_id']);
        if (!$design->print_artwork_id) {
            return back()->withErrors(['design_id' => 'This design has no linked print artwork yet.']);
        }

        $film = FilmInventory::firstOrNew([
            'print_artwork_id' => $design->print_artwork_id,
            'side' => $data['side'],
            'shirt_color' => $data['shirt_color'] ?? null,
        ]);
        $film->design_id = $design->id;
        $film->prints_available = ($film->exists ? $film->prints_available : 0) + $data['prints_available'];
        $film->cost_per_print = $data['cost_per_print'];
        $film->notes = $data['notes'] ?? $film->notes;
        $film->save();

        $matched = $this->orders->autoMatchOrderingOrders($design, $data['shirt_color'] ?? null);

        $msg = "Added {$data['prints_available']} prints to {$design->name} (" . ucfirst($data['side']) . ')!';
        if ($matched > 0) $msg .= " Auto-matched {$matched} order(s) waiting in Ordering.";
        return back()->with('success', $msg);
    }

    public function updateFilm(Request $request, FilmInventory $film)
    {
        $data = $request->validate([
            'cost_per_print' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $film->update($data);
        return back()->with('success', 'Film updated.');
    }

    public function removeFilmUnit(FilmInventory $film)
    {
        if ($film->prints_available > 0) {
            $film->decrement('prints_available');

            if ($film->prints_available === 0) {
                $designIds = array_filter([
                    $film->design_id,
                    ...Design::where('print_artwork_id', $film->print_artwork_id)->pluck('id')->toArray()
                ]);

                Order::whereIn('design_id', $designIds)
                    ->whereIn('film_status', ['Have Film', 'Ordering', 'have_film', 'ordering'])
                    ->whereNotIn('delivery_status', ['Delivered', 'delivered'])
                    ->update(['film_status' => 'No Film']);
            }

            return back()->with('success', 'Subtracted 1 film print from inventory.');
        }

        return back()->with('error', 'Film print count is already 0.');
    }

    public function deleteFilm(FilmInventory $film)
    {
        $designIds = array_filter([
            $film->design_id,
            ...Design::where('print_artwork_id', $film->print_artwork_id)->pluck('id')->toArray()
        ]);

        $ordersToReset = Order::whereIn('design_id', $designIds)
            ->whereIn('film_status', ['Have Film', 'Ordering', 'have_film', 'ordering'])
            ->whereNotIn('delivery_status', ['Delivered', 'delivered'])
            ->get();

        foreach ($ordersToReset as $order) {
            $order->update(['film_status' => 'No Film']);
        }

        $resetCount = $ordersToReset->count();
        $film->delete();

        $msg = 'Film removed from inventory.';
        if ($resetCount > 0) {
            $msg .= " Reset {$resetCount} order(s) back to 'No Film'.";
        }

        return back()->with('success', $msg);
    }

    public function storeColor(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:shirt_colors,name',
        ]);

        Color::create([
            'name' => ucfirst(trim($validated['name'])),
        ]);

        return redirect()->back()->with('success', 'Color added successfully!');
    }

    public function destroyColor(Color $color)
    {
        $color->delete();

        return redirect()->back()->with('success', 'Color deleted successfully!');
    }
}