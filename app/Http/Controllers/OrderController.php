<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Collection;
use App\Models\Design;
use App\Models\FilmInventory;
use App\Models\Order;
use App\Models\Shirt;
use App\Models\ShirtType;
use App\Models\ShirtColor;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use App\Models\ShirtInventory;
use App\Models\PrintedShirt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orders,
        private InventoryService $inventory,
    ) {}

    private const PAGE_SIZE = 15;

    public function index(Request $request)
    {
        $collections = Collection::where('active', true)
            ->with(['designs' => fn ($q) => $q->where('active', true)])
            ->orderBy('name')
            ->get();

        // Global orders table - sorted latest first
        $globalOrders = $this->filteredQuery($request)
            ->paginate(self::PAGE_SIZE, ['*'], 'page')
            ->withQueryString();

        // Per-collection orders tables - sorted latest first and filtered
        $collectionOrders = $collections->mapWithKeys(function ($collection) use ($request) {
            $req = clone $request;
            $req->merge(['collection' => $collection->id]);

            $orders = $this->filteredQuery($req)
                ->take(self::PAGE_SIZE)
                ->get();

            return [$collection->id => $orders];
        });

        return view('orders.index', compact('globalOrders', 'collections', 'collectionOrders'));
    }

    public function rows(Request $request)
    {
        $page = max(1, (int) $request->input('page', 2));

        $req = clone $request;
        if ($request->input('scope') === 'collection' && $request->filled('collection_id')) {
            $req->merge(['collection' => $request->collection_id]);
        }

        $orders = $this->filteredQuery($req)
            ->skip(($page - 1) * self::PAGE_SIZE)
            ->take(self::PAGE_SIZE)
            ->get();

        return view('orders._rows', compact('orders'))->render();
    }

    private function filteredQuery(Request $request)
    {
        return Order::with('design.collection')
            ->orderBy('id', 'desc') // Guarantees latest orders stay on top globally and per collection
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->search;
                $q->where(function ($sub) use ($search) {
                    $sub->where('customer_handle', 'like', '%' . $search . '%')
                        ->orWhere('order_number', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('collection'), fn ($q) => $q->whereHas('design', fn ($qq) => $qq->where('collection_id', $request->collection)))
            ->when($request->filled('readiness'), fn ($q) => $q->where('readiness', $request->readiness))
            ->when($request->filled('delivery_status'), fn ($q) => $q->where('delivery_status', $request->delivery_status))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->payment_status))
            ->when($request->filled('print_status'), fn ($q) => $q->where('print_status', $request->print_status))
            ->when($request->filled('shirt_status'), fn ($q) => $q->where('shirt_status', $request->shirt_status))
            ->when($request->filled('film_status'), fn ($q) => $q->where('film_status', $request->film_status))
            ->when($request->filled('shirt_type_id'), fn ($q) => $q->where('shirt_type_id', $request->shirt_type_id))
            ->when($request->filled('size'), fn ($q) => $q->where('size', $request->size))
            ->when($request->filled('color'), fn ($q) => $q->where('color', $request->color));
    }

    public function create()
    {
        $collections = Collection::with('designs')->where('active', true)->get();
        $shirtTypes = ShirtType::orderBy('name')->get();
        $shirtColors = ShirtColor::orderBy('name')->get();

        return view('orders.create', compact('collections', 'shirtTypes', 'shirtColors'));
    }

    public function store(StoreOrderRequest $request)
    {
        $data = $request->validated();
        
        $data['total_price'] = $data['base_price'] + ($data['delivery_fee'] ?? 0);

        $order = $this->orders->create($data);

        return redirect()->route('orders.index')->with('success', "Order {$order->order_number} added!");
    }

    public function show(Order $order)
    {
        $order->load('design.collection');
        return view('orders.show', compact('order'));
    }

    public function edit(Order $order)
    {
        $collections = Collection::with('designs')->where('active', true)->get();
        $shirtTypes = ShirtType::orderBy('name')->get();
        $shirtColors = ShirtColor::orderBy('name')->get();

        return view('orders.edit', compact('order', 'collections', 'shirtTypes', 'shirtColors'));
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'customer_handle'   => 'required|string|max:255',
            'customer_phone'    => 'nullable|string|max:255',
            'customer_location' => 'nullable|string|max:255',
            'source'            => 'required|in:TikTok,Instagram,Website,Walk-in,Other',
            'order_date'        => 'required|date',
            'design_id'         => 'required|exists:designs,id',
            'shirt_type_id'     => 'required',
            'size'              => 'required|string',
            'color'             => 'required|string',
            'shirt_status'      => 'required|in:Not Yet,Buying,Bought,Done',
            'film_status'       => 'required|in:No Film,Ordering,Have Film,Printed,Done',
            'print_status'      => 'required|in:Pending,Printing,Printed,Done',
            'delivery_status'   => 'required|in:Pending,Packaging,Delivering,Delivered,Cancelled',
            'payment_status'    => 'required|in:Not Yet,Partial,Paid',
            'payment_method'    => 'nullable|string',
            'base_price'        => 'required|numeric|min:0',
            'delivery_fee'      => 'nullable|numeric|min:0',
            'notes'             => 'nullable|string',
        ]);

        $data['total_price'] = $data['base_price'] + ($data['delivery_fee'] ?? 0);
        $data['profit'] = $data['total_price'] - $order->shirt_cost - $order->film_cost;

        $wasBought    = ($order->shirt_status === 'Bought');
        $isBought     = ($data['shirt_status'] === 'Bought');
        $specsChanged = ($data['size'] !== $order->size || $data['color'] !== $order->color || $data['shirt_type_id'] != $order->shirt_type_id);

        DB::transaction(function () use ($order, $data, $wasBought, $isBought, $specsChanged) {
            if ($wasBought && (!$isBought || $specsChanged)) {
                $oldTypeName = ShirtType::find($order->shirt_type_id)?->name ?? $order->shirt_type_id;
                $oldStock = ShirtInventory::where(function ($q) use ($oldTypeName, $order) {
                        $q->where('type', $oldTypeName)->orWhere('type', $order->shirt_type_id);
                    })
                    ->where('size', $order->size)
                    ->where('color', $order->color)
                    ->first();

                if ($oldStock) {
                    $oldStock->increment('quantity', 1);
                    $oldStock->decrement('reserved_quantity', 1);
                }
            }

            if ($isBought && (!$wasBought || $specsChanged)) {
                $newTypeName = ShirtType::find($data['shirt_type_id'])?->name ?? $data['shirt_type_id'];
                $newStock = ShirtInventory::where(function ($q) use ($newTypeName, $data) {
                        $q->where('type', $newTypeName)->orWhere('type', $data['shirt_type_id']);
                    })
                    ->where('size', $data['size'])
                    ->where('color', $data['color'])
                    ->where('quantity', '>', 0)
                    ->first();

                if ($newStock) {
                    $newStock->decrement('quantity', 1);
                    $newStock->increment('reserved_quantity', 1);
                }
            }

            $order->update($data);
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully!');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $map = [
            'shirt_status'    => ['Not Yet', 'Buying', 'Bought', 'Done', 'not yet', 'buying', 'bought', 'done'],
            'film_status'     => ['No Film', 'Ordering', 'Have Film', 'Printed', 'Done', 'no film', 'ordering', 'have film', 'printed', 'done'],
            'print_status'    => ['Pending', 'Printing', 'Printed', 'Done', 'pending', 'printing', 'printed', 'done'],
            'delivery_status' => ['Pending', 'Packaging', 'Delivering', 'Delivered', 'Cancelled', 'pending', 'packaging', 'delivering', 'delivered', 'cancelled'],
            'payment_status'  => ['Not Yet', 'Partial', 'Paid', 'not yet', 'partial', 'paid'],
        ];

        foreach ($map as $field => $allowed) {
            if ($request->filled($field)) {
                $request->validate([$field => 'in:' . implode(',', $allowed)]);
                $newVal = $request->input($field);

                try {
                    if ($field === 'print_status' && in_array(strtolower($newVal), ['printed', 'done'])) {
                        $this->markOrderAsPrinted($order);
                    } else {
                        $this->orders->updateStatusField($order, $field, $newVal);
                    }
                } catch (\RuntimeException $e) {
                    return back()->with('error', $e->getMessage());
                }
                return back()->with('success', 'Status updated!');
            }
        }
        return back()->withErrors(['status' => 'No valid status field was submitted.']);
    }

    public function advancePrint(Order $order)
    {
        try {
            $status = strtolower(trim($order->print_status ?? 'pending'));

            if (in_array($status, ['pending', 'not yet', ''])) {
                $order->print_status = 'Printing';
                $order->save();
                return back()->with('success', 'Order #' . $order->order_number . ' is now Printing!');
            }
            
            if ($status === 'printing') {
                $this->markOrderAsPrinted($order);
                return back()->with('success', '✅ SUCCESS: Order #' . $order->order_number . ' printed, stock deducted & COGS calculated!');
            }

            $this->orders->advancePrint($order);
            return back()->with('success', 'Print status advanced!');
            
        } catch (\Exception $e) {
            return back()->with('error', 'Database Error: ' . $e->getMessage());
        }
    }

    private function markOrderAsPrinted(Order $order): void
    {
        DB::transaction(function () use ($order) {
            if (!$order->printed_shirt_id) {
                $matchAttributes = [
                    'design_id' => $order->design_id,
                    'size'      => $order->size,
                    'color'     => $order->color,
                ];

                if (Schema::hasColumn('printed_shirts', 'shirt_type_id')) {
                    $matchAttributes['shirt_type_id'] = $order->shirt_type_id;
                }

                $printedShirt = PrintedShirt::firstOrCreate(
                    $matchAttributes,
                    ['quantity'  => 0]
                );
                
                $order->printed_shirt_id = $printedShirt->id;
            }

            $shirtCost = 0;
            $filmCost = 0;

            $typeName = ShirtType::find($order->shirt_type_id)?->name ?? $order->shirt_type_id;
            $shirtStock = ShirtInventory::where(function ($q) use ($typeName, $order) {
                    $q->where('type', $typeName)->orWhere('type', $order->shirt_type_id);
                })
                ->where('size', $order->size)
                ->where('color', $order->color)
                ->first();

            if ($shirtStock) {
                if ($shirtStock->reserved_quantity > 0) {
                    $shirtStock->decrement('reserved_quantity');
                }
                $shirtStock->increment('used_quantity');
                
                $shirtCost = (float) ($shirtStock->cost_per_unit ?? $shirtStock->cost ?? 0);
            }

            if ($order->design_id) {
                $design = Design::find($order->design_id);
                $filmQuery = FilmInventory::query();

                if ($design && $design->print_artwork_id) {
                    $filmQuery->where('print_artwork_id', $design->print_artwork_id);
                } else {
                    $filmQuery->where('design_id', $order->design_id);
                }

                $films = $filmQuery->get();
                foreach ($films as $film) {
                    if ($film->reserved_quantity > 0) {
                        $film->decrement('reserved_quantity');
                    }
                    $film->increment('used_quantity');
                    
                    $filmCost += (float) ($film->cost_per_print ?? $film->cost_per_unit ?? $film->cost ?? 0);
                }
            }

            $order->print_status = 'Printed';
            $order->film_status  = 'Printed';
            
            $order->shirt_cost = $shirtCost;
            $order->film_cost  = $filmCost;
            
            $totalCogs = $shirtCost + $filmCost;
            
            if (Schema::hasColumn('orders', 'cogs')) {
                $order->cogs = $totalCogs;
            }
            
            $order->profit = (float) $order->total_price - $totalCogs;
            
            $order->save();
        });
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }
    
    public function pipeline(Request $request)
    {
        $orders = Order::with('design.collection')
            ->where('delivery_status', '!=', 'Cancelled')
            ->where(function ($q) {
                $q->where('delivery_status', '!=', 'Delivered')
                  ->orWhere('payment_status', '!=', 'Paid');
            })
            ->orderBy('id', 'desc')
            ->get();

        $groupDefs = [
            'missing_both'        => ['label' => 'Missing Shirt + Film', 'badge' => 'badge-missing-both'],
            'missing_shirt'       => ['label' => 'Missing Shirt',        'badge' => 'badge-missing-shirt'],
            'missing_film'        => ['label' => 'Missing Film',         'badge' => 'badge-missing-film'],
            'buying'              => ['label' => 'Buying',               'badge' => 'badge-missing-shirt'],
            'ordering'            => ['label' => 'Ordering',             'badge' => 'badge-missing-film'],
            'buying_and_ordering' => ['label' => 'Buying + Ordering',    'badge' => 'badge-missing-both'],
            'print_pending'       => ['label' => 'Print Pending',        'badge' => 'badge-ready'],
            'printing'            => ['label' => 'Printing',             'badge' => 'status-printed'],
            'printed'             => ['label' => 'Printed',              'badge' => 'status-printed'],
            'packaging'           => ['label' => 'Packaging',            'badge' => 'status-packaging'],
            'delivering'          => ['label' => 'Delivering',           'badge' => 'status-delivering'],
            'awaiting_payment'    => ['label' => 'Awaiting Payment',     'badge' => 'payment-unpaid'],
            'unpaid'              => ['label' => 'Unpaid',               'badge' => 'payment-unpaid'],
        ];

        $groups = collect(array_keys($groupDefs))->mapWithKeys(fn ($key) => [$key => collect()]);

        foreach ($orders as $order) {
            foreach ($this->pipelineCategoriesFor($order) as $cat) {
                $groups[$cat]->push($order);
            }
        }

        foreach ($groups as $cat => $collection) {
            $groups[$cat] = $collection->sortByDesc(function ($o) {
                $num = preg_replace('/[^0-9]/', '', (string)$o->order_number);
                return $num !== '' ? (int)$num : $o->id;
            })->values();
        }

        $unpaidOrders = Order::where('delivery_status', '!=', 'Cancelled')->where('payment_status', '!=', 'Paid')->get();
        $moneyOwed = $unpaidOrders->sum(fn ($o) => $o->total_price - $o->partial_amount);
        $unpaidCount = $unpaidOrders->count();

        return view('orders.pipeline', compact('groups', 'groupDefs', 'moneyOwed', 'unpaidCount'));
    }

    private function pipelineCategoriesFor(Order $order): array
    {
        $deliv = strtolower(trim($order->delivery_status ?? ''));
        $print = strtolower(trim($order->print_status ?? ''));
        $shirt = strtolower(trim($order->shirt_status ?? ''));
        $film  = strtolower(trim($order->film_status ?? ''));

        if ($deliv === 'delivered') return ['awaiting_payment'];
        if ($deliv === 'delivering') return $this->withUnpaid($order, ['delivering']);
        if ($deliv === 'packaging') return $this->withUnpaid($order, ['packaging']);
        if (in_array($print, ['printed', 'done'])) return $this->withUnpaid($order, ['printed']);
        if ($print === 'printing') return $this->withUnpaid($order, ['printing']);
        if ($order->readiness === 'ready' && in_array($print, ['pending', 'not yet', ''])) return $this->withUnpaid($order, ['print_pending']);

        $shirtCat = match ($shirt) {
            'not yet' => 'missing_shirt',
            'buying'  => 'buying',
            default   => null,
        };
        $filmCat = match ($film) {
            'no film'  => 'missing_film',
            'ordering' => 'ordering',
            default    => null,
        };

        if ($shirtCat === 'missing_shirt' && $filmCat === 'missing_film') {
            $categories = ['missing_both'];
        } elseif ($shirtCat === 'buying' && $filmCat === 'ordering') {
            $categories = ['buying_and_ordering'];
        } else {
            $categories = array_values(array_filter([$shirtCat, $filmCat]));
        }

        if (empty($categories)) {
            $categories = ['print_pending'];
        }

        return $this->withUnpaid($order, $categories);
    }

    private function withUnpaid(Order $order, array $categories): array
    {
        if (strtolower(trim($order->payment_status ?? '')) !== 'paid') {
            $categories[] = 'unpaid';
        }
        return $categories;
    }

    public function buylist(Request $request)
    {
    $collections = Collection::where('active', true)
        ->with(['designs' => fn ($q) => $q->where('active', true)])
        ->get();

    $orders = Order::with(['design.collection'])
        ->whereNotIn('delivery_status', ['Delivered', 'Cancelled'])
        ->orderBy('order_date')
        ->get()
        ->map(function($order) {
            $shirtType = 'Standard Tee';
            if (method_exists($order, 'shirtType') && $order->shirtType) {
                $shirtType = $order->shirtType->name;
            } elseif (!empty($order->type)) {
                $shirtType = $order->type;
            } elseif (!empty($order->shirt_type)) {
                $shirtType = $order->shirt_type;
            }

            return [
                'id' => $order->id,
                'customer_handle' => $order->customer_handle,
                'order_number' => $order->order_number,
                'design_id' => $order->design_id,
                'design' => $order->design->name ?? 'Unknown Design',
                'has_front' => (bool) ($order->design->has_front ?? true),
                'has_back' => (bool) ($order->design->has_back ?? false),
                'collection_id' => $order->design->collection_id ?? null,
                'collection' => $order->design->collection->name ?? 'Unknown Collection',
                'shirt_type' => $shirtType,
                'size' => $order->size ?? 'N/A',
                'color' => $order->color ?? 'N/A',
                'shirt_status' => $order->shirt_status ?? 'Not Yet',
                'film_status' => $order->film_status ?? 'No Film',
                'printed_shirt_id' => $order->printed_shirt_id,
            ];
        });

    $lowStockThreshold = 2;
    $blockingDesignIds = Order::whereNotIn('delivery_status', ['Delivered', 'Cancelled'])
                              ->where('film_status', 'No Film')
                              ->pluck('design_id')
                              ->unique();

    $filmList = collect();
    foreach ($collections as $collection) {
        foreach ($collection->designs as $design) {
            $frontLow = $design->has_front && ($this->inventory->resolveFilm($design, 'front')?->prints_available ?? 0) <= $lowStockThreshold;
            $backLow  = $design->has_back  && ($this->inventory->resolveFilm($design, 'back')?->prints_available ?? 0) <= $lowStockThreshold;
            $isBlocking = $blockingDesignIds->contains($design->id);

            if ($frontLow || $backLow || $isBlocking) {
                $filmList->push([
                    'id' => $design->id,
                    'design' => $design->name, 
                    'collection' => $collection->name,
                    'front_low' => $frontLow, 
                    'back_low' => $backLow, 
                    'is_blocking' => $isBlocking,
                ]);
            }
        }
    }

    return view('orders.buylist', compact('orders', 'collections', 'filmList'));
    }

    public function checkInventory(Request $request)
    {
        $result = ['shirt' => null, 'film' => null];

        try {
            if ($request->filled(['size', 'color'])) {
                $result['shirt'] = $this->checkShirtAvailability($request);
            }

            if ($request->filled('design_id')) {
                $result['film'] = $this->checkFilmAvailability($request);
            }
        } catch (\Throwable $e) {
            Log::error('checkInventory exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $result['shirt'] = [
                'status' => 'Not Yet',
                'class' => 'warning',
                'printed_available' => false,
                'printed_shirt_id' => null,
                'quantity' => 0,
                'message' => 'Unable to check stock.',
            ];
        }

        return response()->json($result);
    }

    private function checkShirtAvailability(Request $request): array
    {  
        $fallback = [
            'status' => 'Not Yet',
            'printed_available' => false,
            'printed_shirt_id' => null,
        ];

        if ($request->filled(['design_id', 'size', 'color'])) {
            try {
                $printedQuery = PrintedShirt::where('design_id', $request->design_id)
                    ->where('size', $request->size)
                    ->where('color', $request->color)
                    ->where('quantity', '>', 0);

                if ($request->filled('shirt_type_id')) {
                    $shirtTypeId = $request->shirt_type_id;
                    $printedQuery->where(function ($q) use ($shirtTypeId) {
                        if (Schema::hasColumn('printed_shirts', 'shirt_type_id')) {
                            $q->where('shirt_type_id', $shirtTypeId);
                        }
                        if (Schema::hasColumn('printed_shirts', 'type')) {
                            $typeName = ShirtType::find($shirtTypeId)?->name;
                            if ($typeName) {
                                $q->orWhere('type', $typeName);
                            }
                        }
                    });
                }

                $printedShirt = $printedQuery->first();

                if ($printedShirt) {
                    return [
                        'status' => 'Done',
                        'class' => 'success',
                        'printed_available' => true,
                        'printed_shirt_id' => $printedShirt->id,
                        'quantity' => $printedShirt->quantity,
                        'message' => "Printed spare available ({$printedShirt->size}/{$printedShirt->color}) — ships immediately.",
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('PrintedShirt check failed: ' . $e->getMessage());
            }
        }

        $shirtTypeId = $request->input('shirt_type_id');
        $typeName = null;

        if ($shirtTypeId) {
            $typeName = ShirtType::find($shirtTypeId)?->name ?? $request->input('type') ?? $shirtTypeId;
        } elseif ($request->filled('type')) {
            $typeName = $request->input('type');
        }

        if (!$typeName) {
            return array_merge($fallback, [
                'class' => 'warning',
                'message' => 'Select a shirt type...',
            ]);
        }

        try {
            $shirt = $this->inventory->findShirt($typeName, $request->size, $request->color);

            return match (true) {
                $shirt && $shirt->quantity > 0 => array_merge($fallback, [
                    'class' => 'success',
                    'quantity' => $shirt->quantity,
                    'message' => "{$shirt->quantity} in stock.",
                ]),
                $shirt => array_merge($fallback, [
                    'class' => 'error',
                    'quantity' => 0,
                    'message' => 'Out of stock — need to buy.',
                ]),
                default => array_merge($fallback, [
                    'class' => 'warning',
                    'quantity' => 0,
                    'message' => 'Shirt type not found in inventory.',
                ]),
            };
        } catch (\Throwable $e) {
            Log::error('findShirt error: ' . $e->getMessage());
            return array_merge($fallback, [
                'class' => 'warning',
                'quantity' => 0,
                'message' => 'Inventory lookup error.',
            ]);
        }
    }

    private function checkFilmAvailability(Request $request): array
    {
        try {
            $design = Design::find($request->design_id);
            if (!$design) {
                return ['status' => 'No Film', 'class' => 'warning', 'message' => 'Design not found.'];
            }

            $frontFilm = $design->has_front ? $this->inventory->resolveFilm($design, 'front') : null;
            $backFilm  = $design->has_back  ? $this->inventory->resolveFilm($design, 'back')  : null;

            $frontReady = !$design->has_front || (($frontFilm?->prints_available ?? 0) > 0);
            $backReady  = !$design->has_back  || (($backFilm?->prints_available ?? 0) > 0);

            if ($frontReady && $backReady) {
                return [
                    'status'  => 'Have Film',
                    'class'   => 'success',
                    'message' => 'Film prints ready.',
                ];
            }

            $missing = [];
            if (!$frontReady) $missing[] = 'front';
            if (!$backReady)  $missing[] = 'back';

            return [
                'status'  => 'No Film',
                'class'   => 'error',
                'message' => 'Missing ' . implode(' & ', $missing) . ' film print(s).',
            ];
        } catch (\Throwable $e) {
            Log::error('checkFilmAvailability error: ' . $e->getMessage());
            return [
                'status'  => 'No Film',
                'class'   => 'warning',
                'message' => 'Film stock status unavailable.',
            ];
        }
    }
}