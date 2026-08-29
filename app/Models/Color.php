<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    // Point directly to your migration's table
    protected $table = 'shirt_colors';

    protected $fillable = ['name'];
}