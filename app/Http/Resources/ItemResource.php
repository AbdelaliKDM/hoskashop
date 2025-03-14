<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
      $product = $this->product()->withTrashed()->first();
      //$subcategory = is_null($product) ? null : $product->subcategory()->withTrashed()->first();

      if($this->cart->type == 'current'){
        $discount = is_null($product) ? 0 : (is_null($product->discount()) ? 0 : $product->discount()->amount);
      }else{
        $discount = $this->discount;
      }
        return [
          'product_id' => $this->product_id,
          //'subcategory_id' => is_null($product) ? null : $product->subcategory_id,
          //'category_id' => is_null($subcategory) ? null : $subcategory->category_id,
          'category_ids' => $product->category_ids(),
          'subcategory_ids' => $product->subcategory_ids(),
          'unit_name' => empty($this->unit_name) ? $product->unit_name : $this->unit_name ,
          'pack_name' => empty($this->pack_name) ? $product->pack_name : $this->pack_name ,
          'unit_price' => empty($this->unit_price) ? $product->getPrice()->unit_price : $this->unit_price,
          'pack_price' => empty($this->pack_price) ? $product->getPrice()->pack_price : $this->pack_price,
          'pack_units' => empty($this->pack_units) ? $product->pack_units : $this->pack_units ,
          'unit_id' => is_null($product) ? null : $product->unit_id,
          'unit_type' => is_null($product) ? null : $product->unit?->name($request->header('Accept-Language','ar')),
          'discount_amount' => $discount ,
          'status' => $product?->status,
          'image' => asset($product?->image),
          'quantity' => $this->quantity
        ];
    }
}
