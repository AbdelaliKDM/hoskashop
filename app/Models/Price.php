<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_type_id',
        'unit_price',
        'pack_price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
