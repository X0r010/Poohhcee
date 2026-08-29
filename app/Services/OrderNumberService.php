<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\OrderSequence;
use Illuminate\Support\Facades\DB;

/**
 * Generates order numbers atomically from a per-collection counter row,
 * instead of parsing the previous order's number as a string.
 *
 * This approach: SELECT ... FOR UPDATE on a dedicated counter row inside a
 * transaction, so concurrent requests queue instead of colliding.
 */
class OrderNumberService
{
    public function next(Collection $collection): string
    {
        return DB::transaction(function () use ($collection) {
            $sequence = OrderSequence::lockForUpdate()
                ->firstOrCreate(['collection_id' => $collection->id], ['next_number' => 1]);

            $number = $sequence->next_number;
            $sequence->increment('next_number');

            // Takes the short_code (or collection name if empty) and strictly trims it to 3 uppercase letters
            $rawPrefix = !empty($collection->short_code) ? $collection->short_code : $collection->name;
            $prefix = strtoupper(substr($rawPrefix, 0, 3));

            return $prefix . '-' . str_pad($number, 3, '0', STR_PAD_LEFT);
        });
    }
}