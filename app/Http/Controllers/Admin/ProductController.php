<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\{User, Customer, Application, ApplicationService, CurrencyPricelist,  ProductMain, ProductsEntry,Service,IndexG2,IndexS2, ProQtyMaxDiscountPercentage,  Currency, WarrantyMaster, ProductTypeClassMaster};
use Carbon\Carbon;

class ProductController extends Controller
{

public function index(Request $request)
{
    try {
        // Pagination Inputs
        $page    = (int) $request->input('page', 1);
        $perPage = (int) $request->input('record', 10);
        $priceType = $request->input('price_type_id'); // For dynamic price type filter

        /** -----------------------------
         * Build Query with Relations
         * ----------------------------- */
        $query = ProductMain::active()->with(['pricing', 'discounts', 'category', 'typeClass']);

        /** -----------------------------
         * Apply Filters
         * ----------------------------- */
        if ($request->filled('category_id')) {
            $query->where('cate_id', $request->category_id);
        }
 
        if ($request->filled('type_class_id')) {
            $query->where('product_type_class_id', $request->type_class_id);
        }

        if ($request->filled('name_or_upc')) {
            $query->where(function ($q) use ($request) {
                $q->where('upc_code', 'like', '%' . $request->name_or_upc . '%')
                  ->orWhere('pro_title', 'like', '%' . $request->name_or_upc . '%');
            });
        }

        if ($request->has('hot')) {
            $query->where('hot_product', $request->hot);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        /** -----------------------------
         * Sorting Logic
         * ----------------------------- */
        $sortBy = $request->input('sort_by', 'latest'); // default to latest
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('pro_id', 'asc');
                break;
            case 'price_high':
                $query->with(['pricing' => function ($q) {
                    $q->orderBy('pro_price_entry', 'desc');
                }]);
                break;
            case 'price_low':
                $query->with(['pricing' => function ($q) {
                    $q->orderBy('pro_price_entry', 'asc');
                }]);
                break;
            default:
                $query->orderBy('pro_id', 'desc'); // latest
                break;
        }

        /** -----------------------------
         * Pagination
         * ----------------------------- */
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);

        /** -----------------------------
         * Transform Data
         * ----------------------------- */
        $response = $paginated->getCollection()->map(function ($product) use ($priceType) {
            $pricing = $priceType 
                ? $product->pricing->where('price_list_type_id', $priceType)->first()
                : $product->pricing->first();

            return [
                'id'                      => $product->pro_id,
                'name'                    => $product->pro_title,
                'upc_code'                => $product->upc_code,
                'category_id'             => $product->cate_id,
                'type_class_id'           => $product->product_type_class_id,
                'category_name'           => $product->category->application_name ?? null,
                'type_class_name'         => $product->typeClass->product_type_class_name ?? null,
                'hot'                     => $product->hot_product,
                'status'                  => $product->status,
                'reorder_stock_level'     => $product->ware_house_stock,
                'max_discount_percentage' => $product->pro_max_discount,
                'price_list_type_id'      => $pricing->price_list_type_id ?? null,
                'price'                   => $pricing->pro_price_entry ?? null,
                'description'             => $pricing->pro_desc_entry ?? null,
                'updated_at'              => $pricing->updated_at ?? null,
                'hsn_code'                => $pricing->hsn_code ?? null,
            ];
        });

        /** -----------------------------
         * Response
         * ----------------------------- */
        return response()->json([
            'status'     => 'success',
            'message'    => 'Products listed successfully.',
            'data'       => $response,
            'pagination' => [
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}





 public function edit(Request $request)
{
        $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_products,pro_id'
    ]);
 if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }
    try {
        $product = ProductMain::with(['pricing', 'discounts', 'category', 'typeClass'])
            ->where('pro_id', $request->id)
            ->firstOrFail();

        $response = [
            'id'                     => $product->pro_id,
            'name'                   => $product->pro_title,
            'upc_code'               => $product->upc_code,
            'category_id'            => $product->cate_id,
    'category_name'           => $product->category->application_name ?? null,
 'type_class_name'         => $product->typeClass->product_type_class_name ?? null,
            'type_class_id'          => $product->product_type_class_id,
            'hot'                    => $product->hot_product,
            'status'                 => $product->status,
            'moq'                    => $product->admin_moq,
            'reorder_stock_level'    => $product->ware_house_stock,
            'warranty_id'            => $product->pro_warranty,
            'image'                  => $product->pro_image ? asset($product->pro_image) : null,

            'pricing' => $product->pricing->map(function ($price) {
                return [
                    'price_list_type_id' => $price->price_list_type_id,
                    'hsn_code'           => $price->hsn_code,
                    'item_code'          => $price->model_no,
                    'description'        => $price->pro_desc_entry,
                    'price'              => $price->pro_price_entry,
                    'currency'           => $price->currency,
                    'updated_at'         => $price->updated_at,
                ];
            }),

            'max_discount_percentage' => $product->pro_max_discount,
            'quantity_slab' => $product->qty_slab,

            'discounts' => $product->discounts->map(function ($discount) {
                return [
                    'quantity_slab_from'  => $discount->min_qty,
                    'quantity_slab_to'    => $discount->max_qty,
                    'discount_percentage' => $discount->max_discount_percent,
                ];
            }),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => $response
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}


public function storeOrUpdate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id'                       => 'nullable|exists:tbl_products,pro_id',
        'name'                     => 'required|string|max:200',
        'category_id'              => 'required|exists:tbl_application,application_id',
        'type_class_id'            => 'required|exists:tbl_product_type_class_master,product_type_class_id',
        'hot'                      => 'in:0,1',
        'status'                   => 'in:active,inactive',

        'moq'                      => 'required|integer|min:1',
        'reorder_stock_level'      => 'required|integer|min:1',
        'warranty_id'              => 'required|exists:tbl_warranty_master,warranty_id',

        'pricing'                  => 'required|array|min:1',
        'pricing.*.price_list_type_id' => 'required|exists:tbl_currency_pricelist,pricelist_id',
        'pricing.*.hsn_code'       => 'required|string',
        'pricing.*.item_code'      => 'required|string',
        'pricing.*.description'    => 'required|string',
        'pricing.*.price'          => 'required|numeric',
        'pricing.*.currency'       => 'required|string',

        'max_discount_percentage'  => 'required|numeric',
        'quantity_slab'  => 'required|in:Yes,No',
        'discounts'                => 'required|array',
        'discounts.*.quantity_slab_from' => 'required|integer',
        'discounts.*.quantity_slab_to'   => 'required|integer',
        'discounts.*.discount_percentage'   => 'required|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $data = [];

        // Handle product image if provided
     if ($request->filled('image') && str_starts_with($request->image, 'data:image/')) {
  
        $data['pro_image'] = $this->processBase64Image($request->image);
   }


        // Create or update the product
 $dataToSave = array_merge($data, [
    'pro_title'             => $request->name,
    'cate_id'               => $request->category_id,
    'product_type_class_id' => $request->type_class_id,
    'hot_product'           => $request->hot,
    'status'                => $request->status,
    'admin_moq'             => $request->moq,
    'ware_house_stock'      => $request->reorder_stock_level,
    'pro_warranty'          => $request->warranty_id,
    'qty_slab'              => $request->quantity_slab,
    'pro_max_discount'      => $request->max_discount_percentage ?? 0,
    'updated_at'            => now()
]);

// Add UPC only for create (when id is empty)
if (empty($request->id)) {
    $dataToSave['upc_code'] = upcCodeProduct();
}

$product = ProductMain::updateOrCreate(
    ['pro_id' => $request->id],
    $dataToSave
);

 
        // Remove old entries if updating
        if ($request->id) {
            ProductsEntry::where('pro_id', $request->id)->delete();
            ProQtyMaxDiscountPercentage::where('proid', $request->id)->delete();
        }

        insertCategoryProductID('product', $product->pro_id, $request->category_id);
        
        // Add product pricing entries
        foreach ($request->pricing as $priceData) {
            ProductsEntry::create([
                'pro_id'             => $product->pro_id,
                'app_cat_id'         => $request->category_id,
                'cate_id'            => $request->category_id,
                'price_list_type_id' => $priceData['price_list_type_id'] ?? null,
                'hsn_code'           => $priceData['hsn_code'] ?? null,
                'model_no'           => $priceData['item_code'] ?? null,
                'pro_desc_entry'     => $priceData['description'] ?? null,
                'pro_price_entry'    => $priceData['price'] ?? null,
                'currency'           => $priceData['currency'] ?? null,
                'price_list' =>'pvt',
                'sort_order' => 0,
           //   'last_modified' => Carbon::now(),

            ]);
        }

        // Add quantity discount entries
        if (!empty($request->discounts)) {
            foreach ($request->discounts as $discountData) {
                ProQtyMaxDiscountPercentage::create([
                    'proid'              => $product->pro_id,
                    'min_qty'            => $discountData['quantity_slab_from'] ?? null,
                    'max_qty'            => $discountData['quantity_slab_to'] ?? null,
                    'max_discount_percent' => $discountData['discount_percentage'] ?? null,
                ]);
            }
        }

        return response()->json([
            'status'  => 'success',
            'message' => $request->id ? 'Updated successfully.' : 'Created successfully.'
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

private function processBase64Image($base64Image)
{
    if (!preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
        throw new \Exception('Invalid image format.');
    }

    $extension = strtolower($type[1]);
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        throw new \Exception('Invalid image type.');
    }

    $base64Image = substr($base64Image, strpos($base64Image, ',') + 1);
    $base64Image = str_replace(' ', '+', $base64Image);
    $imageData = base64_decode($base64Image);

    if ($imageData === false) {
        throw new \Exception('Base64 decode failed.');
    }

    $imageName = time() . '_' . uniqid() . '.' . $extension;
    $imagePath = 'uploads/Product/image/';
    $fullPath  = public_path($imagePath);

    if (!file_exists($fullPath)) {
        mkdir($fullPath, 0755, true);
    }

    file_put_contents($fullPath . $imageName, $imageData);

    return $imagePath . $imageName;
}





    public function updateStatus(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_products,pro_id',
            'status' => 'required|in:active,inactive'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $product = ProductMain::where('pro_id', $request->id)->first();
        $product->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Status updated.']);
    }

public function destroy(Request $request)
{
    try {
        // Normalize input: allow both single ID and array
        $ids = $request->input('id');
        $ids = is_array($ids) ? $ids : (isset($ids) ? [$ids] : []);

        // Validate
        $validator = Validator::make(
            ['id' => $ids],
            [
                'id'   => 'required|array|min:1',
                'id.*' => 'exists:tbl_products,pro_id'
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $products = ProductMain::with(['pricing', 'discounts'])
            ->whereIn('pro_id', $ids)
            ->get();

        foreach ($products as $product) {
            $product->update(['deleteflag' => 'inactive']);

            if ($product->pricing) {
                $product->pricing()->update(['deleteflag' => 'inactive']);
            }
            // if ($product->discounts) {
            //     $product->discounts()->update(['deleteflag' => 'inactive']);
            // }
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Product(s) and related data soft deleted successfully.'
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => $e->getMessage(),
            'trace'   => $e->getTraceAsString() // <-- keep only while debugging
        ], 500);
    }
}

  public function updateHot(Request $request)
{
    // Normalize input: allow both single ID and array
    $ids = $request->input('id');
    $ids = is_array($ids) ? $ids : (isset($ids) ? [$ids] : []);

    // Validate against normalized array
    $validator = Validator::make(
        ['id' => $ids, 'hot' => $request->hot],
        [
            'id'   => 'required|array|min:1',
            'id.*' => 'exists:tbl_products,pro_id',
            'hot'  => 'required|in:0,1'
        ]
    );

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Update products
    ProductMain::whereIn('pro_id', $ids)
        ->update(['hot_product' => $request->hot]);

    return response()->json([
        'status'  => 'success',
        'message' => 'Hot flag updated for selected product(s).'
    ]);
}

    public  function insertCategoryIDProductService()
        {
           // Update categories for products
        $products = ProductMain::whereNull('cate_id')->get();
        foreach ($products as $product) {
            $category_id = IndexG2::where('match_pro_id_g2', $product->pro_id)
                ->value('pro_id'); // Using value() instead of pluck()->first()

            if ($category_id) {
                ProductMain::where('pro_id', $product->pro_id)
                    ->update(['cate_id' => $category_id]);
            }
        }

        // Update categories for services
        $services = Service::whereNull('cate_id')->get();
        foreach ($services as $service) {
            $category_id = IndexS2::where('match_service_id_s2', $service->service_id)
                ->value('service_id');

            if ($category_id) {
                Service::where('service_id', $service->service_id)
                    ->update(['cate_id' => $category_id]);
            }
        }
         return response()->json(['status' => 'success', 'message' => 'Successfully updated.']);
    }

}
  