<?php

namespace App\Http\Resources;

use Chargily\ChargilyPay\Api\Prices;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $discount = $this->discount();

        $images = $this->images()->get('path')->pluck('path')->toArray();
        $videos = $this->videos()->get('path')->pluck('path')->toArray();
        $prices = $this->prices()->get();

        $this->image ? array_unshift($images,$this->image) : $images;

        $images = array_map(function($image) {
          return asset($image);
        }, $images);

        return [
          'id' => $this->id,
          //'subcategory_id' => $this->subcategory_id,
          //'category_id' => $this->subcategory->category_id,
          'category_ids' => $this->category_ids(),
          'subcategory_ids' => $this->subcategory_ids(),
          'unit_name' => $this->unit_name,
          'pack_name' => $this->pack_name,
          'unit_price' => $this->getPrice()?->unit_price ?? 0,
          'pack_price' => $this->getPrice()?->pack_price ?? 0,
          'pack_units' => $this->pack_units,
          'unit_id' => $this->unit_id,
          'unit_type' => $this->unit?->name($request->header('Accept-Language','ar')),
          'status' => $this->status,
          'image' => asset($this->image),
          'is_discounted' => is_null($discount) ? false : true,
          'discount_amount' => is_null($discount) ? 0 : $discount->amount,
          'start_date' => is_null($discount) ? null : $discount->start_date,
          'end_date' => is_null($discount) ? null : $discount->end_date,
          'in_cart' => empty($this->in_cart()) ? false : true,
          'quantity' => $this->in_cart(),
          'description' => $this->description,
          'images' => $images,
          'videos' => $videos,
          'prices' => $prices,
        ];
    }
}
