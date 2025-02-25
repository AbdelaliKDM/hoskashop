<?php

namespace App\Models;

use Session;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\SoftDeletes;
use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory, SoftDeletes, SoftCascadeTrait;

    protected $fillable = [
        //'subcategory_id',
        'unit_id',
        'unit_name',
        'pack_name',
        'image',
        'pack_units',
        'unit_type',
        'status',
        'description'
    ];

    protected $casts = [
        //'subcategory_id' => 'integer',
        // 'unit_price' => 'double',
        // 'pack_price' => 'double',
        'pack_units' => 'integer',
        'unit_type' => 'integer',
    ];

    protected $softCascade = ['discounts'];

    public function getImageAttribute($value)
    {
        return $value && Storage::disk('upload')->exists($value)
            ? Storage::disk('upload')->url($value)
            : null;
    }

    /* public function subcategory(){
      return $this->belongsTo(Subcategory::class);
    } */

    public function product_subcategories()
    {
        return $this->hasMany(ProductSubcategory::class);
    }

    public function getPrice()
    {
        $user = auth()->user();
        return $this->prices()->where('user_type_id', $user->user_type_id)->first();
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

    public function subcategories()
    {
        return $this->belongsToMany(Subcategory::class, ProductSubcategory::class);
    }

    public function subcategory_ids()
    {
        return $this->subcategories()->pluck('subcategories.id');
    }

    public function category_ids()
    {
        return $this->subcategories()->distinct('category_id')->pluck('category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function videos()
    {
        return $this->hasMany(ProductVideo::class);
    }

    /* public function category(){
      return $this->subcategory->category;
    } */

    public function discounts()
    {
        return $this->hasMany(Discount::class);
    }

    public function ads()
    {
        return $this->hasManyThrough(Ad::class, ProductAd::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function users_to_remind()
    {
        return $this->hasManyThrough(User::class, Reminder::class, 'product_id', 'id', 'id', 'user_id');
    }

    public function discount()
    {
        return $this->discounts()->where([
            ['start_date', '<=', now()],
            ['end_date', '>=', now()]
        ])->first();
    }

    public function has_pack()
    {
        $price = $this->getPrice();
        if (empty($this->pack_name) || empty($price->pack_price) || empty($this->pack_units)) {
            return false;
        }

        return true;
    }

    public function in_cart()
    {
        $user = auth()->user();

        $item = Item::whereHas('cart', function ($query) use ($user) {
            $query->where('user_id', $user?->id)
                  ->where('type', 'current');
        })->where('product_id', $this->id)->first();

        return $item?->quantity ?? 0;
    }

    public function add_to_cart($cart_id, $quantity, $discount)
    {
        // Correction: Removed the extra $this-> prefix before getPrice()
        $price = $this->getPrice();

        if ($this->has_pack() && $quantity >= $this->pack_units) {
            $pack_quantity = intdiv($quantity, $this->pack_units);
            $amount = $pack_quantity * ($price->pack_price * (1 - ($discount / 100)));

            Item::create([
                'cart_id'    => $cart_id,
                'product_id' => $this->id,
                'unit_name'  => $this->unit_name,
                'pack_name'  => $this->pack_name,
                'unit_price' => $price->unit_price,
                'pack_price' => $price->pack_price,
                'pack_units' => $this->pack_units,
                'type'       => 'pack',
                'quantity'   => $pack_quantity,
                'discount'   => $discount,
                'amount'     => $amount
            ]);

            $quantity = $quantity % $this->pack_units;
        }
        if ($quantity > 0) {
            $amount = $quantity * ($price->unit_price * (1 - ($discount / 100)));
            Item::create([
                'cart_id'    => $cart_id,
                'product_id' => $this->id,
                'unit_name'  => $this->unit_name,
                'pack_name'  => $this->pack_name,
                'unit_price' => $price->unit_price,
                'pack_price' => $price->pack_price,
                'pack_units' => $this->pack_units,
                'type'       => 'unit',
                'quantity'   => $quantity,
                'discount'   => $discount,
                'amount'     => $amount
            ]);
        }
    }

    public function notify($status)
    {
        if ($status == 'available' && $this->reminders()->count()) {
            $notice = Notice::ProductNotice($this->id, $this->unit_name, $this->image, $status);
            $users = $this->users_to_remind();

            Notification::send($notice, $users);

            $this->reminders()->delete();
        }
    }


    public function getPriceaverage()
    {
        $minPrice = $this->prices()->min('unit_price');
        $maxPrice = $this->prices()->max('unit_price');
        $minPrice =number_format($minPrice, 2, '.', ',');
        $maxPrice = number_format($maxPrice, 2, '.', ',');
        return  $minPrice." - ".$maxPrice;
    }
}
