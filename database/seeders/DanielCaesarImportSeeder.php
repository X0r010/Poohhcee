<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Design;
use App\Models\FilmInventory;
use App\Models\Order;
use App\Models\OrderSequence;
use App\Models\PrintArtwork;
use App\Models\ShirtInventory;
use App\Models\ShirtType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Imports Daniel Caesar's historical orders.
 * Handles multiple Designs sharing a single PrintArtwork (film) and
 * two shirt cuts (Boxy Tee @ $5, Boxy Long Sleeve @ $6).
 */
class DanielCaesarImportSeeder extends Seeder
{
    private const FILM_COST_PER_SIDE = 0.50;
    private const BOXY_TEE_COST = 5.00;
    private const BOXY_LONG_SLEEVE_COST = 6.00;

    private array $customRows = [];

    public function run(): void
    {
        $this->importCsv(database_path('seeders/data/DanielCaesar.csv'), 'Daniel Caesar');

        if (!empty($this->customRows)) {
            $this->command->warn('These rows did not match a known design pattern and were imported as standalone designs -- review and merge manually if needed:');
            foreach ($this->customRows as $c) {
                $this->command->warn("   Row #{$c['row']}: \"{$c['item']}\" -> created as its own Design/Artwork");
            }
        }
    }

    private function importCsv(string $path, string $collectionName): void
    {
        if (!file_exists($path)) {
            $this->command->error("CSV not found at {$path}");
            return;
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_shift($rows));
        $rows = array_values(array_filter($rows, fn ($r) => count($r) >= count($header) && trim($r[0] ?? '') !== ''));

        DB::transaction(function () use ($rows, $header, $collectionName) {
            // 1. Collection with explicit 'DANI' short code
            $collection = Collection::firstOrCreate(
                ['name' => $collectionName],
                ['slug' => Str::slug($collectionName), 'short_code' => 'DANI', 'active' => true]
            );
            $collection->update(['short_code' => 'DANI']);

            // 2. Clear old Daniel Caesar orders and reset order sequence to 1
            $designIds = Design::where('collection_id', $collection->id)->pluck('id');
            if ($designIds->isNotEmpty()) {
                Order::whereIn('design_id', $designIds)->delete();
            }

            OrderSequence::updateOrCreate(
                ['collection_id' => $collection->id],
                ['next_number' => 1]
            );

            // 3. Ensure shirt types exist
            $boxyTee = ShirtType::firstOrCreate(['name' => 'Boxy Tee']);
            $boxyLongSleeve = ShirtType::firstOrCreate(['name' => 'Boxy Long Sleeve']);

            $designCache = [];  // designName => Design
            $artworkCache = []; // artworkName => PrintArtwork
            $shirtCache = [];   // "type|size|color" => ShirtInventory

            // ── Pass 1: Setup Artworks, Designs, Films, and Shirts ──
            foreach ($rows as $i => $rawRow) {
                $row = array_combine($header, $rawRow);
                $item = trim($row['Item']);
                $color = trim($row['Color']);
                $size = trim($row['Size']);
                $isLong = str_contains(strtolower($item), 'long');

                $resolved = $this->resolveDesign($item, $color, $isLong);
                if ($resolved['custom'] ?? false) {
                    $this->customRows[] = ['row' => $i + 1, 'item' => $item];
                }

                // Artwork (shared film)
                if (!isset($artworkCache[$resolved['artwork']])) {
                    $artwork = PrintArtwork::firstOrCreate(
                        ['collection_id' => $collection->id, 'name' => $resolved['artwork']],
                        ['has_front' => true, 'has_back' => true]
                    );
                    $artworkCache[$resolved['artwork']] = $artwork;
                }
                $artwork = $artworkCache[$resolved['artwork']];

                // Design
                if (!isset($designCache[$resolved['design']])) {
                    $design = Design::firstOrCreate(
                        ['collection_id' => $collection->id, 'name' => $resolved['design']],
                        ['active' => true, 'print_artwork_id' => $artwork->id]
                    );
                    if (!$design->print_artwork_id) {
                        $design->update(['print_artwork_id' => $artwork->id]);
                    }
                    $designCache[$resolved['design']] = $design;
                }
                $design = $designCache[$resolved['design']];

                // Film Inventory (includes design_id)
                foreach (['front', 'back'] as $side) {
                    FilmInventory::firstOrCreate(
                        ['print_artwork_id' => $artwork->id, 'side' => $side, 'shirt_color' => null],
                        ['design_id' => $design->id, 'prints_available' => 0, 'reserved_quantity' => 0, 'used_quantity' => 0, 'cost_per_print' => self::FILM_COST_PER_SIDE]
                    );
                }

                // Shirt stock
                $shirtType = $isLong ? $boxyLongSleeve : $boxyTee;
                $shirtCost = $isLong ? self::BOXY_LONG_SLEEVE_COST : self::BOXY_TEE_COST;
                $variantKey = $shirtType->name . '|' . $size . '|' . $color;

                if (!isset($shirtCache[$variantKey])) {
                    $shirtCache[$variantKey] = ShirtInventory::firstOrCreate(
                        ['type' => $shirtType->name, 'size' => $size, 'color' => $color],
                        ['quantity' => 0, 'reserved_quantity' => 0, 'used_quantity' => 0, 'printed_available' => 0, 'cost_per_unit' => $shirtCost]
                    );
                }
            }

            // Reset used_quantity counters before re-importing
            foreach ($shirtCache as $shirt) {
                $shirt->update(['used_quantity' => 0]);
            }
            foreach ($artworkCache as $artwork) {
                FilmInventory::where('print_artwork_id', $artwork->id)->update(['used_quantity' => 0]);
            }

            // ── Pass 2: Process orders and increment inventory ──
            foreach ($rows as $rawRow) {
                $row = array_combine($header, $rawRow);
                $item = trim($row['Item']);
                $color = trim($row['Color']);
                $size = trim($row['Size']);
                $isLong = str_contains(strtolower($item), 'long');

                $resolved = $this->resolveDesign($item, $color, $isLong);
                $design = $designCache[$resolved['design']];
                $artwork = $artworkCache[$resolved['artwork']];

                $shirtType = $isLong ? $boxyLongSleeve : $boxyTee;
                $shirtCost = $isLong ? self::BOXY_LONG_SLEEVE_COST : self::BOXY_TEE_COST;
                $variantKey = $shirtType->name . '|' . $size . '|' . $color;

                $shirt = $shirtCache[$variantKey];
                $shirt->increment('used_quantity');

                foreach (['front', 'back'] as $side) {
                    $film = FilmInventory::where('print_artwork_id', $artwork->id)->where('side', $side)->first();
                    if ($film) $film->increment('used_quantity');
                }

                $this->createOrder($row, $collection, $design, $shirtType, $shirtCost);
            }
        });

        $this->command->info('Import complete: ' . count($rows) . ' orders from ' . $collectionName);
    }

