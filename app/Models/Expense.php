<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = ['expense_date', 'category', 'title', 'description', 'amount', 'reference'];

    protected $casts = ['expense_date' => 'date'];

    public static function categories(): array
    {
        return [
            'Shirts',
            'DTF Film',
            'Packaging',
            'Personal',
            'Delivery',
            'Marketing',
            'Equipment',
            'Supplies',
            'Gas',
            'Other',
        ];
    }
}