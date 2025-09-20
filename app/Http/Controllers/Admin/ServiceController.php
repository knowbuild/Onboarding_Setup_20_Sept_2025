<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\{User, Customer, Application, ApplicationService, CurrencyPricelist,  ProductMain, Service, ServicesEntry, ServiceQtyMaxDiscountPercentage,  Currency, WarrantyMaster, ProductTypeClassMaster};
use Carbon\Carbon;

class ServiceController extends Controller
{
public function index(Request $request)
{
    try {
        $page     = (int) $request->input('page', 1);
        $perPage  = (int) $request->input('record', 10);
        $priceTypeId = $request->input('price_type_id', 103);

        $query = Service::with(['pricing', 'discounts', 'category', 'typeClass']);

        /** -----------------------------
         * Apply Filters
         * ----------------------------- */

        // Category filter
        if ($request->filled('category_id')) {
            $query->where('cate_id', $request->category_id);
        }

        // Search filter (by UPC or Service Title)
        if ($request->filled('name_or_upc')) {
            $query->where(function ($q) use ($request) {
                $q->where('upc_code', 'like', '%' . $request->name_or_upc . '%')
                  ->orWhere('service_title', 'like', '%' . $request->name_or_upc . '%');
            });
        }

        // Hot service filter
        if (!is_null($request->hot)) {
            $query->where('hot_service', $request->hot);
        }

        // Price type filter
        $query->whereHas('pricing', function ($q) use ($priceTypeId) {
            $q->where('price_list_type_id', $priceTypeId);
        });

        /** -----------------------------
         * Pagination + Sorting
         * ----------------------------- */
        $paginated = $query->orderByDesc('service_id')
                           ->paginate($perPage, ['*'], 'page', $page);

        /** -----------------------------
         * Format Response
         * ----------------------------- */
        $data = $paginated->getCollection()->map(function ($service) use ($priceTypeId) {
            $pricing = $service->pricing->where('price_list_type_id', $priceTypeId)->first();

            return [
                'id'                      => $service->service_id,
                'name'                    => $service->service_title,
                'category_id'             => $service->cate_id,
                'product_id'              => $service->pro_id,
                'upc_code'                => $service->upc_code,
                'hot'                     => $service->hot_service,
                'status'                  => $service->status,
                'qty_slab'                => $service->qty_slab,
                'service_period_id'       => $service->service_period,
                'category_name'           => $service->category->application_service_name ?? null,
                'max_discount_percentage' => $service->service_max_discount,
                'price_list_type_id'      => $pricing->price_list_type_id ?? null,
                'price'                   => $pricing->service_price_entry ?? null,
                'updated_at'              => $pricing->updated_at ?? null,
            ];
        });

        return response()->json([
            'status'     => 'success',
            'message'    => 'Services listed successfully.',
            'data'       => $data,
            'pagination' => [
                'total'        => $paginated->total(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
            ]
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage(),
        ], 500);
    }
}


 public function edit(Request $request)
{
        $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_services,service_id'
    ]);
 if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }
    try {
        $service = Service::with(['pricing', 'discounts'])
            ->where('service_id', $request->id)
            ->firstOrFail();

        $response = [
                'id'                     => $service->service_id,
                'name'                   => $service->service_title,
                'category_id'            => $service->cate_id,
                'product_id'          => $service->pro_id,
                'upc_code'          => $service->upc_code,
                'hot'                    => $service->hot_service,
                'status'                 => $service->status,
                'qty_slab'                    => $service->qty_slab,
                'service_period_id'            => $service->service_period,
        

            'pricing' => $service->pricing->map(function ($price) {
                return [
                    'price_list_type_id' => $price->price_list_type_id,
                    'hsn_code'           => $price->hsn_code,
                    'item_code'          => $price->model_no,
                    'description'        => $price->service_desc_entry,
                    'price'              => $price->service_price_entry,
                    'currency'           => $price->currency,
                    'updated_at'         => $price->updated_at,
                ];
            }),

            'max_discount_percentage' => $service->service_max_discount,
            'quantity_discount_tiers' => $service->discounts->count(),

            'discounts' => $service->discounts->map(function ($discount) {
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

//   'quantity_discount_tiers' => $request->quantity_discount_tiers, is use in qty_slab in list
public function storeOrUpdate(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id'                       => 'nullable|exists:tbl_services,service_id',
        'name'                     => 'required|string|max:200',
        'category_id'              => 'required|exists:tbl_application_service,application_service_id',
        'product_id'            => 'required|exists:tbl_products,pro_id',
        'upc_code'               => 'required',
        'hot'                      => 'in:0,1',
        'status'                   => 'in:active,inactive',
        'service_period_id'              =>  'required|integer|exists:tbl_service_master,service_id',

        'pricing'                  => 'required|array|min:1',
        'pricing.*.price_list_type_id' => 'nullable|exists:tbl_currency_pricelist,pricelist_id',
        'pricing.*.hsn_code'       => 'nullable|string',
        'pricing.*.item_code'      => 'nullable|string',
        'pricing.*.description'    => 'nullable|string',
        'pricing.*.price'          => 'required|numeric',
        'pricing.*.currency'       => 'nullable|string',

        'max_discount_percentage'  => 'nullable|numeric',
        'quantity_discount_tiers'  => 'nullable|numeric',
        'discounts'                => 'nullable|array',
        'discounts.*.quantity_slab_from' => 'nullable|integer',
        'discounts.*.quantity_slab_to'   => 'nullable|integer',
        'discounts.*.discount_percentage'   => 'nullable|integer',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        $data = [];

      

        // Check quantity slab
        $qtySlabs = !empty($request->discounts) && count($request->discounts) > 0 ? 'Yes' : 'No';

        // Create or update the service
        $service = Service::updateOrCreate(
            ['service_id' => $request->id],
            array_merge($data, [
                'service_title'            => $request->name,
                'cate_id'              => $request->category_id,
                'hot_service'          => $request->hot,
                'status'               => $request->status,

                'service_period'         => $request->service_period_id,
                'qty_slab'             => $qtySlabs,
                'upc_code'             => $request->upc_code,
                'service_max_discount'     => $request->max_discount_percentage ?? 0,
            ])
        );

        // Remove old entries if updating
        if ($request->id) {
            ServicesEntry::where('service_id', $request->id)->delete();
            ServiceQtyMaxDiscountPercentage::where('serviceid', $request->id)->delete();
        }

          insertCategoryProductID('service', $service->service_id, $request->category_id);
          
        // Add service pricing entries
        foreach ($request->pricing as $priceData) {
            ServicesEntry::create([
                'service_id'             => $service->service_id,
                'app_cat_id'         => $request->category_id,
                'cate_id'            => $request->category_id,
                'price_list_type_id' => $priceData['price_list_type_id'] ?? null,
                'hsn_code'           => $priceData['hsn_code'] ?? null,
                'model_no'           => $priceData['item_code'] ?? null,
                'service_desc_entry'     => $priceData['description'] ?? null,
                'service_price_entry'    => $priceData['price'] ?? null,
                'currency'           => $priceData['currency'] ?? null,
                'price_list' =>'pvt',
                'sort_order' => 0,
        

            ]);
        }

        // Add quantity discount entries
        if (!empty($request->discounts)) {
            foreach ($request->discounts as $discountData) {
                ServiceQtyMaxDiscountPercentage::create([
                    'serviceid'              => $service->service_id,
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



public function destroy(Request $request)
{
    $validator = Validator::make($request->all(), [
        'id' => 'required|exists:tbl_services,service_id'
    ]);
 if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }
    try {
        $service = Service::with(['pricing', 'discounts'])
            ->where('service_id', $request->id)
            ->firstOrFail();

        // Delete related entries first
        $service->pricing()->delete();
        $service->discounts()->delete();

        // Delete the main service
        $service->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'service and related data deleted successfully.'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}


    public function updateStatus(Request $request)
    {
       $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_services,service_id',
            'status' => 'required|in:active,inactive'
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $service = Service::where('service_id', $request->id)->first();
        $service->update(['status' => $request->status]);

        return response()->json(['status' => 'success', 'message' => 'Status updated.']);
    }
}
  