    private function resolveDesign(string $item, string $color, bool $isLong): array
    {
        $itemLower = strtolower($item);
        $colorLabel = ucfirst(strtolower(trim($color)));

        if (str_contains($itemLower, 'never enough')) {
            return [
                'design'  => "Never Enough {$colorLabel}" . ($isLong ? ' Long' : ''),
                'artwork' => "Never Enough {$colorLabel}",
            ];
        }

        if (str_contains($itemLower, 'who knows')) {
            return [
                'design'  => 'Who Knows' . ($isLong ? ' Long' : ''),
                'artwork' => 'Who Knows',
            ];
        }

        if (str_contains($itemLower, 'get you')) {
            return [
                'design'  => trim($item),
                'artwork' => 'Get You',
            ];
        }

        return ['design' => trim($item), 'artwork' => trim($item), 'custom' => true];
    }

    private function createOrder(array $row, Collection $collection, Design $design, ShirtType $shirtType, float $shirtCost): void
    {
        $size = trim($row['Size']);
        $color = trim($row['Color']);

        $artwork = $design->printArtwork;
        $filmCost = $artwork ? $artwork->films->sum('cost_per_print') : 0;

        [$paymentStatus, $paymentMethod] = $this->parsePayment($row['Payment']);
        $deliveryStatus = $this->mapDeliveryStatus($row['Status']);
        $source = $this->mapSource($row['From']);
        $orderDate = $this->parseDate($row['Date']);

        $basePrice = $this->parseMoney($row['Base Price']);
        $deliveryFee = $this->parseMoney($row['Delivery Fee']);
        $total = $this->parseMoney($row['Total ($)']);
        if ($total == 0 && $basePrice > 0) $total = $basePrice + $deliveryFee;

        $profit = $total - $shirtCost - $filmCost;

        Order::create([
            'order_number'       => $this->nextOrderNumber($collection),
            'order_date'         => $orderDate,
            'customer_handle'    => ltrim(trim($row['Customer']), '@'),
            'customer_phone'     => trim($row['Number'] ?? '') ?: null,
            'customer_location'  => Str::limit(trim($row['Location'] ?? ''), 250, '') ?: null,
            'source'             => $source,
            'design_id'          => $design->id,
            'size'               => $size,
            'color'              => $color,
            'shirt_type_id'      => $shirtType->id,
            'base_price'         => $basePrice,
            'delivery_fee'       => $deliveryFee,
            'total_price'        => $total,
            'shirt_cost'         => $shirtCost,
            'film_cost'          => $filmCost,
            'profit'             => $profit,
            'payment_status'     => $paymentStatus,
            'payment_method'     => $paymentMethod,
            'partial_amount'     => 0,
            'shirt_status'       => 'Done',
            'film_status'        => 'Done',
            'print_status'       => 'Done',
            'delivery_status'    => $deliveryStatus,
            'readiness'          => 'printed',
            'notes'              => 'Imported from CSV',
        ]);
    }

