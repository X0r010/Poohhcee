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

class OrderImportSeeder extends Seeder
{
    private const SHIRT_COST = 5.00;
    private const FILM_COST_PER_SIDE = 0.50;

    public function run(): void
    {
        $this->importCsv(database_path('seeders/data/Clairo.csv'), 'Clairo');
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
            // 1. Force short code to 'CLAI'
            $collection = Collection::firstOrCreate(
                ['name' => $collectionName],
                ['slug' => Str::slug($collectionName), 'short_code' => 'CLAI', 'active' => true]
            );
            $collection->update(['short_code' => 'CLAI']);

            // 2. Clear old Clairo orders & reset sequence to 1
            $designIds = Design::where('collection_id', $collection->id)->pluck('id');
            if ($designIds->isNotEmpty()) {
                Order::whereIn('design_id', $designIds)->delete();
            }

            OrderSequence::updateOrCreate(
                ['collection_id' => $collection->id],
                ['next_number' => 1]
            );

            // 3. Target 'Boxy Tee' shirt type
            $shirtType = ShirtType::firstOrCreate(['name' => 'Boxy Tee']);

            // ── Pass 1: stock inventory ──
            $seenVariants = [];
            $seenDesigns = [];

            foreach ($rows as $row) {
                $data = array_combine($header, $row);
                $size = trim($data['Size']);
                $color = trim($data['Color']);
                $variantKey = $size . '|' . $color;

                if (!isset($seenVariants[$variantKey])) {
                    ShirtInventory::firstOrCreate(
                        ['type' => 'Boxy Tee', 'size' => $size, 'color' => $color],
                        ['quantity' => 0, 'reserved_quantity' => 0, 'used_quantity' => 0, 'printed_available' => 0, 'cost_per_unit' => self::SHIRT_COST]
                    );
                    $seenVariants[$variantKey] = true;
                }

                $designName = trim($data['Item']);
                if (!isset($seenDesigns[$designName])) {
                    $design = Design::firstOrCreate(
                        ['collection_id' => $collection->id, 'name' => $designName],
                        ['active' => true]
                    );

                    if (!$design->print_artwork_id) {
                        $artwork = PrintArtwork::create([
                            'collection_id' => $collection->id,
                            'name' => $designName,
                            'has_front' => true,
                            'has_back' => true,
                        ]);
                        $design->update(['print_artwork_id' => $artwork->id]);
                    }

                    foreach (['front', 'back'] as $side) {
                        FilmInventory::firstOrCreate(
                            ['print_artwork_id' => $design->print_artwork_id, 'side' => $side, 'shirt_color' => null],
                            ['design_id' => $design->id, 'prints_available' => 0, 'reserved_quantity' => 0, 'used_quantity' => 0, 'cost_per_print' => self::FILM_COST_PER_SIDE]
                        );
                    }

                    $seenDesigns[$designName] = $design;
                }
            }

            // Reset used_quantity before recounting
            foreach ($seenVariants as $vKey => $_) {
                [$s, $c] = explode('|', $vKey);
                ShirtInventory::where('type', 'Boxy Tee')->where('size', $s)->where('color', $c)->update(['used_quantity' => 0]);
            }
            foreach ($seenDesigns as $design) {
                FilmInventory::where('print_artwork_id', $design->print_artwork_id)->update(['used_quantity' => 0]);
            }

            // ── Pass 2: create orders and increment used counters ──
            foreach ($rows as $row) {
                $data = array_combine($header, $row);
                $this->importRow($data, $collection, $shirtType, $seenDesigns[trim($data['Item'])]);
            }
        });

        $this->command->info('Import complete: ' . count($rows) . ' orders from ' . $collectionName);
    }

    private function importRow(array $row, Collection $collection, ShirtType $shirtType, Design $design): void
    {
        $size = trim($row['Size']);
        $color = trim($row['Color']);

        $shirt = ShirtInventory::where('type', 'Boxy Tee')->where('size', $size)->where('color', $color)->first();
        if ($shirt) $shirt->increment('used_quantity');
        $shirtCost = $shirt->cost_per_unit ?? self::SHIRT_COST;

        $filmCost = 0;
        foreach (['front', 'back'] as $side) {
            $film = FilmInventory::where('print_artwork_id', $design->print_artwork_id)->where('side', $side)->first();
            if ($film) {
                $film->increment('used_quantity');
                $filmCost += $film->cost_per_print;
            }
        }

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