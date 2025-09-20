<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Country;
use App\Models\FinancialYear;
use App\Models\FiscalMonth;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

class EmployeeController extends Controller
{


public function index(Request $request) 
{
    $query = User::with([
            'department:id,name', 
            'designation:id,name', 
            'country:id,name', 
            'state:id,name', 
            'city:id,name'
        ])
        ->select(
            'id', 'name', 'email', 'mobile', 'department_id', 'designation_id',
            'country_id', 'state_id', 'city_id', 'user_type', 'status',
            'account_status', 'category_ids', 'segment_ids'
        )
        ->where('user_type', 'user');

    // Filter by location
    $query->when($request->filled('country_id'), fn($q) => $q->where('country_id', $request->country_id));
    $query->when($request->filled('state_id'), fn($q) => $q->where('state_id', $request->state_id));
    $query->when($request->filled('city_id'), fn($q) => $q->where('city_id', $request->city_id));

    // Filter by category and segment
    $query->when($request->filled('category_ids'), fn($q) => $q->where('category_ids', 'like', "%{$request->category_ids}%"));
    $query->when($request->filled('segment_ids'), fn($q) => $q->where('segment_ids', 'like', "%{$request->segment_ids}%"));

    // Financial Year Filter
    if ($request->filled('financial_year_id')) {
        applyFinancialYearFilter($query, $request->financial_year_id);
    }

    // Search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%$search%")
              ->orWhere('email', 'like', "%$search%");
        });
    }

    // Sorting
    $sort = $request->get('sort_by');
    match ($sort) {
        'AToZ' => $query->orderBy('name'),
        'ZToA' => $query->orderByDesc('name'),
        'OldToNew' => $query->orderBy('created_at'),
        'NewToOld' => $query->orderByDesc('created_at'),
        default => $query->orderByDesc('id')
    };

    // Pagination
    $page = $request->input('page', 1);
    $perPage = $request->input('record', 10);

    Paginator::currentPageResolver(function () use ($page) {
        return $page;
    });

    $paginated = $query->paginate($perPage)->appends($request->all());

    // Transform result
    $data = $paginated->getCollection()->map(function ($user) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'department_id' => $user->department_id,
            'department_name' => optional($user->department)->name,
            'designation_id' => $user->designation_id,
            'designation_name' => optional($user->designation)->name,
            'country_id' => $user->country_id,
            'state_id' => $user->state_id,
            'city_id' => $user->city_id,
            'country_name' => optional($user->country)->name,
            'state_name' => optional($user->state)->name,
            'city_name' => optional($user->city)->name,
            'user_type' => $user->user_type,
            'status' => $user->status,
            'account_status' => $user->account_status,
            'category_ids' => $user->category_ids,
            'segment_ids' => $user->segment_ids,
        ];
    });

    // Return with pagination metadata
    return response()->json([
        'status' => 'success',
        'message' => 'Employees listed successfully.',
        'data' => $data,
        'pagination' => [
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ],
    ]);
}







    public function edit(Request $request)
    {
        $user = User::select(
            'id', 'name', 'email', 'department_id', 'designation_id',
            'country_id', 'state_id', 'city_id', 'category_ids', 'segment_ids',
            'user_type', 'status', 'account_status','mobile'
        )->find($request->id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Employee not found.'
            ], 404);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Employee details retrieved successfully.',
            'data'    => $user
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'             => 'nullable|exists:users,id',
            'name'           => 'required|string|max:255',
            'email'          => 'required|email|unique:users,email,' . $request->id,
            'mobile'         => 'required',
            'password'       => $request->id ? 'nullable|string|min:6' : 'required|string|min:6',
            'department_id'  => 'nullable|exists:departments,id',
            'designation_id' => 'nullable|exists:designations,id',
            'country_id'     => 'nullable|exists:countries,id',
            'state_id'       => 'nullable|exists:states,id',
            'city_id'        => 'nullable|exists:cities,id',
            'category_ids'   => 'nullable|string',
            'segment_ids'    => 'nullable|string',
            'user_type'      => 'required|in:admin,user',
            'status'         => 'required|in:active,inactive',
            'account_status' => 'required|in:pending,approved,hold,expired,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $isUpdate = !empty($request->id);
        $data = $request->only([
            'name', 'email', 'department_id', 'designation_id',
            'country_id', 'state_id', 'city_id', 'category_ids', 'segment_ids',
            'user_type', 'status', 'account_status','mobile'
        ]);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
            $data['password_show'] = $request->password;
        }

        $user = User::updateOrCreate(['id' => $request->id], $data);

        return response()->json([
            'status'  => 'success',
            'message' => $isUpdate ? 'Employee updated successfully.' : 'Employee created successfully.',
        ]);
    }

    public function destroy(Request $request)
    {
        $user = User::find($request->id);

        if (!$user) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Employee not found.'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Employee deleted successfully.'
        ]);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|exists:users,id',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::find($request->id);
        $user->status = $request->status;
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Employee status updated successfully.'
        ]);
    }
}
