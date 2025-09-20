<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\ProductService;
use App\Models\ProductServiceDetail;
use App\Models\ProductServiceDiscount;

class ProductServiceController extends Controller
{
public function index(Request $request)
{
    $validator = Validator::make($request->all(), [
        'type' => 'required|in:product,service',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    // Search filters
    $upc_usc     = $request->upc_usc;
    $name        = $request->name;
    $category_id = $request->category_id;
    $updated_at  = $request->price_last_updated;
    $hot         = $request->hot;
    $search_all  = $request->search_all;

    // Build query
    $query = ProductService::with([
        'details' => function ($q) {
            $q->select([
                'id',
                'product_service_id',
                'price_list_type',
                'hsn_sac_code',
                'item_code',
                'price',
                'details',
                'max_discount_percentage',
                'quantity_discount_tiers',
            ])->with(['discounts' => function ($qd) {
                $qd->select([
                    'id',
                    'product_service_id',
                    'product_service_details_id',
                    'quantity_slab_from',
                    'quantity_slab_to',
                    'max_discount_percentage',
                ]);
            }]);
        }
    ])
    ->select([
        'id',
        'upc_usc',
        'name',
        'category_id',
        'type_class_id',
        'hot',
        'image',
        'moq',
        'reorder_stock_level',
        'warranty_id',
        'product_id',
        'type',
    ])
    ->where('type', $request->type);

    // Apply individual filters
    if (!empty($upc_usc)) {
        $query->where('upc_usc', 'like', '%' . $upc_usc . '%');
    }
 
    if (!empty($name)) {
        $query->where('name', 'like', '%' . $name . '%');
    }

    if (!empty($category_id)) {
        $query->where('category_id', $category_id);
    }

    if (!is_null($hot)) {
        $query->where('hot', $hot);
    }

    if (!empty($updated_at)) {
        $query->whereHas('details', function ($q) use ($updated_at) {
            $q->whereDate('updated_at', $updated_at);
        });
    }

    // Global search: upc_usc, name, category name, updated_at, hot
    if (!empty($search_all)) {
        $query->where(function ($q) use ($search_all) {
            $q->where('upc_usc', 'like', '%' . $search_all . '%')
              ->orWhere('name', 'like', '%' . $search_all . '%')
              ->orWhereHas('category', function ($cat) use ($search_all) {
                  $cat->where('name', 'like', '%' . $search_all . '%');
              })
              ->orWhere('hot', 'like', '%' . $search_all . '%')
              ->orWhereHas('details', function ($det) use ($search_all) {
                  $det->whereDate('updated_at', 'like', '%' . $search_all . '%');
              });
        });
    }

    $products = $query->orderByDesc('id')->get();

    return response()->json([
        'status' => 'success',
        'data'   => $products
    ]);
}

    public function edit(Request $request)
    {
        $request->validate(['id' => 'required|exists:product_services,id']);

        $product = ProductService::with(['details.discounts'])->find($request->id);

        return response()->json([
            'status' => 'success',
            'data'   => $product
        ]);
    }

   public function storeOrUpdate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id'                     => 'nullable|exists:product_services,id',
        'upc_usc'                => 'required|string|max:100',
        'name'                   => 'required|string|max:200',
        'category_id'            => 'nullable|exists:categories,id',
        'type_class_id'          => 'nullable|exists:type_classes,id',
        'hot'                    => 'in:0,1',
        'image'                  => 'nullable|string',
        'moq'                    => 'required|integer|min:1',
        'reorder_stock_level'    => 'required|integer|min:1',
        'warranty_id'            => 'nullable|exists:warranties,id',
        'product_id'             => 'nullable|integer',
        'type'                   => 'required|in:product,service',
        'status'                 => 'in:active,inactive',

        'details'                => 'required|array',
        'details.*.price_list_type'         => 'nullable|string',
        'details.*.hsn_sac_code'            => 'nullable|string',
        'details.*.item_code'               => 'nullable|string',
        'details.*.price'                   => 'required|numeric',
        'details.*.details'                 => 'nullable|string',
        'details.*.max_discount_percentage' => 'nullable|numeric',
        'details.*.quantity_discount_tiers' => 'required|in:0,1',
        'details.*.discounts'               => 'array|nullable',
        'details.*.discounts.*.quantity_slab_from' => 'required|integer',
        'details.*.discounts.*.quantity_slab_to'   => 'required|integer',
        'details.*.discounts.*.max_discount_percentage' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    DB::beginTransaction();

    try {
        $data = $request->only([
            'upc_usc', 'name', 'category_id', 'type_class_id', 'hot',
            'moq', 'reorder_stock_level', 'warranty_id', 'product_id',
            'type', 'status'
        ]);

        // Handle base64 image if provided
        if (!empty($request->image)) {
            $data['image'] = $this->processBase64Image($request->image);
        }

        // Create or update ProductService
        $productService = ProductService::updateOrCreate(
            ['id' => $request->id],
            $data
        );

        // Clean up existing details & discounts if updating
        if ($request->id) {
            foreach ($productService->details as $detail) {
                $detail->discounts()->delete();
            }
            $productService->details()->delete();
        }

        // Create new details and nested discounts
        foreach ($request->details as $detailData) {
            $detail = $productService->details()->create([
                'price_list_type'         => $detailData['price_list_type'] ?? null,
                'hsn_sac_code'            => $detailData['hsn_sac_code'] ?? null,
                'item_code'               => $detailData['item_code'] ?? null,
                'price'                   => $detailData['price'],
                'details'                 => $detailData['details'] ?? null,
                'max_discount_percentage' => $detailData['max_discount_percentage'] ?? 0,
                'quantity_discount_tiers' => $detailData['quantity_discount_tiers'],
                'status'                  => 'active',
            ]);

            if (!empty($detailData['discounts'])) {
                foreach ($detailData['discounts'] as $discount) {
                    $detail->discounts()->create([
                        'product_service_id'        => $productService->id,
                        'quantity_slab_from'        => $discount['quantity_slab_from'],
                        'quantity_slab_to'          => $discount['quantity_slab_to'],
                        'max_discount_percentage'   => $discount['max_discount_percentage'],
                        'status'                    => 'active',
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json([
            'status'  => 'success',
            'message' => $request->id ? 'Updated successfully.' : 'Created successfully.'
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()], 500);
    }
}

private function processBase64Image($base64Image)
{
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
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
        $imagePath = 'uploads/ProductService/image/';
        $fullPath = public_path($imagePath);

        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        file_put_contents($fullPath . $imageName, $imageData);

        return $imagePath . $imageName;
    } else {
        throw new \Exception('Invalid image format.');
    }
}


    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required|exists:product_services,id']);

        $product = ProductService::find($request->id);
        $product->update(['deleted_at' => now()]);

        return response()->json(['status' => 'success', 'message' => 'Deleted successfully.']);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:product_services,id',
            'status' => 'required|in:active,inactive'
        ]);

        $product = ProductService::find($request->id);
        $product->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Status updated.']);
    }
}
