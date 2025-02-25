<?php

namespace App\Http\Controllers;

use App\Models\Price;
use App\Models\UserType;
use Session;
use Exception;
use App\Models\Unit;
use App\Models\Product;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductCollection;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\PaginatedProductCollection;

class ProductController extends Controller
{

  public function index()
  {
    $categories = Category::all();
    $units = Unit::all();
    $userTypes = UserType::all();
    return view('content.products.list')
      ->with('categories', $categories)
      ->with('units', $units)
      ->with('userTypes', $userTypes);
  }
  public function create(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'subcategory_ids' => 'required|array',
      'unit_id' => 'required|exists:units,id',
      'unit_name' => 'required|string',
      'pack_name' => 'sometimes|string',
      'image' => 'sometimes|mimetypes:image/*',
      'pack_units' => 'required_with:pack_price|nullable|integer',
      'status' => 'required|in:1,2',
      'description' => 'sometimes|nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'message' => $validator->errors()->first()
      ]);
    }

    try {
      $data = $request->except('image');
      foreach ($data as $key => $value) {
        if (preg_match('/^(unit_price_|pack_price_)/', $key)) {
          unset($data[$key]);
        }
      }

      $product = Product::create($data);
      if ($request->hasFile('image')) {
        $url = $request->image->store('/uploads/products/images', 'upload');
        $product->image = $url;
        $product->save();
      }
      if ($request->has('subcategory_ids')) {
        $product->subcategories()->sync($request->subcategory_ids);
      }
      foreach ($request->all() as $key => $value) {
        if (strpos($key, 'unit_price_') === 0) {
          $userTypeId = str_replace('unit_price_', '', $key);
          $unitPrice = $value;
          $packPriceKey = 'pack_price_' . $userTypeId;
          $packPrice = $request->has($packPriceKey) ? $request->get($packPriceKey) : null;

          Price::create([
            'product_id' => $product->id,
            'user_type_id' => $userTypeId,
            'unit_price' => $unitPrice,
            'pack_price' => $packPrice
          ]);
        }
      }

      return response()->json([
        'status' => 1,
        'message' => 'success',
        'data' => new ProductResource($product)
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'status' => 0,
        'message' => $e->getMessage()
      ]);
    }
  }


  public function update(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'product_id' => 'required|exists:products,id',
      'subcategory_ids' => 'sometimes|array',
      'unit_id' => 'sometimes|exists:units,id',
      'unit_name' => 'sometimes|string',
      'pack_name' => 'sometimes|string',
      'image' => 'sometimes|mimetypes:image/*',
      'pack_units' => 'required_with:pack_price|nullable|integer',
      'status' => 'sometimes|in:1,2',
      'description' => 'sometimes|nullable|string',
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 0,
        'message' => $validator->errors()->first()
      ]);
    }

    try {
      $product = Product::findOrFail($request->product_id);

      $data = $request->except('image', 'product_id');
      foreach ($data as $key => $value) {
        if (preg_match('/^(unit_price_|pack_price_)/', $key)) {
          unset($data[$key]);
        }
      }
      $product->update($data);

      if ($request->hasFile('image')) {
        $url = $request->image->store('/uploads/products/images', 'upload');
        $product->image = $url;
        $product->save();
      }

      if ($request->has('subcategory_ids')) {
        $product->subcategories()->sync($request->subcategory_ids);
      }

      if ($request->has('status')) {
        $product->notify($request->status == '1' ? 'available' : 'unavailable');
      }

      // Loop through all request inputs to update/create the price records.
      foreach ($request->all() as $key => $value) {
        if (strpos($key, 'unit_price_') === 0) {
          $userTypeId = str_replace('unit_price_', '', $key);
          $unitPrice = $value;
          $packPriceKey = 'pack_price_' . $userTypeId;
          $packPrice = $request->has($packPriceKey) ? $request->get($packPriceKey) : null;

          // Update or create a Price record for this product/user type.
          Price::updateOrCreate(
            [
              'product_id' => $product->id,
              'user_type_id' => $userTypeId,
            ],
            [
            'unit_price' => $unitPrice,
              'pack_price' => $packPrice
            ]
          );
        }
      }

      return response()->json([
        'status' => 1,
        'message' => 'success',
        'data' => new ProductResource($product)
      ]);

    } catch (\Exception $e) {
      return response()->json([
        'status' => 0,
        'message' => $e->getMessage()
      ]);
    }
  }

  public function delete(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'product_id' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(
        [
          'status' => 0,
          'message' => $validator->errors()->first()
        ]
      );
    }

    try {

      $product = Product::findOrFail($request->product_id);

      $product->delete();

      return response()->json([
        'status' => 1,
        'message' => 'success',
      ]);

    } catch (Exception $e) {
      return response()->json(
        [
          'status' => 0,
          'message' => $e->getMessage()
        ]
      );
    }

  }

  public function restore(Request $request)
  {

    $validator = Validator::make($request->all(), [
      'product_id' => 'required',
    ]);

    if ($validator->fails()) {
      return response()->json(
        [
          'status' => 0,
          'message' => $validator->errors()->first()
        ]
      );
    }

    try {

      $product = Product::withTrashed()->findOrFail($request->product_id);

      $product->restore();

      return response()->json([
        'status' => 1,
        'message' => 'success',
        'data' => new ProductResource($product)
      ]);

    } catch (Exception $e) {
      return response()->json(
        [
          'status' => 0,
          'message' => $e->getMessage()
        ]
      );
    }

  }

  public function get(Request $request)
  {  //paginated
    $validator = Validator::make($request->all(), [
      'category_id' => 'sometimes|missing_with:subcategory_id|exists:categories,id',
      'subcategory_id' => 'sometimes|exists:subcategories,id',
      'search' => 'sometimes|string',

    ]);

    if ($validator->fails()) {
      return response()->json(
        [
          'status' => 0,
          'message' => $validator->errors()->first()
        ]
      );
    }

    try {

      $products = Product::orderBy('created_at', 'DESC');

      if ($request->has('category_id')) {

        $products = $products->whereHas('subcategories', function ($query) use ($request) {
          $query->where('category_id', $request->category_id);
        });
      }

      if ($request->has('subcategory_id')) {

        $products = $products->whereHas('subcategories', function ($query) use ($request) {
          $query->where('subcategories.id', $request->subcategory_id);
        });
      }

      if ($request->has('search')) {

        $products = $products->where('unit_name', 'like', '%' . $request->search . '%');
        //->orWhere('pack_name', 'like', '%' . $request->search . '%');
      }

      if ($request->has('all')) {
        $products = $products->get();
        return response()->json([
          'status' => 1,
          'message' => 'success',
          'data' => $products
        ]);

      }
      $products = $products->paginate(10);


      return response()->json([
        'status' => 1,
        'message' => 'success',
        'data' => new PaginatedProductCollection($products)
      ]);

    } catch (Exception $e) {
      return response()->json(
        [
          'status' => 0,
          'message' => $e->getMessage()
        ]
      );
    }

  }
}
