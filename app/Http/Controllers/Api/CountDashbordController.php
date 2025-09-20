<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Models\WebEnquiry;
use App\Models\Models\WebEnquiryEdit;
use Carbon\Carbon;
use App\Models\Models\Order;
use App\Models\Models\AdminUser;
use Illuminate\Support\Facades\DB;
class CountDashbordController extends Controller
{


public function totalEnquiries(Request $request)
{
    $acc_manager = $request->input('acc_manager');
    $enq_date = $request->input('financial_year'); // Expected format: 2024-2025

    if ($enq_date) {
        list($startYear, $endYear) = explode('-', $enq_date);
    } else {
        $currentYear = date('Y');
        $startYear = (date('m') >= 4) ? $currentYear : $currentYear - 1;
        $endYear = $startYear + 1;
    }
    
    $start_date = "{$startYear}-04-01"; // 1st April of the start year
    $end_date = "{$endYear}-03-31"; // 31st March of the end year
    

    $queryWebEnq = WebEnquiry::query();
    $queryWebEnqEdit = WebEnquiryEdit::query();

    if ($acc_manager) {
        $queryWebEnqEdit->where('acc_manager', $acc_manager);
    }

    if ($enq_date) {
        $queryWebEnq->whereBetween('Enq_Date', [$start_date, $end_date]);
        $queryWebEnqEdit->whereBetween('Enq_Date', [$start_date, $end_date]);
    }

    $totalEnquiries = $queryWebEnq->where('tbl_web_enq.dead_duck', '0')->count();
    $assignedEnquiries = $queryWebEnqEdit->where('tbl_web_enq_edit.dead_duck', '0')->count();

    // Count unassigned enquiries (no assigned account manager)
    $unassignedEnquiries = WebEnquiry::leftJoin('tbl_web_enq_edit', 'tbl_web_enq.id', '=', 'tbl_web_enq_edit.enq_id')
        ->whereNull('tbl_web_enq_edit.acc_manager');

    if ($acc_manager) {
        $unassignedEnquiries->where('tbl_web_enq_edit.acc_manager', $acc_manager);
    }

    if ($enq_date) {
        $unassignedEnquiries->whereBetween('tbl_web_enq.Enq_Date', [$start_date, $end_date]);
    }

    $unassignedEnquiries = $unassignedEnquiries->count();

    // Count escalations (unassigned enquiries older than 1 day)
    $escalations = WebEnquiry::leftJoin('tbl_web_enq_edit', 'tbl_web_enq.id', '=', 'tbl_web_enq_edit.enq_id')
        ->whereNull('tbl_web_enq_edit.acc_manager')
        ->whereDate('tbl_web_enq.Enq_Date', '<', Carbon::now()->subDay());

    if ($acc_manager) {
        $escalations->where('tbl_web_enq_edit.acc_manager', $acc_manager);
    }

    if ($enq_date) {
        $escalations->whereBetween('tbl_web_enq.Enq_Date', [$start_date, $end_date]);
    }

    $escalations = $escalations->count();

    // Count deleted enquiries (filtered by inactive delete flag)
    $deletedEnquiries = WebEnquiry::where('tbl_web_enq.deleteflag', 'inactive');


    if ($acc_manager) {
        $deletedEnquiries->join('tbl_web_enq_edit', 'tbl_web_enq.id', '=', 'tbl_web_enq_edit.enq_id')
                         ->where('tbl_web_enq_edit.acc_manager', $acc_manager);
    }

    if ($enq_date) {
        $deletedEnquiries->whereBetween('tbl_web_enq.Enq_Date', [$start_date, $end_date]);
    }

    $deletedEnquiries = $deletedEnquiries->count();
	
	
	
	 $deadduckEnquiries = WebEnquiry::where('tbl_web_enq.dead_duck', '0');


    if ($acc_manager) {
        $deadduckEnquiries->join('tbl_web_enq_edit', 'tbl_web_enq.id', '=', 'tbl_web_enq_edit.enq_id')
                         ->where('tbl_web_enq_edit.dead_duck', '0')
						 ->where('tbl_web_enq_edit.acc_manager', $acc_manager);
    }

    if ($enq_date) {
        $deadduckEnquiries->whereBetween('tbl_web_enq.Enq_Date', [$start_date, $end_date]);
    }

    $deadduckEnquiries = $deadduckEnquiries->count();

    // Avoid division by zero
    $assignedPercentage = $totalEnquiries > 0 ? ($assignedEnquiries / $totalEnquiries) * 100 : 0;
    $escalationsPercentage = $totalEnquiries > 0 ? ($escalations / $totalEnquiries) * 100 : 0;
    $unassignedPercentage = $totalEnquiries > 0 ? ($unassignedEnquiries / $totalEnquiries) * 100 : 0;
    $deletedPercentage = $totalEnquiries > 0 ? ($deletedEnquiries / $totalEnquiries) * 100 : 0;

    $data = [
        
        'total_enquiries' => $totalEnquiries,
        'assigned_enquiries' => $assignedEnquiries,
        'escalations' => $escalations,
        'unassigned_enquiries' => $unassignedEnquiries,
        'deleted_enquiries' => $deletedEnquiries,
        'assigned_enquiries_per' => round($assignedPercentage, 2),
        'escalations_per' => round($escalationsPercentage, 2),
        'unassigned_enquiries_per' => round($unassignedPercentage, 2),
        'deleted_enquiries_per' => round($deletedPercentage, 2),
        
    ];

    return response()->json($data);
// Ensure $data is an array
//$data = is_array($data) && array_keys($data) !== range(0, count($data) - 1) ? [$data] : $data;

//return response()->json($data);

}
public function getOrderEnquiryDiffDays(Request $request)
{
    $acc_manager = $request->input('acc_manager');
    $enq_date = $request->input('financial_year'); // Expected format: 2024-2025

    // Determine the financial year start and end dates
    if ($enq_date) {
        list($startYear, $endYear) = explode('-', $enq_date);
    } else {
        $currentYear = date('Y');
        $startYear = (date('m') >= 4) ? $currentYear : $currentYear - 1;
        $endYear = $startYear + 1;
    }

    $start_date = "{$startYear}-04-01"; // 1st April of the start year
    $end_date = "{$endYear}-03-31"; // 31st March of the end year

    // Fetch order and enquiry date differences with grouped counts
    $queryResults = Order::join('tbl_web_enq_edit', 'tbl_web_enq_edit.id', '=', 'tbl_order.edited_enq_id')
        ->join('tbl_admin', 'tbl_web_enq_edit.acc_manager', '=', 'tbl_admin.admin_id')
        ->selectRaw('
            tbl_web_enq_edit.acc_manager, 
            CONCAT(tbl_admin.admin_fname, " ", tbl_admin.admin_lname) AS admin_name,
            SUM(CASE WHEN DATEDIFF(tbl_order.date_ordered, tbl_web_enq_edit.Enq_Date) < 1 THEN 1 ELSE 0 END) AS "< 1 day",
            SUM(CASE WHEN DATEDIFF(tbl_order.date_ordered, tbl_web_enq_edit.Enq_Date) >= 1 
                     AND DATEDIFF(tbl_order.date_ordered, tbl_web_enq_edit.Enq_Date) < 2 THEN 1 ELSE 0 END) AS "< 1/2 day",
            SUM(CASE WHEN DATEDIFF(tbl_order.date_ordered, tbl_web_enq_edit.Enq_Date) >= 2
                     AND DATEDIFF(tbl_order.date_ordered, tbl_web_enq_edit.Enq_Date) < 3 THEN 1 ELSE 0 END) AS "< 3/4 day",
            SUM(CASE WHEN DATEDIFF(tbl_order.date_ordered, tbl_web_enq_edit.Enq_Date) >= 3 
                     AND DATEDIFF(tbl_order.date_ordered, tbl_web_enq_edit.Enq_Date) < 5 THEN 1 ELSE 0 END) AS "< 5 day"
        ')
        ->whereBetween('tbl_web_enq_edit.Enq_Date', [$start_date, $end_date]);

    if ($acc_manager) {
        $queryResults->where('tbl_web_enq_edit.acc_manager', $acc_manager);
    }

    $groupedData = $queryResults->groupBy('tbl_web_enq_edit.acc_manager', 'admin_name')->get();

    // Convert result to array
    $responseData = $groupedData->toArray();

  
    return response()->json(count($responseData) ? $responseData : [[]]);
}


public function saveEnquiry(Request $request)
{
    $enquiryData = $request->input('enquiry_data'); // Extract enquiry_data
    $accManager = $request->input('acc_manager'); // Extract acc_manager from root level
    $id = $request->input('enq_id');

    // Validation Rules
    $rules = [
        'enquiry_data.Cus_name' => 'required|string|max:255',
        'enquiry_data.Cus_email' => 'required|email|max:255',
        'enquiry_data.Cus_mob' => 'required|digits_between:10,15',
        'enquiry_data.Cus_msg' => 'nullable|string',
        'enquiry_data.enq_remark' => 'nullable|string',
        'enquiry_data.country' => 'required|integer',
        'enquiry_data.state' => 'nullable|string|max:255',
        'enquiry_data.city' => 'nullable|string|max:255',
        'enquiry_data.enquiry_type' => 'required|string',
        'enquiry_data.company_segment' => 'required|integer',
        'enquiry_data.product_category' => 'required|integer',
        'enquiry_data.enquiry_source' => 'required|string|max:255',
        'acc_manager' => 'required|integer',
    ];

    // Validate request
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // Data for tbl_web_enq
    $webEnquiryData = [
        "Cus_name" => $enquiryData['Cus_name'],
        "Cus_email" => $enquiryData['Cus_email'],
        "Cus_mob" => $enquiryData['Cus_mob'],
        "Cus_msg" => $enquiryData['Cus_msg'],
        "enq_remark" => $enquiryData['enq_remark'],
        "ref_source" => $enquiryData['enquiry_source'],
        
    ];

    // Data for tbl_web_enq_edit
    $webEnquiryEditData = [
        "Cus_name" => $enquiryData['Cus_name'],
        "Cus_email" => $enquiryData['Cus_email'],
        "Cus_mob" => $enquiryData['Cus_mob'],
        "Cus_msg" => $enquiryData['Cus_msg'],
        "address" => $enquiryData['address'] ?? null, // Handle missing address
        "country" => (int)$enquiryData['country'],
        "state" => $enquiryData['state'] ?? null,
        "city" => $enquiryData['city'] ?? null,
        "enq_type" => $enquiryData['enquiry_type'],
        "cust_segment" => $enquiryData['company_segment'],
        "product_category" => $enquiryData['product_category'],
        "ref_source" => $enquiryData['enquiry_source'],
        "acc_manager" => $accManager,
    ];

    if (empty($id)) {
        // Create new entry
        $webEnquiry = WebEnquiry::create($webEnquiryData);
        $webEnquiryEditData['enq_id'] = $webEnquiry->id;
        $webEnquiryEdit = WebEnquiryEdit::create($webEnquiryEditData);
    } else {
        // Update existing entry
        WebEnquiry::where('id', $id)->update($webEnquiryData);
        WebEnquiryEdit::updateOrCreate(
            ['enq_id' => $id],
            $webEnquiryEditData
        );

        // Fetch updated records
        $webEnquiry = WebEnquiry::find($id);
        $webEnquiryEdit = WebEnquiryEdit::where('enq_id', $id)->first();
    }

    // ? Prevent error if $webEnquiryEdit is null
    $result = [
        'Cus_name' => $webEnquiry->Cus_name,
        'Cus_email' => $webEnquiry->Cus_email,
        'Cus_mob' => $webEnquiry->Cus_mob,
        'Cus_msg' => $webEnquiry->Cus_msg,
        'enq_remark' => $webEnquiry->enq_remark,
        'address' => $webEnquiryEdit->address ?? null, // Handle null safely
        'country' => $webEnquiryEdit->country ?? null,
        'state' => $webEnquiryEdit->state ?? null,
        'city' => $webEnquiryEdit->city ?? null,
        'enquiry_type' => $webEnquiryEdit->enq_type ?? null,
        'company_segment' => $webEnquiryEdit->cust_segment ?? null,
        'product_category' => $webEnquiryEdit->product_category ?? null,
        'enquiry_source' => $webEnquiryEdit->ref_source ?? null,
        'acc_manager' => $webEnquiryEdit->acc_manager ?? null,
        'enq_id' => $webEnquiryEdit->enq_id ?? $id,
        'eid' => $webEnquiryEdit->ID,

    ];

//print_r($result);
//////////////whatsapp starts////////


	//$notify_cust	= $_REQUEST["notify_cust"];
		/*if($notify_cust=='1')
		{*/
		$customers_contact_no			= $webEnquiry->Cus_mob;
		$acc_manager_name 				= admin_name($webEnquiryEdit->acc_manager);
		$acc_manager_email 				= account_manager_email($webEnquiryEdit->acc_manager);
		$acc_manager_phone 				= "91".account_manager_phone($webEnquiryEdit->acc_manager);			
			//echo "Dispatch details";
			if(strlen($customers_contact_no)<=10)
			{
				$phoneno="91".$customers_contact_no;
			}
			else
			{
					$phoneno="91".$customers_contact_no;
			}
			//echo "PH NO::::".$phoneno;
			//$phoneno="919811169723";//23-dec-2019
	 		//$acc_manager_phone="917053062400";//23-dec-2019
			//$phoneno="91".$customers_contact_no;
//echo "Cust Contact No:".		$phoneno=	$_REQUEST["enq_cust_mobile"];
$Enq_Date_w				= date("y");
$enq_edited_id			= $result['eid']; //exit;//$webEnquiry->id;
$EID_w=$Enq_Date_w.$enq_source_abbrv_w =enq_source_abbrv($enquiryData['enquiry_source']).$enq_edited_id;

$message='Dear *'.$webEnquiry->Cus_name.'*,'." \r\n ".
' Enquiry ID : '.$EID_w.'.'." \r\n ".
' We are in receipt of your enquiry for : '.$webEnquiryEdit->product_category.' .'." \r\n ".
' *Sales A/c manager* for your case is *'.$acc_manager_name.'*'." \r\n ".
' *Email :* '.$acc_manager_email." \r\n ".
' *Contact no. :* '.$acc_manager_phone." \r\n ".
' Incase of urgency, you may like to contact us directly on above contact no or our sales helpline'." \r\n ".
' Thanking You,'." \r\n ".
' ACL Stanlay Team'." \r\n ".
' Helpline: 011-41406926'." \r\n ".
' Web : www.stanlay.in'." \r\n ".
'------------------------------------------------------------'."".
' ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
whatsapp_msg($phoneno,$message);//function to send whatsapp 21-dec-2019
		 
		 
		 
/*************whatsapp send to AC MANAGER***********/		  
//$acc_manager_phone=$acc_manager_phone;
$message_ac_manager='Dear  *'.$acc_manager_name.'*,'. "\r\n". 
' *Enquiry ID* : '.$EID_w.', '." Assigned to you. \r\n".
' Please get in touch with customer: '."\r\n".
' *Customer Name :* '.$webEnquiry->Cus_name."\r\n".
' *Email :* '.$webEnquiry->Cus_email."\r\n".
' *Contact no. :* '.$webEnquiry->Cus_mob."\r\n".
' *Customer Message. :* '.$webEnquiry->Cus_msg."\r\n".
' Thanking You,'."\r\n".
' ACL Stanlay Team'."\r\n".
' Helpline: 011-41406926'."\r\n".
' Web : www.stanlay.in'."\r\n".
'------------------------------------------------------------'."".
' ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
 whatsapp_msg($acc_manager_phone,$message_ac_manager);

/////////////whatsapp ends//////////



    return response()->json(['success' => true, 'enquiry_data' => $result]);
}



public function editEnquiry(Request $request)
{
    $id = $request->enq_id;

    // Fetch the web enquiry and its edit details
    $webEnquiry = WebEnquiry::find($id);
    $webEnquiryEdit = WebEnquiryEdit::where('enq_id', $id)->first();

    // Check if the enquiry and its edit details are found
    if (!$webEnquiry || !$webEnquiryEdit) {
        return response()->json(['success' => false, 'message' => 'Enquiry not found'], 404);
    }

    // Prepare the response data
    $result = [
        'Cus_name' => $webEnquiry->Cus_name,
        'Cus_email' => $webEnquiry->Cus_email,
        'Cus_mob' => $webEnquiry->Cus_mob,
        'Cus_msg' => $webEnquiry->Cus_msg,
        'enq_remark' => $webEnquiry->enq_remark,
        'address' => $webEnquiryEdit->address,
        'country' => $webEnquiryEdit->country,
        'state' => $webEnquiryEdit->state,
        'city' => $webEnquiryEdit->city,
        'enquiry_type' => $webEnquiryEdit->enq_type,
        'company_segment' => $webEnquiryEdit->cust_segment,
        'product_category' => $webEnquiryEdit->product_category,
        'enquiry_source' => $webEnquiryEdit->ref_source,
        'acc_manager' => $webEnquiryEdit->acc_manager,
        'enq_id' => $webEnquiryEdit->enq_id,
    ];
	
	
	
	
//////////////whatsapp starts////////


//	$notify_cust	= $_REQUEST["notify_cust"];
		/*if($notify_cust=='1')
		{*/
		$customers_contact_no			= $webEnquiry->Cus_mob;
		$acc_manager_name 				= admin_name($webEnquiryEdit->acc_manager);
		$acc_manager_email 				= account_manager_email($webEnquiryEdit->acc_manager);
		$acc_manager_phone 				= "91".account_manager_phone($webEnquiryEdit->acc_manager);			
			//echo "Dispatch details";
			if(strlen($customers_contact_no)<=10)
			{
				$phoneno="91".$customers_contact_no;
			}
			else
			{
					$phoneno="91".$customers_contact_no;
			}
			//echo "PH NO::::".$phoneno;
			$phoneno="919811169723";//23-dec-2019
	 		$acc_manager_phone="917053062400";//23-dec-2019
			//$phoneno="91".$customers_contact_no;
//echo "Cust Contact No:".		$phoneno=	$_REQUEST["enq_cust_mobile"];
/*$Enq_Date_w				= date("y");
$enq_edited_id			= $webEnquiry->id;
$EID_w=$Enq_Date_w.$enq_source_abbrv_w =enq_source_abbrv($enquiryData['enquiry_source']).$enq_edited_id;

$message='Dear *'.$webEnquiry->Cus_name.'*,'." \r\n ".
' Enquiry ID : '.$EID_w.'.'." \r\n ".
' We are in receipt of your enquiry for : '.$webEnquiryEdit->product_category.' .'." \r\n ".
' *Sales A/c manager* for your case is *'.$acc_manager_name.'*'." \r\n ".
' *Email :* '.$acc_manager_email." \r\n ".
' *Contact no. :* '.$acc_manager_phone." \r\n ".
' Incase of urgency, you may like to contact us directly on above contact no or our sales helpline'." \r\n ".
' Thanking You,'." \r\n ".
' ACL Stanlay Team'." \r\n ".
' Helpline: 011-41406926'." \r\n ".
' Web : www.stanlay.in'." \r\n ".
'------------------------------------------------------------'."".
' ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
whatsapp_msg($phoneno,$message);*/
//function to send whatsapp 21-dec-2019
		 
		 
		 
/*************whatsapp send to AC MANAGER***********/		  
//$acc_manager_phone=$acc_manager_phone;
$message_ac_manager='Dear  *'.$acc_manager_name.'*,'. "\r\n". 
' *Enquiry ID* : '.$EID_w.', '." Assigned to you. \r\n".
' Please get in touch with customer: '."\r\n".
' *Customer Name :* '.$webEnquiry->Cus_name."\r\n".
' *Email :* '.$webEnquiry->Cus_email."\r\n".
' *Contact no. :* '.$webEnquiry->Cus_mob."\r\n".
' *Customer Message. :* '.$webEnquiry->Cus_msg."\r\n".
' Thanking You,'."\r\n".
' ACL Stanlay Team'."\r\n".
' Helpline: 011-41406926'."\r\n".
' Web : www.stanlay.in'."\r\n".
'------------------------------------------------------------'."".
' ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
 whatsapp_msg($acc_manager_phone,$message_ac_manager);

/////////////whatsapp ends//////////	

    // Return the response
    return response()->json([
        'success' => true,
        'enquiry_data' => $result
    ]);
}

public function locationWiseEnquiries(Request $request)
{
    $acc_manager = $request->input('acc_manager');
    $financial_year = $request->input('financial_year'); // Expected format: YYYY-YYYY
    $country = $request->input('country');
    $state = $request->input('state');
    $city = $request->input('city');

    // Determine financial year start and end dates
    if ($financial_year) {
        [$startYear, $endYear] = explode('-', $financial_year);
    } else {
        $currentYear = date('Y');
        $startYear = (date('m') >= 4) ? $currentYear : $currentYear - 1;
        $endYear = $startYear + 1;
    }
    
    $start_date = "$startYear-04-01";
    $end_date = "$endYear-03-31";

    // Build query
    $query = DB::table('tbl_web_enq_edit as enq_edit')
        ->join('all_cities as city', 'enq_edit.city', '=', 'city.city_id')
        ->join('tbl_zones as state', 'enq_edit.state', '=', 'state.zone_id')
        ->join('tbl_country as country', 'enq_edit.country', '=', 'country.country_id')
        ->leftJoin('tbl_web_enq as web_enq', 'enq_edit.enq_id', '=', 'web_enq.ID')
        ->selectRaw('city.city_name AS city, city.latitude, city.longitude,
            country.country_name AS country, state.zone_name AS state,
            COUNT(DISTINCT web_enq.ID) AS enquiries,
            COUNT(DISTINCT CASE WHEN enq_edit.acc_manager != 0 THEN enq_edit.id END) AS assign_count,
            COUNT(DISTINCT CASE WHEN enq_edit.acc_manager != 0 AND enq_edit.lead_id != 0 THEN enq_edit.id END) AS offer_count,
            COUNT(DISTINCT CASE WHEN enq_edit.acc_manager != 0 AND enq_edit.order_id != 0 THEN enq_edit.id END) AS sales_count')
        ->where('web_enq.deleteflag', 'active')
        ->where('enq_edit.deleteflag', 'active')
        ->whereBetween('enq_edit.Enq_Date', [$start_date, $end_date]);

    // Apply filters
    if ($acc_manager) $query->where('enq_edit.acc_manager', $acc_manager);
    if ($country) $query->where('country.country_id', $country);
    if ($state) $query->where('state.zone_id', $state);
    if ($city) $query->where('city.city_id', $city);

    // Grouping based on filters
    if ($city && $state && $country) {
        $query->groupBy('city.city_name');
    } elseif ($state && $country) {
        $query->groupBy('city.city_name');
    } elseif ($country) {
        $query->groupBy('state.zone_name');
    } else {
    //    $query->groupBy('country.country_name');
 $query->groupBy('city.city_name');
    }


    $queryResults = $query->get();

    // Format response
    $responseData = $queryResults->map(function ($item) use ($city, $state, $country) {
        $location = [$item->latitude, $item->longitude];

        return [
            'location' => $location,
            'country' => $item->country,
            'state' => $item->state,
            'city' => $item->city,
            'enquiries' => (int) $item->enquiries,
            'assign_count' => (int) $item->assign_count,
            'offer_count' => (int) $item->offer_count,
            'sales_count' => (int) $item->sales_count,
            'assigned_per' => $this->calculatePercentage($item->assign_count, $item->enquiries),
            'offer_per' => $this->calculatePercentage($item->offer_count, $item->enquiries),
            'sales_per' => $this->calculatePercentage($item->sales_count, $item->enquiries)
        ];
    });

    return response()->json($responseData);
}


private function calculatePercentage($count, $total)
{
    return ($total > 0) ? round(($count / $total) * 100, 2) : 0;
}


}
