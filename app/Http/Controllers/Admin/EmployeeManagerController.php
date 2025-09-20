<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\{
    User,
    AdminAllowedState,
    AdminAccessInModule,
    Country,
    State,
    City,
    Application,
    CustSegment
};

class EmployeeManagerController extends Controller
{
public function index(Request $request)
{
    try {
        // ? Collect inputs
        $employeeId       = $request->input('employee_id');
        $teamId           = $request->input('team_id');
        $businessFuncId   = $request->input('business_function_id');
        $status           = $request->input('status');
        $page             = (int) $request->input('page', 1);
        $perPage          = (int) $request->input('record', 1000);

        // ? Build base query
        $query = User::active()->with(['modulePermissions', 'allowedPMSRules', 'businessFunction', 'team', 'employeeRole'])
            ->orderByDesc('admin_id');

        // ? Apply filters
        if (!empty($employeeId)) {
            $query->where('admin_id', $employeeId);
        }

        if (!empty($teamId)) {
            $query->where('admin_team', $teamId);
        }

        if (!empty($businessFuncId)) {
            $query->where('admin_role_id', $businessFuncId);
        }

        if (!empty($status)) {
            $query->where('admin_status', $status);
        }


        // ? Get data and apply pagination manually
        $allData = $query->get();

        // ? Sort data (if needed)
        $sortedData = $allData->sortByDesc('admin_id')->values();

        // ? Paginate manually
        $paginatedData = $sortedData->forPage($page, $perPage)->values();

        // ? Format for response
        $formattedData = $paginatedData->map(fn($emp) => [

            'id'                => $emp->admin_id,
            'employee_name'     => trim("{$emp->admin_fname} {$emp->admin_lname}"),
            'business_function' => $emp->businessFunction->admin_role_name ?? '',
            'employee_role'     => $emp->employeeRole->name ?? '',
            'team'              => $emp->team->team_name ?? '',
            'email'             => $emp->admin_email,
            'status'            => $emp->admin_status,
        ]);

        // ? Build response
        return response()->json([
            'status'     => 'success',
            'message'    => 'Employee list retrieved successfully.',
            'data'       => $formattedData,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $sortedData->count(),
                'last_page'    => ceil($sortedData->count() / $perPage),
            ]
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while fetching employee list.',
            'error'   => $e->getMessage(),
        ], 500);
    }
}


 
public function edit(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_admin,admin_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $employeeId = $request->id;

        $employee = User::with(['modulePermissions', 'allowedPMSRules'])
            ->findOrFail($employeeId);

        $gender = $employee->admin_gender === 1 ? 'Male' : ($employee->admin_gender === 0 ? 'Female' : 'Other');

        $allowedCountry = !empty($employee->allowed_country)
            ? array_map('intval', explode(', ', $employee->allowed_country))
            : [];

        $pqvCategories = !empty($employee->allowed_category)
            ? array_map('intval', explode(', ', $employee->allowed_category))
            : [];

        $subordinates = !empty($employee->subordinates)
            ? array_map('intval', explode(', ', $employee->subordinates))
            : [];

        $pmsRulesRaw = $employee->allowedPMSRules()
            ->select('allowed_country', 'allowed_states', 'allowed_city', 'allowed_segments', 'allowed_category')
            ->get()
            ->groupBy('allowed_country');

        $pmsRules = [];
        foreach ($pmsRulesRaw as $countryId => $detailsGroup) {
            $details = [];
            foreach ($detailsGroup as $detail) {
                $details[] = [
                    'states'     => (int)$detail->allowed_states,
                    'cities'     => !empty($detail->allowed_city) ? array_map('intval', explode(', ', $detail->allowed_city)) : [],
                    'segments'   => !empty($detail->allowed_segments) ? array_map('intval', explode(', ', $detail->allowed_segments)) : [],
                    'categories' => !empty($detail->allowed_category) ? array_map('intval', explode(', ', $detail->allowed_category)) : []
                ];
            }
            $pmsRules[] = [
                'country' => (int)$countryId,
                'details' => $details
            ];
        }

        $permissionsRaw = $employee->modulePermissions()
            ->select('module_id', 'page_id')
            ->get()
            ->groupBy('module_id');

        $permissions = [];
        foreach ($permissionsRaw as $moduleId => $submodulesGroup) {
            $submodules = [];
            foreach ($submodulesGroup as $submodule) {
                $submodules[] = ['submodule_id' => (int)$submodule->page_id];
            }
            $permissions[] = [
                'module_id' => (int)$moduleId,
                'submodules' => $submodules
            ];
        }

        $employeeData = [
             'id'                   => $employee->admin_id,
            'first_name'           => $employee->admin_fname,
            'last_name'            => $employee->admin_lname,
            'gender'               => $gender,
            'business_function_id' => (string)$employee->admin_role_id,
            'role_id'              => (string)$employee->allowed_module,
            'address'              => $employee->admin_address,
            'country_id'           => (int)$employee->admin_country,
            'state_id'             => (int)$employee->admin_state,
            'city_id'              => (int)$employee->admin_city,
            'zip_code'             => $employee->admin_zip,
            'telephone'            => $employee->admin_telephone,
            'allowed_country'      => $allowedCountry,
            'email'                => $employee->admin_email,
            'password'             => $employee->admin_password,
            'confirm_password'     => $employee->admin_password,
            'pqv_product_category' => $pqvCategories,
            'pms_rules'            => $pmsRules,
            'team_id'              => $employee->admin_team,
            'team_leader_id'       => $employee->sub_team_lead,
            'team_manager_id'      => $employee->admin_team_lead,
            'subordinates'         => $subordinates,
            'permissions'          => $permissions
        ];

        return response()->json([
            'status'  => 'success',
            'message' => 'Employee details retrieved successfully.',
            'employee'=> $employeeData
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while fetching editable data.',
            'error'   => $e->getMessage()
        ], 500);
    }
}


 public function storeOrUpdate(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                  'id'                   => 'nullable|exists:tbl_admin,admin_id',
                'first_name'           => 'required|string|max:50',
                'last_name'            => 'required|string|max:50',
                'gender'               => 'nullable|in:Male,Female',
                'business_function_id' => 'required|integer',
                'role_id'              => 'required|integer',
                'address'              => 'required|string|max:255',
                'country_id'           => 'required|integer',
                'state_id'             => 'required|integer',
                'city_id'              => 'required|integer',
                'zip_code'             => 'required|digits_between:4,10',
                'telephone'            => 'required|string|max:15',
                'allowed_country'      => 'nullable|array',
                'email'                => [
                    'required',
                    'email',
                    'max:100',
                    Rule::unique('tbl_admin', 'admin_email')->ignore($request->id, 'admin_id')
                ],
                'password'             => $request->id ? 'nullable|min:6' : 'required|min:6',
                'confirm_password'     => $request->id ? 'nullable|same:password' : 'required|same:password',
              
                'pqv_product_category' => 'nullable|array',

                //  PMS Rules Validation
                'pms_rules'                               => 'nullable|array',
                'pms_rules.*.country'                     => 'required|integer',
                'pms_rules.*.details'                     => 'required|array',
                'pms_rules.*.details.*.states'            => 'required|integer',
                'pms_rules.*.details.*.cities'            => 'nullable|array',
                'pms_rules.*.details.*.segments'          => 'nullable|array',
                'pms_rules.*.details.*.categories'        => 'nullable|array',

                //  Team Assignment
                'team_id'                     => 'nullable|integer|exists:tbl_team,team_id',
                'team_leader_id'              => 'nullable|integer|exists:tbl_admin,admin_id',
                'team_manager_id'             => 'nullable|integer|exists:tbl_admin,admin_id',
                'subordinates'                => 'nullable|array',
                'subordinates.*'              => 'integer|exists:tbl_admin,admin_id',

                //  Permissions
                'permissions'                                 => 'nullable|array|min:1',
                'permissions.*.module_id'                    => 'required|integer|exists:tbl_website_page_module,module_id',
                'permissions.*.submodules'                   => 'required|array|min:1',
                'permissions.*.submodules.*.submodule_id'    => 'required|integer|exists:tbl_website_page,page_id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $isUpdate = !empty($request->id);

            //  Convert gender to integer
            $gender_taken = $request->gender === 'Male' ? 1 : ($request->gender === 'Female' ? 0 : null);

            //  Employee Data Mapping
            $employeeData = [
                'admin_fname'      => $request->first_name,
                'admin_lname'      => $request->last_name,
                'admin_gender'     => $gender_taken,
                'admin_role_id'    => $request->business_function_id,
                'allowed_module'   => $request->role_id,
                'admin_address'    => $request->address,
                'admin_country'    => $request->country_id,
                'admin_state'      => $request->state_id,
                'admin_city'       => $request->city_id,
                'admin_zip'   => $request->zip_code,
                'admin_telephone'  => $request->telephone,
               // 'allowed_country'  => json_encode($request->allowed_country ?? []),
                'allowed_country'  =>  isset($request->allowed_country) ? implode(', ', $request->allowed_country) : null,
                'admin_email'      => $request->email,
               // 'allowed_category' => json_encode($request->pqv_product_category ?? []),
                 'allowed_category' =>  isset($request->pqv_product_category) ? implode(', ', $request->pqv_product_category) : null,
                'admin_team'       => $request->team_id,
                'sub_team_lead'    => $request->team_leader_id,
                'admin_team_lead'  => $request->team_manager_id,
               // 'subordinates'     => json_encode($request->subordinates ?? [])
               'subordinates' => isset($request->subordinates) ? implode(', ', $request->subordinates) : null

            ];

            if (!empty($request->password)) {
                $employeeData['password'] = bcrypt($request->password);
                $employeeData['admin_password'] = $request->password;
            }

            //  Create or Update Employee
            $employee = User::updateOrCreate(
                ['admin_id' => $request->id],
                $employeeData
            );

            $employee_id = $employee->admin_id;

            //  Handle PMS Rules
            if ($request->has('pms_rules')) {
                AdminAllowedState::where('admin_id', $employee_id)->delete();

                $pMSAssignmentData = [];
                foreach ($request->pms_rules as $rule) {
                    foreach ($rule['details'] as $ruleDetails) {
                        $pMSAssignmentData[] = [
                            'admin_id'        => $employee_id,
                            'allowed_country' => $rule['country'],
                            'allowed_states'  => $ruleDetails['states'],
                            'allowed_city'    => isset($ruleDetails['cities']) ? implode(', ', $ruleDetails['cities']) : null,
                            'allowed_segments'=> isset($ruleDetails['segments']) ? implode(', ', $ruleDetails['segments']) : null,
                            'allowed_category'=> isset($ruleDetails['categories']) ? implode(', ', $ruleDetails['categories']) : null
                            //  'allowed_city'    => json_encode($ruleDetails['cities'] ?? []),
                           // 'allowed_segments'=> json_encode($ruleDetails['segments'] ?? []),
                           // 'allowed_category'=> json_encode($ruleDetails['categories'] ?? [])
                        ];
                    }
                }

                if (!empty($pMSAssignmentData)) {
                    AdminAllowedState::insert($pMSAssignmentData);
                }
            }

            //  Handle Permissions
            if ($request->has('permissions')) {
                AdminAccessInModule::where('admin_id', $employee_id)->delete();

                $permissionsData = [];
                foreach ($request->permissions as $module) {
                    foreach ($module['submodules'] as $submodule) {
                        $permissionsData[] = [
                            'admin_id'      => $employee_id,
                            'admin_role_id' => $request->business_function_id,
                            'module_id'     => $module['module_id'],
                            'page_id'       => $submodule['submodule_id']
                        ];
                    }
                }

                if (!empty($permissionsData)) {
                    AdminAccessInModule::insert($permissionsData);
                }
            }

            return response()->json([
                'status'  => 'success',
                'message' => $isUpdate ? 'Employee updated successfully.' : 'Employee created successfully.',
               // 'data'    => $employee
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Something went wrong.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
       $validator = Validator::make($request->all(), [
             'id' => 'required|exists:tbl_admin,admin_id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        User::where('admin_id', $request->id)->update(['deleteflag' => 'inactive']);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee Manager deleted successfully.'
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive',
            'id' => 'required|exists:tbl_admin,admin_id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $record = User::find($request->id);
        $record->admin_status = $request->status;
        $record->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Employee Manager status updated successfully.'
        ], 200);
    }

  public function view(Request $request)
{
    try {
        // -----------------------------
        // Validate Input
        // -----------------------------
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:tbl_admin,admin_id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $employeeId = (int) $request->id;

        // -----------------------------
        // Fetch Employee with Relations
        // -----------------------------
        $employee = User::with(['modulePermissions', 'allowedPMSRules'])
            ->findOrFail($employeeId);

        // -----------------------------
        // PQV Product Categories
        // -----------------------------
        $pqvCategories = !empty($employee->allowed_category)
            ? array_map('intval', explode(', ', $employee->allowed_category))
            : [];

        $categoriesName = !empty($pqvCategories)
            ? Application::whereIn('application_id', $pqvCategories)
                ->pluck('application_name')
                ->toArray()
            : [];

        $pqvCategoriesData = [
            'pqv_product_category' => $categoriesName
        ];

        // -----------------------------
        // PMS Rules (Group by Country)
        // -----------------------------
        $pmsRulesRaw = $employee->allowedPMSRules()
            ->select('allowed_country', 'allowed_states', 'allowed_city', 'allowed_segments', 'allowed_category')
            ->get()
            ->groupBy('allowed_country');

        $pmsRules = [];

        foreach ($pmsRulesRaw as $countryId => $detailsGroup) {
            $details = [];

            foreach ($detailsGroup as $detail) {
                // Convert string fields to arrays
                $citiesArray     = $detail->allowed_city ? array_map('intval', explode(', ', $detail->allowed_city)) : [];
                $segmentsArray   = $detail->allowed_segments ? array_map('intval', explode(', ', $detail->allowed_segments)) : [];
                $categoriesArray = $detail->allowed_category ? array_map('intval', explode(', ', $detail->allowed_category)) : [];

                // Fetch names instead of IDs
                $stateName      = $detail->allowed_states ? (State::find($detail->allowed_states)->zone_name ?? null) : null;
                $cityNames      = !empty($citiesArray) ? City::whereIn('city_id', $citiesArray)->pluck('city_name')->toArray() : [];
                $segmentNames   = !empty($segmentsArray) ? CustSegment::whereIn('cust_segment_id', $segmentsArray)->pluck('cust_segment_name')->toArray() : [];
                $categoryNames  = !empty($categoriesArray) ? Application::whereIn('application_id', $categoriesArray)->pluck('application_name')->toArray() : [];
 
                $details[] = [
                    'state'      => $stateName,
                    'cities'     => $cityNames,
                    'segments'   => $segmentNames,
                    'categories' => $categoryNames
                ];
            }

            $countryName = Country::find($countryId)->country_name ?? null;

            $pmsRules[] = [
                'countryId' => (int) $countryId,
                'country'   => $countryName,
                'details'   => $details
            ];
        }

        // -----------------------------
        // Prepare Final Response
        // -----------------------------
        $employeeData = [
            'id'                   => $employee->admin_id,
            'name'                 => $employee->admin_fname . " " . $employee->admin_lname ?? null,
            'pqv_product_category' => $pqvCategoriesData,
            'pms_rules'            => $pmsRules,
        ];

        return response()->json([
            'status'  => 'success',
            'message' => 'Employee details retrieved successfully.',
            'employee'=> $employeeData
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => 'error',
            'message' => 'Something went wrong while fetching editable data.',
            'error'   => $e->getMessage()
        ], 500);
    }
}


}
 