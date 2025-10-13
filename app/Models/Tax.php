<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'country',
        'rate',
        'included_in_price',
        'applies_to',
        'currency',
        'active',
    ];

    protected $casts = [
        'rate' => 'decimal:4',
        'included_in_price' => 'boolean',
        'active' => 'boolean',
    ];
}
