<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Price;
use App\Models\SubCategory;
use App\Models\Category;
use App\Models\Unit;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ProductsImport implements ToModel, WithHeadingRow
{
    protected $userTypes;

    /**
     * Pass the collection of user types into the import.
     *
     * @param \Illuminate\Support\Collection $userTypes
     */
    public function __construct($userTypes)
    {
        $this->userTypes = $userTypes;
    }

    /**
     * Map an Excel row to a Product and insert related Price rows.
     *
     * @param array $row
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        $unitName = trim($row["name"] ?? $row["الاسم"] ?? '');

        if (empty($unitName)) {
            return null;
        }
        $unitTypeLabel = trim($row["type"] ?? $row["نوع"] ?? '');

        $unitId = null;
        if (!empty($unitTypeLabel)) {
            $unit = Unit::where('name_en', $unitTypeLabel)
                        ->orWhere('name_ar', $unitTypeLabel)
                        ->first();
            $unitId = $unit ? $unit->id : null;
        }

        // Get status
        $statusLabel = trim($row["status"] ?? $row["الحالة"] ?? '');
        $status = 1;
        if ($statusLabel == __('Unavailable')) {
            $status = 0;
        }
        $data = [
            'unit_name'   => $unitName,
            'unit_id'     => $unitId,
            'pack_name'   => trim($row["pack"] ?? $row["الحزمة"] ?? ''),
            'pack_units'  => trim($row["quantity"] ?? $row["الكمية"] ?? ''),
            'description' => trim($row["description"] ?? $row["الوصف"] ?? ''),
            'status'      => $status,
        ];

        $product = Product::create($data);
        $categoryName = trim($row["category"] ?? $row["الفئة"] ?? '');
        $subcategoryName = trim($row["subcategory"] ?? $row["الفئة المحددة"] ?? '');

        if (!empty($subcategoryName)) {
            $subcategory = SubCategory::where('name', $subcategoryName)
                                    ->first();

            if ($subcategory) {
                $product->subcategories()->attach($subcategory->id);
            } else if (!empty($categoryName)) {
                $category = Category::where('name', $categoryName)
                                  ->first();

                if ($category) {
                    $newSubcategory = SubCategory::create([
                        'name' => $subcategoryName,
                        'category_id' => $category->id,
                        'status' => 1
                    ]);

                    $product->subcategories()->attach($newSubcategory->id);
                }
            }
        }
        foreach ($this->userTypes as $ut) {
            $unitPrice = null;
            $packPrice = null;
            $headerUnitPrice1 = 'price '.$ut->name_en;
            $headerPackPrice1 = 'pack price '.$ut->name_en;
            $headerUnitPrice2 = 'price_'.$ut->name_en;
            $headerPackPrice2 = 'pack_price_'.$ut->name_en;
            $headerUnitPrice3 = 'price_'.$this->normalizeKey($ut->name_en);
            $headerPackPrice3 = 'pack_price_'.$this->normalizeKey($ut->name_en);
            if (isset($row[$headerUnitPrice1])) {
                $unitPrice = $row[$headerUnitPrice1];
            } elseif (isset($row[$headerUnitPrice2])) {
                $unitPrice = $row[$headerUnitPrice2];
            } elseif (isset($row[$headerUnitPrice3])) {
                $unitPrice = $row[$headerUnitPrice3];
            } else {
                foreach ($row as $key => $value) {
                    if (stripos($key, 'price') !== false &&
                        (stripos($key, $ut->name_en) !== false || stripos($key, $ut->name_ar) !== false) &&
                        stripos($key, 'pack') === false) {
                        $unitPrice = $value;
                        break;
                    }
                }
            }
            if (isset($row[$headerPackPrice1])) {
                $packPrice = $row[$headerPackPrice1];
            } elseif (isset($row[$headerPackPrice2])) {
                $packPrice = $row[$headerPackPrice2];
            } elseif (isset($row[$headerPackPrice3])) {
                $packPrice = $row[$headerPackPrice3];
            } else {

                foreach ($row as $key => $value) {
                    if (stripos($key, 'price') !== false &&
                        (stripos($key, $ut->name_en) !== false || stripos($key, $ut->name_ar) !== false) &&
                        stripos($key, 'pack') !== false) {
                        $packPrice = $value;
                        break;
                    }
                }
            }
            Price::create([
                'product_id'   => $product->id,
                'user_type_id' => $ut->id,
                'unit_price'   => $unitPrice,
                'pack_price'   => $packPrice,
            ]);
        }

        return $product;
    }

    private function normalizeKey($key)
    {
        $normalized = str_replace(' ', '_', $key);
        $normalized = preg_replace('/[^\p{L}\p{N}_]/u', '', $normalized);
        $normalized = strtolower($normalized);

        return $normalized;
    }
}