    private function nextOrderNumber(Collection $collection): string
    {
        $seq = OrderSequence::firstOrCreate(['collection_id' => $collection->id], ['next_number' => 1]);
        $number = $seq->next_number;
        $seq->increment('next_number');
        return $collection->short_code . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    private function parsePayment(string $raw): array
    {
        $raw = trim($raw);
        if (stripos($raw, 'paid') === 0) {
            preg_match('/\(([^)]+)\)/', $raw, $m);
            return ['Paid', $m[1] ?? null];
        }
        if (stripos($raw, 'partial') !== false) return ['Partial', null];
        return ['Not Yet', null];
    }

    private function mapDeliveryStatus(string $raw): string
    {
        $raw = trim($raw);
        return in_array($raw, ['Pending', 'Packaging', 'Delivering', 'Delivered', 'Cancelled']) ? $raw : 'Delivered';
    }

    private function mapSource(string $raw): string
    {
        $raw = trim($raw);
        return match (true) {
            stripos($raw, 'tik') !== false => 'TikTok',
            strtoupper($raw) === 'IG' => 'Instagram',
            strtoupper($raw) === 'FB' => 'Other',
            default => in_array($raw, ['TikTok', 'Instagram', 'Website', 'Walk-in', 'Other']) ? $raw : 'Other',
        };
    }

    private function parseDate(string $raw): string
    {
        $parts = explode('.', trim($raw));
        [$d, $m, $y] = $parts + ['01', '01', '2026'];
        $y = strlen($y) === 2 ? '20' . $y : $y;
        return sprintf('%04d-%02d-%02d', (int) $y, (int) $m, (int) $d);
    }

    private function parseMoney(?string $raw): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $raw ?? '0');
    }
}