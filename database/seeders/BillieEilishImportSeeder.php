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
 * Imports Billie Eilish collection historical orders.
 */
class BillieEilishImportSeeder extends Seeder
{
    private const FILM_COST_PER_SIDE = 0.50;
    private const SHIRT_UNIT_COST = 5.00;

    private array $customRows = [];

    public function run(): void
    {
        $this->importCsv(database_path('seeders/data/BillieEilish.csv'), 'Billie Eilish');

        if (!empty($this->customRows)) {
            $this->command->warn('Unrecognized item patterns imported as standalone designs:');
            foreach ($this->customRows as $c) {
                $this->command->warn("   Row #{$c['row']}: \"{$c['item']}\"");
            }
        }
    }

    private function importCsv(string $path, string $collectionName): void
    {
        if (!file_exists($path)) {
            $this->command->error("CSV file not found at {$path}");
            return;
        }

        $rows = array_map('str_getcsv', file($path));
        $header = array_map('trim', array_shift($rows));
        $rows = array_values(array_filter($rows, fn ($r) => count($r) >= count($header) && trim($r[0] ?? '') !== ''));

        DB::transaction(function () use ($rows, $header, $collectionName) {
            // 1. Setup Collection with 'BILL' short code
            $collection = Collection::firstOrCreate(
                ['name' => $collectionName],
                ['slug' => Str::slug($collectionName), 'short_code' => 'BILL', 'active' => true]
            );
            $collection->update(['short_code' => 'BILL']);

            // 2. Clear old Billie Eilish orders & reset sequence counter
            $designIds = Design::where('collection_id', $collection->id)->pluck('id');
            if ($designIds->isNotEmpty()) {
                Order::whereIn('design_id', $designIds)->delete();
            }

            OrderSequence::updateOrCreate(
                ['collection_id' => $collection->id],
                ['next_number' => 1]
            );

            // 3. Ensure base shirt type exists
            $shirtType = ShirtType::firstOrCreate(['name' => 'Boxy Tee']);

            $designCache = [];
            $artworkCache = [];

            // ── Pass 1: Setup Artworks, Designs, Films, and Shirt Stock ──
            foreach ($rows as $i => $rawRow) {
                $row = array_combine($header, $rawRow);
                $item = trim($row['Item']);
                $color = ucfirst(strtolower(trim($row['Color'])));
                $size = strtoupper(trim($row['Size'])); // Normalize Xl -> XL

                $resolved = $this->resolveDesign($item, $color);
                if ($resolved['custom'] ?? false) {
                    $this->customRows[] = ['row' => $i + 1, 'item' => $item];
                }

                // Print Artwork
                if (!isset($artworkCache[$resolved['artwork']])) {
                    $artwork = PrintArtwork::firstOrCreate(
                        ['collection_id' => $collection->id, 'name' => $resolved['artwork']],
                        ['has_front' => true, 'has_back' => str_contains(strtolower($item), 'back')]
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

                // Film Inventory
                $sides = $artwork->has_back ? ['front', 'back'] : ['front'];
                foreach ($sides as $side) {
                    FilmInventory::firstOrCreate(
                        ['print_artwork_id' => $artwork->id, 'side' => $side, 'shirt_color' => null],
                        ['design_id' => $design->id, 'prints_available' => 0, 'reserved_quantity' => 0, 'used_quantity' => 0, 'cost_per_print' => self::FILM_COST_PER_SIDE]
                    );
                }

                // Blank Shirt Inventory
                ShirtInventory::firstOrCreate(
                    ['type' => $shirtType->name, 'size' => $size, 'color' => $color],
                    ['quantity' => 0, 'reserved_quantity' => 0, 'used_quantity' => 0, 'printed_available' => 0, 'cost_per_unit' => self::SHIRT_UNIT_COST]
                );
            }

            // ── Pass 2: Insert Orders ──
            foreach ($rows as $rawRow) {
                $row = array_combine($header, $rawRow);
                $item = trim($row['Item']);
                $color = ucfirst(strtolower(trim($row['Color'])));

                $resolved = $this->resolveDesign($item, $color);
                $design = $designCache[$resolved['design']];

                $this->createOrder($row, $collection, $design, $shirtType);
            }
        });

        $this->command->info('Import complete: ' . count($rows) . ' orders from ' . $collectionName);
    }

    private function resolveDesign(string $item, string $color): array
    {
        $itemLower = strtolower($item);

        if (str_contains($itemLower, 'billie eyes')) {
            return [
                'design'  => 'Billie Eyes',
                'artwork' => 'Billie Eyes',
            ];
        }

        if (str_contains($itemLower, 'iltd')) {
            return [
                'design'  => 'Pink ILTD',
                'artwork' => 'Pink ILTD',
            ];
        }

        if (str_contains($itemLower, 'pink')) {
            return [
                'design'  => $color === 'Black' ? 'Pink Design (Black Shirt)' : 'Pink Design',
                'artwork' => 'Pink Design',
            ];
        }

        if (str_contains($itemLower, 'white')) {
            return [
                'design'  => $color === 'Black' ? 'White Design (Black Shirt)' : 'White Design',
                'artwork' => 'White Design',
            ];
        }

        return ['design' => trim($item), 'artwork' => trim($item), 'custom' => true];
    }

    private function createOrder(array $row, Collection $collection, Design $design, ShirtType $shirtType): void
    {
        $size = strtoupper(trim($row['Size']));
        $color = ucfirst(strtolower(trim($row['Color'])));

        $artwork = $design->printArtwork;
        $filmCost = $artwork ? $artwork->films->sum('cost_per_print') : self::FILM_COST_PER_SIDE;

        [$paymentStatus, $paymentMethod] = $this->parsePayment($row['Payment'] ?? '');
        $deliveryStatus = $this->mapDeliveryStatus($row['Status'] ?? '');
        $source = $this->mapSource($row['From'] ?? '');
        $orderDate = $this->parseDate($row['Date'] ?? '');

        $basePrice = $this->parseMoney($row['Base Price'] ?? '');
        $deliveryFee = $this->parseMoney($row['Delivery Fee'] ?? '');
        $total = $this->parseMoney($row['Total ($)'] ?? '');

        // Default prices if empty in CSV (e.g. pending/unpaid row #18)
        if ($basePrice == 0) $basePrice = 12.00;
        if ($total == 0) $total = $basePrice + $deliveryFee;

        $shirtCost = self::SHIRT_UNIT_COST;
        $profit = $total - $shirtCost - $filmCost;

        Order::create([
            'order_number'       => $this->nextOrderNumber($collection),
            'order_date'         => $orderDate,
            'customer_handle'    => ltrim(trim($row['Customer'] ?? ''), '@'),
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
            'shirt_status'       => trim($row['Shirt Status'] ?? 'Done') ?: 'Done',
            'film_status'        => 'Done',
            'print_status'       => trim($row['Print'] ?? 'Done') ?: 'Done',
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
            return ['Paid', $m[1] ?? 'ABA'];
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