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

class LanaDelReyImportSeeder extends Seeder
{
    private const FILM_COST_PER_SIDE = 0.50;
    private const SHIRT_UNIT_COST = 5.00;

    public function run(): void
    {
        $collectionName = 'Lana Del Rey';

        $rows = [
            ['Date' => '30.07.26', 'Customer' => '@Channchivita Chourng', 'Item' => 'Lust for life', 'Size' => 'S', 'Color' => 'White', 'Payment' => 'Paid (ABA)', 'Shirt Status' => 'Done', 'Print' => 'Done', 'Status' => 'Delivered', 'From' => 'FB', 'Number' => '', 'Location' => '', 'Base Price' => '12$', 'Delivery Fee' => '$2.00', 'Total ($)' => '$14.00'],
            ['Date' => '30.07.26', 'Customer' => '@Sokunthea Sreynoch', 'Item' => 'Lust for life', 'Size' => 'M', 'Color' => 'White', 'Payment' => 'Paid (ABA)', 'Shirt Status' => 'Done', 'Print' => 'Done', 'Status' => 'Delivered', 'From' => 'FB', 'Number' => '', 'Location' => '', 'Base Price' => '12$', 'Delivery Fee' => '$0.00', 'Total ($)' => '$12.00'],
            ['Date' => '30.07.26', 'Customer' => '@Mean', 'Item' => 'Lust for life', 'Size' => 'S', 'Color' => 'White', 'Payment' => 'Paid (ABA)', 'Shirt Status' => 'Done', 'Print' => 'Done', 'Status' => 'Delivered', 'From' => 'Tik Tok', 'Number' => '', 'Location' => '', 'Base Price' => '12$', 'Delivery Fee' => '$0.00', 'Total ($)' => '$12.00'],
            ['Date' => '30.07.26', 'Customer' => '@Ethan', 'Item' => 'Lust for life', 'Size' => 'S', 'Color' => 'White', 'Payment' => 'Paid (ABA)', 'Shirt Status' => 'Done', 'Print' => 'Done', 'Status' => 'Delivered', 'From' => 'IG', 'Number' => '', 'Location' => '', 'Base Price' => '12$', 'Delivery Fee' => '$2.00', 'Total ($)' => '$14.00'],
            ['Date' => '30.07.26', 'Customer' => '@Ethan', 'Item' => 'Honeymoon', 'Size' => 'S', 'Color' => 'White', 'Payment' => 'Paid (ABA)', 'Shirt Status' => 'Done', 'Print' => 'Done', 'Status' => 'Delivered', 'From' => 'IG', 'Number' => '', 'Location' => '', 'Base Price' => '12$', 'Delivery Fee' => '$0.00', 'Total ($)' => '$12.00'],
            ['Date' => '30.07.26', 'Customer' => '@Ethan', 'Item' => 'Norman Fucking Rockwell', 'Size' => 'S', 'Color' => 'White', 'Payment' => 'Paid (ABA)', 'Shirt Status' => 'Done', 'Print' => 'Done', 'Status' => 'Delivered', 'From' => 'IG', 'Number' => '', 'Location' => '', 'Base Price' => '12$', 'Delivery Fee' => '$0.00', 'Total ($)' => '$12.00'],
            ['Date' => '03.08.26', 'Customer' => '@Channchivita Chourng', 'Item' => 'Lust for life', 'Size' => 'S', 'Color' => 'White', 'Payment' => 'Paid (ABA)', 'Shirt Status' => 'Done', 'Print' => 'Done', 'Status' => 'Delivered', 'From' => 'Tik Tok', 'Number' => '', 'Location' => '', 'Base Price' => '12$', 'Delivery Fee' => '$0.00', 'Total ($)' => '$12.00'],
            ['Date' => '05.08.26', 'Customer' => "@Sopha's", 'Item' => 'Norman Fucking Rockwell', 'Size' => 'L', 'Color' => 'White', 'Payment' => 'Not yet', 'Shirt Status' => 'Not yet', 'Print' => 'Done', 'Status' => 'Pending', 'From' => 'Tik Tok', 'Number' => '', 'Location' => '', 'Base Price' => '', 'Delivery Fee' => '', 'Total ($)' => ''],
        ];

        DB::transaction(function () use ($rows, $collectionName) {
            // 1. Setup Collection
            $collection = Collection::firstOrCreate(
                ['name' => $collectionName],
                ['slug' => Str::slug($collectionName), 'short_code' => 'LANA', 'active' => true]
            );

            // 2. Clear old collection orders & reset sequence
            $designIds = Design::where('collection_id', $collection->id)->pluck('id');
            if ($designIds->isNotEmpty()) {
                Order::whereIn('design_id', $designIds)->delete();
            }

            OrderSequence::updateOrCreate(
                ['collection_id' => $collection->id],
                ['next_number' => 1]
            );

            // 3. Base shirt type
            $shirtType = ShirtType::firstOrCreate(['name' => 'Boxy Tee']);

            $designCache = [];
            $artworkCache = [];

            // ── Pass 1: Artworks, Designs, Films, Blank Shirts ──
            foreach ($rows as $row) {
                $item = Str::title(trim($row['Item']));
                $color = ucfirst(strtolower(trim($row['Color'])));
                $size = strtoupper(trim($row['Size']));

                if (!isset($artworkCache[$item])) {
                    $artwork = PrintArtwork::firstOrCreate(
                        ['collection_id' => $collection->id, 'name' => $item],
                        ['has_front' => true, 'has_back' => true]
                    );
                    $artworkCache[$item] = $artwork;
                }
                $artwork = $artworkCache[$item];

                if (!isset($designCache[$item])) {
                    $design = Design::firstOrCreate(
                        ['collection_id' => $collection->id, 'name' => $item],
                        ['active' => true, 'print_artwork_id' => $artwork->id]
                    );
                    if (!$design->print_artwork_id) {
                        $design->update(['print_artwork_id' => $artwork->id]);
                    }
                    $designCache[$item] = $design;
                }
                $design = $designCache[$item];

                FilmInventory::firstOrCreate(
                    ['print_artwork_id' => $artwork->id, 'side' => 'front', 'shirt_color' => null],
                    [
                        'design_id'         => $design->id,
                        'prints_available'  => 0,
                        'reserved_quantity' => 0,
                        'used_quantity'     => 0,
                        'cost_per_print'    => self::FILM_COST_PER_SIDE,
                    ]
                );

                ShirtInventory::firstOrCreate(
                    ['type' => $shirtType->name, 'size' => $size, 'color' => $color],
                    ['quantity' => 0, 'reserved_quantity' => 0, 'used_quantity' => 0, 'printed_available' => 0, 'cost_per_unit' => self::SHIRT_UNIT_COST]
                );
            }

            // ── Pass 2: Insert Orders ──
            foreach ($rows as $row) {
                $item = Str::title(trim($row['Item']));
                $design = $designCache[$item];
                $this->createOrder($row, $collection, $design, $shirtType);
            }
        });

        $this->command->info('Imported ' . count($rows) . ' orders into ' . $collectionName);
    }

    private function createOrder(array $row, Collection $collection, Design $design, ShirtType $shirtType): void
    {
        $size = strtoupper(trim($row['Size']));
        $color = ucfirst(strtolower(trim($row['Color'])));
        $filmCost = self::FILM_COST_PER_SIDE;

        [$paymentStatus, $paymentMethod] = $this->parsePayment($row['Payment'] ?? '');
        $deliveryStatus = $this->mapDeliveryStatus($row['Status'] ?? '');
        $source = $this->mapSource($row['From'] ?? '');
        $orderDate = $this->parseDate($row['Date'] ?? '');

        $basePrice = $this->parseMoney($row['Base Price'] ?? '');
        $deliveryFee = $this->parseMoney($row['Delivery Fee'] ?? '');
        $total = $this->parseMoney($row['Total ($)'] ?? '');

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
            'shirt_status'       => (strtolower(trim($row['Shirt Status'] ?? '')) === 'not yet') ? 'Not yet' : 'Done',
            'film_status'        => 'Done',
            'print_status'       => 'Done',
            'delivery_status'    => $deliveryStatus,
            'readiness'          => 'printed',
            'notes'              => 'Imported from Lana Del Rey list',
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