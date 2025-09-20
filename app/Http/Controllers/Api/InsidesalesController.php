<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InsidesalesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   #############################################Leads#############################################



public function inside_sales_listing_olddddd(Request $request)
{
    $todays_enq_date = date("Y-m-d");
    $alarm_1_days = fetchAlarm_config("alarm_1");
    $alarm_2_days = fetchAlarm_config("alarm_2");
    $alarm_3_days = fetchAlarm_config("alarm_3");

    $page = $request->pageno ?? 1;
    $max_results = $request->records ?? 100;
    $from = (($page * $max_results) - $max_results);
    $from = $from < 0 ? 0 : $from;

    $query = DB::table('tbl_web_enq')
        ->leftJoin('tbl_web_enq_edit', 'tbl_web_enq_edit.enq_id', '=', 'tbl_web_enq.ID')
        ->leftJoin('tbl_lead', 'tbl_web_enq_edit.lead_id', '=', 'tbl_lead.id')
        ->leftJoin('tbl_comp', 'tbl_comp.id', '=', 'tbl_lead.comp_name')
        ->leftJoin('tbl_company_extn', 'tbl_company_extn.company_extn_id', '=', 'tbl_comp.co_extn_id')
        ->leftJoin('tbl_order', 'tbl_order.edited_enq_id', '=', 'tbl_web_enq_edit.ID')
        ->leftJoin('tbl_application', 'tbl_application.application_id', '=', 'tbl_web_enq_edit.product_category')
        ->leftJoin('tbl_enq_source', 'tbl_enq_source.enq_source_description', '=', 'tbl_web_enq.ref_source')
        ->select(
            DB::raw('CASE WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) THEN CONCAT(tbl_comp.comp_name, " ", tbl_company_extn.company_extn_name) ELSE tbl_comp.comp_name END AS company_full_name'),
            'tbl_web_enq.ID as enq_id',
            'tbl_web_enq.page_link',
            'tbl_web_enq.Cus_name',
            'tbl_web_enq.Cus_email',
            'tbl_web_enq.Cus_mob',
            'tbl_web_enq.page_name',
            'tbl_web_enq.Cus_msg',
            'tbl_web_enq.ref_source',
            'tbl_web_enq_edit.product_category',
            'tbl_web_enq.Enq_Date',
            'tbl_web_enq.enq_remark',
            'tbl_web_enq.added_by_acc_manager',
            'tbl_web_enq.deleteflag',
            'tbl_web_enq_edit.ID as enq_id_edited',
            'tbl_web_enq_edit.lead_id',
            'tbl_web_enq_edit.order_id',
            'tbl_web_enq_edit.enq_stage',
            'tbl_web_enq_edit.enq_type',
            'tbl_web_enq_edit.remind_me',
            'tbl_web_enq_edit.acc_manager',
            'tbl_enq_source.enq_source_name',
            'tbl_web_enq_edit.price_type',
            'tbl_web_enq_edit.hot_enquiry',
            'tbl_web_enq_edit.old_enq_date',
            'tbl_web_enq_edit.hot_productnote',
            'tbl_web_enq_edit.hot_productnoteother',
            'tbl_web_enq.dead_duck',
            'tbl_web_enq_edit.enq_remark_edited',
            'tbl_web_enq_edit.mel_updated_on',
            DB::raw('DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq'),
            DB::raw('DATEDIFF(CURDATE(), tbl_lead.time_lead_added) AS days_since_lead'),
            DB::raw('DATEDIFF(tbl_order.time_ordered, tbl_web_enq_edit.Enq_Date) AS days_since_offer'),
            'tbl_web_enq_edit.city',
            'tbl_web_enq_edit.state',
            'tbl_web_enq_edit.ref_source',
            'tbl_web_enq_edit.cust_segment',
            'tbl_application.application_name',
            'tbl_lead.time_lead_added',
            'tbl_lead.comp_person_id',
            'tbl_lead.lead_desc',
            'tbl_order.customers_id',
            'tbl_order.date_ordered',
            'tbl_order.total_order_cost',
            'tbl_order.ensure_sale_month',
            'tbl_order.ensure_sale_month_date',
            'tbl_order.order_by',
            'tbl_order.orders_id',
            'tbl_order.hot_offer',
            'tbl_order.offer_type'
        );

    $filters = [
        ['tbl_web_enq.deleteflag', '=', 'active'],
    ];

    foreach ($filters as $filter) {
        $query->where(...$filter);
    }

    $optionalFilters = [
        ['tbl_web_enq.ID', $request->enq_id],
        ['tbl_web_enq_edit.hot_enquiry', $request->hot_enquiry],
        ['tbl_web_enq_edit.enq_stage', $request->enq_stage],
        ['tbl_web_enq_edit.enq_type', $request->enq_type !== '-1' ? $request->enq_type : null],
        ['tbl_web_enq.ref_source', $request->ref_source !== 'all' ? $request->ref_source : null],
        ['tbl_web_enq.dead_duck', $request->dead_duck !== '-1' ? $request->dead_duck : null],
    ];

    foreach ($optionalFilters as [$column, $value]) {
        if (!is_null($value)) {
            $query->where($column, '=', $value);
        }
    }

    if ($request->lead_assigned_search === '1') {
        $query->whereNotNull('tbl_web_enq_edit.ID');
    } elseif ($request->lead_assigned_search === '0') {
        $query->whereNull('tbl_web_enq_edit.ID');
    }

    if ($request->todays_enq) {
        $query->whereDate('tbl_web_enq.Enq_Date', $todays_enq_date);
    }

    if ($request->overdue) {
        $query->where('tbl_web_enq.Enq_Date', '>=', DB::raw('DATE_SUB(CURDATE(), INTERVAL ' . $alarm_3_days . ' DAY)'))
              ->where('tbl_web_enq.Enq_Date', '<', DB::raw('CURDATE()'));
    }

    if ($request->search_by) {
        $search = $request->search_by;
        $query->where(function($subquery) use ($search) {
            $subquery->where('tbl_web_enq_edit.Cus_name', 'like', "%$search%")
                     ->orWhere('tbl_web_enq_edit.Cus_email', 'like', "%$search%")
                     ->orWhere('tbl_web_enq_edit.Cus_mob', 'like', "%$search%");
        });
    }

    if ($request->date_from && $request->date_to) {
        $query->whereBetween(DB::raw('DATE(tbl_web_enq.Enq_Date)'), [$request->date_from, $request->date_to]);
    }

    $query->offset($from)
          ->limit($max_results)
          ->orderBy('tbl_web_enq.ID', 'desc');

$data = $query->get();
$count = $query->count();

$offers = $data->map(function ($row) {
    $tasks_details_array = 0;
    $product_items_details = 0;
    $case_duration_as_per_segment = 0;
    $case_duration_of_this_customer = 0;

    $lead_id = $row->lead_id;
    $orders_id = $row->order_id;
    $enq_type = $row->enq_type;

    $cust_segment = $row->cust_segment;
    $cust_segment_name = lead_cust_segment_name($cust_segment);

    if (!empty($lead_id)) {
        $comp_person_id = get_comp_person_id_by_lead_id($lead_id);
        $app_cat_id = get_app_cat_id_by_lead_id($lead_id);
        $application_name = application_name($app_cat_id);
        $comp_ids = comp_name_by_lead_id($lead_id);
        $company_full_name = company_names($comp_ids);
    } else {
        $comp_person_id = 0;
        $app_cat_id = "0";
        $application_name = $enq_type != 'service'
            ? application_name($row->product_category)
            : service_application_name($row->product_category);
        $company_full_name = "0";
    }

    $sales_cycle_duration = case_duration_as_per_segment($cust_segment);
    $enq_source_name = enq_source_name($row->ref_source);
    $acc_manager_name = admin_name($row->acc_manager);
    $acc_manager_designation_id = "7";
    $acc_manager_designation_name = designation_name("7");

    if (!empty($orders_id)) {
        $days_since_offer = $row->days_since_offer ?? 5;
        $product_items_details = product_name_generated_with_quantity_json_tbl_order_product_listing_new_without_json_stringfy($orders_id);
        $customers_id = get_customers_id_by_order_id($orders_id);
        $offer_details = offer_details($orders_id);
        $pi_id = performa_invoice_id($orders_id);
        $performa_invoice_details = performa_invoice_details($pi_id);
        $company_full_name = company_names($customers_id);
        $customer_sales_cycle_duration = case_duration_of_this_customer($customers_id);
        $tasks_details_array = getTaskList($orders_id);

        if (abs($days_since_offer) < $customer_sales_cycle_duration || abs($days_since_offer) < $sales_cycle_duration) {
            $track_image_tooltip = "On Track";
            $track_image = "ontrack.png";
        } elseif (abs($days_since_offer + 30) < $customer_sales_cycle_duration || abs($days_since_offer + 30) < $sales_cycle_duration) {
            $track_image_tooltip = "CAUTION";
            $track_image = "ontrack-1.png";
        } elseif (abs($days_since_offer) > $customer_sales_cycle_duration || abs($days_since_offer) > $sales_cycle_duration) {
            $track_image_tooltip = "High Risk";
            $track_image = "ontrack-2.png";
        } else {
            $track_image_tooltip = "NIA";
            $track_image = "ontrack.png";
        }
    } else {
        $product_items_details = [];
        $days_since_offer = 0;
        $customers_id = 0;
        $offer_details = [];
        $pi_id = 0;
        $performa_invoice_details = [];
        $customer_sales_cycle_duration = 0;
        $tasks_details_array = [];
        $track_image_tooltip = "On Track";
        $track_image = "ontrack.png";
    }

    return [
        'ID' => $row->enq_id,
        'enq_id' => $row->enq_id_edited,
        'order_id' => $row->order_id,
        'lead_id' => $lead_id,
        'application_name' => $application_name,
        'cust_segment_name' => $cust_segment_name,
        'enq_source_name' => $enq_source_name,
        'admin_fname' => $acc_manager_name,
        'acc_manager_designation_name' => $acc_manager_designation_name,
        'company_full_name' => $company_full_name,
        'days_since_offer' => $row->days_since_offer,
        'product_items_details' => $product_items_details,
        'performa_invoice_details' => $performa_invoice_details,
        'offer_data' => $offer_details,
        'offer_task_details' => $tasks_details_array,
        'sales_cycle_duration' => $sales_cycle_duration,
        'customer_sales_cycle_duration' => $customer_sales_cycle_duration,
        'track_image_tooltip' => $track_image_tooltip,
        'track_image' => $track_image,
        // include other $row properties as needed
    ];
});

//return ['count' => $count, 'data' => $offers];
$num_rows_enq_assigned_today="0";
return response()->json([ 
			'enquiry_data' => $offers,
			'export_enquiry_data' => $offers,
			'num_rows_count' => $count,
			'assigned_today' => $num_rows_enq_assigned_today
		]);
		
}



public function inside_sales_listing(Request $request)
    {
$todays_enq_date		=	date("Y-m-d");	   
	   
"<br>".		  $alarm_1_days=fetchAlarm_config("alarm_1");
"<br>".		  $alarm_2_days=fetchAlarm_config("alarm_2");
"<br>".		  $alarm_3_days=fetchAlarm_config("alarm_3");


			$today_filter				= date("Y-m-d");
			$today_filter_date_end		= date('Y-m-d',strtotime($today_filter . ' -1 day'));
			$today_filter_date_end3		= date('Y-m-d',strtotime($today_filter . ' -3 day'));
			$today_filter_date_end5		= date('Y-m-d',strtotime($today_filter . ' -5 day'));
	   		$acc_manager_request        = $request->acc_manager;
			$todays_enq        			= $request->todays_enq;
			$added_by_acc_manager       = $request->added_by_acc_manager;
			$lead_assigned_search 		= $request->lead_assigned_search;
			$AdminLoginID_SET			= $request->AdminLoginID_SET;
		    $admin_role_id				= $request->admin_role_id;
			$data_action				= $request->action;
			$OrderNo  					= $request->order_no;
			$overdue					= $request->overdue;
            $pcode						= $request->pcode;
			$search_by					= $request->search_by;
			$enq_id_search				= $request->enq_id;
            $order 						= $request->order;
            $cust_segment				= $request->cust_segment; 
            $sort_by	 				= $request->sort_by;
            $lead_created				= $request->lead_created;
			$offer_created				= $request->offer_created;
            $follow_up_order_by			= $request->follow_up_order_by;
            $offer_probability_search	= $request->enq_stage;
            $last_updated_on			= $request->last_updated_on;
            $app_cat_id_search			= $request->product_category;
            $hvc_search					= $request->hvc_search;
			$deleted_enquiries			= $request->deleted_enquiries;
            $hot_enquiry_search			= $request->hot_enquiry;	
//          $enq_id_search				= $request->enq_id_search;
            $enq_status					= $request->dead_duck;
			$hvc_search_filter			= $request->hvc_search_filter;
			
			
			
			
		    $enquiry_responded 			= $request->enquiry_responded;			
			
			
			
			if($enquiry_responded=='1')
			{
			$search_from 				= $today_filter_date_end;//$request->date_from;
		   $datepicker_to 				= $today_filter_date_end;	
			$lead_created				= "No";
			}
		else	if($enquiry_responded=='3')
			{
			$search_from 				= $today_filter_date_end3;//$request->date_from;
		    $datepicker_to 				= $today_filter_date_end3;	
			$lead_created				= "No";			
			}

			else if($enquiry_responded=='5')
			{
		 	$search_from 				= "";//$request->date_from;
		     $datepicker_to 			= "";//$today_filter_date_end5;	
			$lead_created				= "No";
			}
			


			else if($enquiry_responded=='0')
			{
			$search_from 				= "";//$today_filter_date_end;//$request->date_from;
		    $datepicker_to 				= "";//$today_filter;	
            $lead_created				= "Yes";
			}
			else
			{
			$search_from 				= $request->date_from;
		    $datepicker_to 				= $request->date_to;	
			 $lead_created				= "";	
			}
			
			
$date_range_search_enq_responded = "";

if ($enquiry_responded == '1') {
    // Not Responded <= 1 day
    $date_range_search_enq_responded = " AND tbl_web_enq_edit.lead_id = '0' AND DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) <= 1";
} 
else if ($enquiry_responded == '3') {
    // Not Responded <= 3 days
//    $date_range_search_enq_responded = " AND tbl_web_enq_edit.lead_id = '0' AND DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) <= 3";
	$date_range_search_enq_responded = " AND tbl_web_enq_edit.lead_id = '0' AND DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) > 1 AND DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) <= 3";
} 
else if ($enquiry_responded == '5') {
    // Not Responded >= 5 days
    $date_range_search_enq_responded = " AND tbl_web_enq_edit.lead_id = '0' AND DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) >= 5";
} 
else if ($enquiry_responded == 'responded') {
    // Inquiry responded
    $date_range_search_enq_responded = " AND tbl_web_enq_edit.lead_id != '0'";
}			
			
			$enq_type 					= $request->enq_type;
			//$ref_source_request	= $request->ref_source_request;
			/*if($enq_status=='' && $enq_status!='-1')
				{
					$enq_status='0';
				}*/
			$ref_source_request			= $request->ref_source;
			$lead_created_search		= "";
			$offer_created_search		= "";
/*$ref_source_search						= $request->ref_source_search;
$date_range_search						= $request->date_range_search;
//$enq_status_search= $request->enq_status_search;
$app_cat_id_search_search				= $request->app_cat_id_search_search;
$offer_probability_search_search		= $request->offer_probability_search_search;
$last_updated_on_search					= $request->last_updated_on_search;
$hot_enquiry_search_search				= $request->hot_enquiry_search_search;
*/
	if(!isset($request->pageno))
	{ 
    	$page = 1; 
	} 
	else 
	{ 
    	$page = $request->pageno; 
	} 
	if(!isset($request->records))
	{ 
    	$max_results = 100; 
	} 
	else 
	{ 
    	$max_results = $request->records; 
	} 
	@$from = (($page * $max_results) - $max_results);  
	if($from<0)
	{
		$from="0";
	}

if($enq_id_search!='' && $enq_id_search!='0')
{
//	$enq_id_search_filter=" and tbl_web_enq_edit.ID = '$enq_id_search' ";
$enq_id_search_filter=" and tbl_web_enq.ID = '$enq_id_search' ";
}

else
{
		$enq_id_search_filter="  ";
}


if($lead_assigned_search!='')
	{
		
		if($lead_assigned_search=='1')//yes is not null
		{
$search_lead_assigned_search=" AND tbl_web_enq_edit.ID IS NOT NULL  ";
		}
		else if($lead_assigned_search=='0')//no is null
		{
$search_lead_assigned_search=" AND tbl_web_enq_edit.ID IS NULL  ";
		}

else
{
	$search_lead_assigned_search=" ";
}
	}
	else
	{
	$search_lead_assigned_search=" ";		
	}
	



if($overdue!='' && $overdue!='0')
{
//	$enq_id_search_filter=" and tbl_web_enq_edit.ID = '$enq_id_search' ";
$overdue_filter=" AND tbl_web_enq.Enq_Date >= DATE_SUB(CURDATE(), INTERVAL $alarm_3_days DAY) AND tbl_web_enq.Enq_Date < CURDATE()";
}

else
{
$overdue_filter="  ";
}



	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword=" AND tbl_web_enq_edit.Cus_name like '%".$search_by."%' OR tbl_web_enq_edit.Cus_email like '%".$search_by."%' OR tbl_web_enq_edit.Cus_mob like '%".$search_by."%' ";
	}

	else
	{
	$search_by_keyword="";
	}

	 	
	if($search_from!='' && $datepicker_to!='')
	{
		
$date_range_search=" AND (date( tbl_web_enq.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}
 
	if($todays_enq!='' && $todays_enq!='0')
	{
		
$date_range_search_today_assigned=" AND DATE(tbl_web_enq.Enq_Date) = '$todays_enq_date'"; // -- Filters for current date only
	}
	else
	{
$date_range_search_today_assigned=" "; // -- Filters for current date only
	}	
	
	
	
	
$date_range_search_today_assigned_s=" AND DATE(tbl_web_enq.Enq_Date) = '$todays_enq_date'"; // -- Filters for current date onlycurrent date only

if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq.dead_duck='$enq_status'";
	
}
else
{
	$enq_status_search="";
}

if($enq_type!='' && $enq_type!='-1')
{
	
	$enq_type_search=" AND tbl_web_enq_edit.enq_type='$enq_type'";

}
else
{
	$enq_type_search="";
}


if($deleted_enquiries=='inactive' )
{
	
	$deleted_enquiries_search=" AND tbl_web_enq.deleteflag='$deleted_enquiries'";

}
else
{
$deleted_enquiries_search=" AND tbl_web_enq.deleteflag='active'";
}




if($offer_probability_search!='' && $offer_probability_search!='0')
{
	
	$offer_probability_search_search=" AND tbl_web_enq_edit.enq_stage='$offer_probability_search'";
}
else
{
	$offer_probability_search_search="";
}


if($hot_enquiry_search!='' )
{
	
	$hot_enquiry_search_search=" AND tbl_web_enq_edit.hot_enquiry='$hot_enquiry_search'";
	$hot_offer_search_search=" AND tbl_order.hot_offer='$hot_enquiry_search'";
}
else
{
	$hot_enquiry_search_search="";
	$hot_offer_search_search=" ";
}

if($last_updated_on!='' && $last_updated_on!='0' && $last_updated_on=='uil15d')
{
	
	$last_updated_on_search=" and tbl_web_enq_edit.mel_updated_on > now() - INTERVAL 15 day";
}
else if($last_updated_on=='nuil15d')
{
	$last_updated_on_search=" and tbl_web_enq_edit.mel_updated_on > now() - INTERVAL 115 day";
}

	else
	{
		$last_updated_on_search="";
	}


if($cust_segment!='')
{
	
	$cust_segment_search=" AND tbl_web_enq_edit.cust_segment='$cust_segment'";

}
else
{
	$cust_segment_search="";
}


if($app_cat_id_search!='')
{
	
	$app_cat_id_search_search=" AND tbl_web_enq_edit.product_category='$app_cat_id_search'";

}
else
{
	$app_cat_id_search_search="";
}

//echo "RUMIT".$lead_created;
if($lead_created!='')
{
	if($lead_created=='Yes')
	{
	$lead_created_search=" AND tbl_web_enq_edit.lead_id!='0'";
	}
	
	else if($lead_created=='No')
	{
	$lead_created_search=" AND tbl_web_enq_edit.lead_id='0'";
	}
}
else
{
	$lead_created_search="";
}
$lead_created_search;

if($offer_created!='')
{
	if($offer_created=='Yes')
	{
	$offer_created_search=" AND tbl_web_enq_edit.order_id!='0'";
	}
	
	else if($offer_created=='No')
	{
	$offer_created_search=" AND tbl_web_enq_edit.order_id='0'";
	}
}
else
{
	$offer_created_search="";
}

if($sort_by!='')
{
if($sort_by=='date_asc')
{
	$order_by="tbl_web_enq_edit.Enq_Date";
	$order="asc";
}
if($sort_by=='date_desc')
{
		$order_by="tbl_web_enq_edit.Enq_Date";
		$order="desc";
}

}
else
{
	$order_by="tbl_web_enq.Enq_Date";
	$order="desc";
}

if($acc_manager_request!='' && $acc_manager_request!='0')
	{
	$acc_manager_search="and tbl_web_enq_edit.acc_manager IN ($acc_manager_request) ";
	}
	else
	{
		$acc_manager_search=" ";
	}

//echo $added_by_acc_manager;
if($added_by_acc_manager!='' && $added_by_acc_manager!='0')
	{
	$added_by_acc_manager_search=" and tbl_web_enq.added_by_acc_manager IN ($added_by_acc_manager) ";
	}
	else
	{
		$added_by_acc_manager_search=" ";
	}



	if($ref_source_request!='' && $ref_source_request!='0')
	{
	$ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
	}
	else
	{
		$ref_source_search=" ";
	}		

if($sort_by!='')
{
if($sort_by=='date_asc')
{
	$order_by="tbl_web_enq_edit.Enq_Date";
	$order="asc";
}
if($sort_by=='date_desc')
{
		$order_by="tbl_web_enq_edit.Enq_Date";
		$order="desc";
}

}
else
{
//	$order_by="tbl_web_enq_edit.Enq_Date";
	$order_by="tbl_web_enq.Enq_Date";
	$order="desc";
}

if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  tbl_web_enq_edit.order_id = '$OrderNo'";
	}
else
{
	$order_no_search="";
}
		 
$searchRecord				= $search_lead_assigned_search.$date_range_search_enq_responded.$added_by_acc_manager_search.$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$deleted_enquiries_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$hot_offer_search_search.$search_by_keyword.$offer_created_search.$overdue_filter.$date_range_search_today_assigned.$order_no_search;//"and acc_manager=$acc_manager_lead";
	
$searchRecordtodayassigned	= $added_by_acc_manager_search.$date_range_search_enq_responded.$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search_today_assigned_s.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$deleted_enquiries_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$search_by_keyword.$offer_created_search.$overdue_filter;//"and acc_manager=$acc_manager_lead";


 $sql="SELECT
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name)
        ELSE tbl_comp.comp_name
    END AS company_full_name,
    tbl_web_enq.ID as 'enq_id', 
    tbl_web_enq.page_link, 
    tbl_web_enq.Cus_name,
    tbl_web_enq.Cus_email,
    tbl_web_enq.Cus_mob,
    tbl_web_enq.page_name,
    tbl_web_enq.Cus_msg,
    tbl_web_enq.ref_source,
	tbl_web_enq_edit.cust_segment,
	tbl_lead.cust_segment as offer_cust_segment,	
    tbl_web_enq_edit.product_category,
    tbl_web_enq.Enq_Date,
	tbl_web_enq_edit.Enq_Date as enq_assigned_date,
    tbl_web_enq.enq_remark,
    tbl_web_enq.added_by_acc_manager,
    tbl_web_enq.deleteflag,
    tbl_web_enq_edit.ID as 'enq_id_edited',
    tbl_web_enq_edit.lead_id,
    tbl_web_enq_edit.order_id,
    tbl_web_enq_edit.enq_stage,
    tbl_web_enq_edit.enq_type,
    tbl_web_enq_edit.remind_me,
    tbl_web_enq_edit.acc_manager,
    tbl_enq_source.enq_source_name,
    tbl_web_enq_edit.price_type,
    tbl_web_enq_edit.hot_enquiry,
    tbl_web_enq_edit.old_enq_date,
    tbl_web_enq_edit.hot_productnote,
    tbl_web_enq_edit.hot_productnoteother,
    tbl_web_enq.dead_duck,
    tbl_web_enq_edit.enq_remark_edited,
    tbl_web_enq.enq_remark,
    tbl_web_enq_edit.mel_updated_on,
    DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
    DATEDIFF(CURDATE(), tbl_lead.time_lead_added) AS days_since_lead,
    DATEDIFF(tbl_order.time_ordered, tbl_web_enq_edit.Enq_Date) AS days_since_offer, 
    tbl_web_enq_edit.city,
	tbl_web_enq_edit.country,
    tbl_web_enq_edit.state,
    tbl_web_enq_edit.ref_source,
    tbl_web_enq_edit.cust_segment,
    tbl_application.application_name,
    tbl_lead.time_lead_added,
    tbl_lead.comp_person_id,
    tbl_lead.lead_desc,
    tbl_order.customers_id,
    tbl_order.date_ordered,
	tbl_order.follow_up_date,
    tbl_order.total_order_cost,
    tbl_order.ensure_sale_month,
    tbl_order.ensure_sale_month_date,
    tbl_order.order_by,
    tbl_order.orders_id,
    tbl_order.hot_offer,
    tbl_order.offer_type
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_lead ON tbl_web_enq_edit.lead_id = tbl_lead.id 
LEFT JOIN tbl_comp ON tbl_comp.id = tbl_lead.comp_name 
LEFT JOIN tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id 

LEFT JOIN tbl_order ON tbl_order.edited_enq_id= tbl_web_enq_edit.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source 



 
WHERE 
  1=1
$searchRecord
 order by $order_by  $order  LIMIT $from, $max_results";	 //exit;
 
//$result_enq =  DB::select(($sql));

/*************changes done 15-03-2025**************************/

$sql_offer 						= collect(DB::select($sql)); // Convert to Collection
$tasks_details_array			= 0;
$product_items_details			= 0;
$case_duration_as_per_segment	= 0;
$case_duration_of_this_customer	= 0;

$offers 						= $sql_offer->map(function ($row) {
$lead_id						= $row->lead_id;
$orders_id						= $row->order_id;
$enq_type						= $row->enq_type;

//$cust_segment					= $row->cust_segment;//lead_cust_segment($lead_id);	

if(!empty($orders_id))
{
	$cust_segment					= $row->offer_cust_segment;//lead_cust_segment($lead_id);	
}
else
{
		$cust_segment					= $row->cust_segment;//lead_cust_segment($lead_id);	
}
$cust_segment_name				= lead_cust_segment_name($cust_segment);
if(!empty($lead_id))
{
$comp_person_id					= get_comp_person_id_by_lead_id($lead_id);
$app_cat_id						= get_app_cat_id_by_lead_id($lead_id);
$application_name				= application_name($app_cat_id);
//$lead_data						= get_lead_details($lead_id);
$comp_ids						= comp_name_by_lead_id($lead_id);
$company_full_name				= company_names($comp_ids);

}
else
{
$comp_person_id					= 0;//get_comp_person_id_by_lead_id($lead_id);
$app_cat_id						= "0";//get_app_cat_id_by_lead_id($lead_id);

if($enq_type!='service')
{
$application_name				= application_name($row->product_category);
}
else
{
	$application_name			= service_application_name($row->product_category);
}
//$lead_data						= [];
$company_full_name				= "0";
}
$sales_cycle_duration			= case_duration_as_per_segment($cust_segment);
$enq_source_name				= enq_source_name($row->ref_source);	
$acc_manager_name				= admin_name($row->acc_manager);
$acc_manager_designation_id		= "7";//account_designation_id($row->acc_manager);
$acc_manager_designation_name	= designation_name("7");//account_designation_id($row->acc_manager);

	


//echo  $row->admin_name;

if(!empty($orders_id))
{
$days_since_offer				= "5";//$row->days_since_offer;
$product_items_details 			= product_name_generated_with_quantity_json_tbl_order_product_listing_new_without_json_stringfy($orders_id);
$customers_id					= get_customers_id_by_order_id($orders_id);
$offer_details					= offer_details($orders_id);
$pi_id							= performa_invoice_id($orders_id);
$performa_invoice_details		= performa_invoice_details($pi_id);
$company_full_name				= company_names($customers_id);
$customer_sales_cycle_duration	= case_duration_of_this_customer($customers_id);
//$tasks_details_array 			= getTaskList($orders_id);

$tasks_details_array 			= getTaskList($orders_id);

if (empty($tasks_details_array)) {
    $followUpDate = \Carbon\Carbon::parse($row->follow_up_date);
    $now = \Carbon\Carbon::now();

    if ($followUpDate->lt($now)) {
        $daysOverdue = round($followUpDate->diffInHours($now) / 24);
        $overdueStatus = "$daysOverdue days overdue";
    } else {
        $overdueStatus = 'On Track';
    }

    $tasks_details_array = [
        [
            'events_id' => null,
            'events_title' => 'Follow-up Reminder',
            'start_event' => $row->follow_up_date,
            'end_event' => $row->follow_up_date,
            'status' => 'Pending',
            'order_id' => $row->orders_id,
            'evttxt' => 'TFU',
            'fallback' => true,
            'note' => 'No task found, using follow_up_date from order',
            'overdue' => $overdueStatus,
        ]
    ];
} else {
    $tasks_details_array= $tasks_details_array;
}


if((abs($days_since_offer) < $customer_sales_cycle_duration) || (abs($days_since_offer) < $sales_cycle_duration  ))
{
//	if(abs($days_since_offer) < $sales_cycle_duration  )
$track_image_tooltip			= "On Track";
$track_image					= "ontrack.png";
}

else if(  (abs($days_since_offer+30) < $customer_sales_cycle_duration) || (abs($days_since_offer+30) < $sales_cycle_duration  ))
{
//	if(abs($days_since_offer+30) < $sales_cycle_duration  )
$track_image_tooltip			= "CAUTION";
$track_image					= "ontrack-1.png";
}

else if(  (abs($days_since_offer) > $customer_sales_cycle_duration) || (abs($days_since_offer) > $sales_cycle_duration  ))
{
//		if(abs($days_since_offer+30) < $sales_cycle_duration  )
$track_image_tooltip				= "High Risk";
$track_image						= "ontrack-2.png";
}

else { 
$track_image_tooltip				="NIA";
$track_image						="ontrack.png";
}

}
else
{
	$product_items_details 			= [];
	$days_since_offer				= "0";//$row->days_since_offer;
	$customers_id					= "0";
	$offer_details					= [];//offer_details($orders_id);
	$pi_id							= "0";//performa_invoice_id($orders_id);
	$performa_invoice_details		= [];//performa_invoice_details($pi_id);
	$company_full_name				= "0";//company_names($customers_id);
	$customer_sales_cycle_duration	= "0";//case_duration_of_this_customer($customers_id);
	$tasks_details_array 			= [];//getTaskList($orders_id);	
	$track_image_tooltip			= "On Track";
	$track_image					="ontrack.png";
//	$company_full_name				= $company_full_name;

}


return [
'ID' =>$row->enq_id,
'enq_id' =>$row->enq_id_edited,
'order_id' =>$row->order_id,
'price_type' =>$row->price_type,
'hot_enquiry' =>$row->hot_enquiry,
'lead_id' =>$row->lead_id,
'Cus_name' =>$row->Cus_name,
'Cus_email' =>$row->Cus_email,
'page_link' =>$row->page_link,
'enq_type' =>$row->enq_type,
'Cus_mob' =>$row->Cus_mob,
'Cus_msg' =>$row->Cus_msg,
'city' =>$row->city,
'state' =>$row->state,
'remind_me' =>$row->remind_me,
'ref_source' =>$row->ref_source,
'cust_segment' =>$row->cust_segment,
'acc_manager_designation_name' =>$acc_manager_designation_name,
'Enq_Date' =>$row->Enq_Date,
'Enq_assigned_date' =>$row->enq_assigned_date,
'old_enq_date' =>$row->old_enq_date,  
'hot_productnote' =>$row->hot_productnote,
'hot_productnoteother' =>$row->hot_productnoteother,
'dead_duck' =>$row->dead_duck,
'enq_remark_edited' =>$row->enq_remark_edited,
'enq_remark' =>$row->enq_remark,
'acc_manager' =>$row->acc_manager,
'product_category' =>$row->product_category,
'deleteflag' =>$row->deleteflag,
'enq_stage' =>$row->enq_stage,
'enq_stage_name' =>enq_stage_name($row->enq_stage),
'mel_updated_on' =>$row->mel_updated_on,
'application_name' =>$application_name,
'cust_segment' =>$row->cust_segment,
'cust_segment_name' =>$cust_segment_name,
'enq_source_name' =>$enq_source_name,
'admin_fname' =>$acc_manager_name,
'company_full_name' =>$company_full_name,
'days_since_enq' => $row->days_since_enq,
'days_since_lead' => $row->days_since_lead,
'days_since_offer' => $row->days_since_offer,
'time_lead_added' => $row->time_lead_added,
'customer_comments' => $row->Cus_msg,
'date_ordered' => $row->date_ordered,
'total_order_cost' => $row->total_order_cost,
'ensure_sale_month' => $row->ensure_sale_month,
'ensure_sale_month_date' => $row->ensure_sale_month_date,
'orders_id' => $row->orders_id,
'comp_person_id' => $row->comp_person_id,
'customers_id' => $row->customers_id,
'lead_desc' => $row->lead_desc,
'offer_type' => $row->offer_type,
'admin_name' => admin_name($row->order_by),
'app_cat_id' => $app_cat_id,
//'comp_person_id' => $comp_person_id,
'company_full_name' => $row->company_full_name,
'price_type' => $row->price_type,
'ref_source' => $row->ref_source,
'cust_segment' => $row->cust_segment,
'country_name' => CountryName($row->country),
'country' => $row->country,
'state_name' => StateName($row->state),
'city_name' => CityName($row->city),
'acc_manager_name' => $acc_manager_name,
'enq_remark_edited' => $row->enq_remark_edited,
'orders_id' => $orders_id,
'lead_id' => $row->lead_id,
'proforma_invoice_id' => $pi_id,
"offer_data"=>$offer_details,
"product_items_details"=>$product_items_details,
"performa_invoice_details"=>$performa_invoice_details,
"offer_task_details"=>$tasks_details_array,
"sales_cycle_duration"=>$sales_cycle_duration,
"customer_sales_cycle_duration"=>$customer_sales_cycle_duration,
"track_image_tooltip"=>$track_image_tooltip,
"track_image"=>$track_image,
   ];
});
/**************changes ends ***********************************/


$result_enq 				= json_decode($offers);


  $sql_assigned_today="SELECT
tbl_web_enq.ID as 'enq_id', 
tbl_enq_source.enq_source_name,
tbl_application.application_name,
CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
where tbl_web_enq.mark_as_completed=0
and tbl_web_enq_edit.deleteflag='active'
$date_range_search_today_assigned_s
 AND tbl_web_enq_edit.ID IS NOT NULL 
 order by $order_by  $order  LIMIT $from, $max_results";	 //exit;
 
$result_enq_assigned_today =  DB::select(($sql_assigned_today));
$num_rows_enq_assigned_today	= count($result_enq_assigned_today); 	  

$sql_count = "SELECT COUNT(*) as total_count
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_lead ON tbl_web_enq_edit.lead_id = tbl_lead.id 
LEFT JOIN tbl_comp ON tbl_comp.id = tbl_lead.comp_name 
LEFT JOIN tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id 
LEFT JOIN tbl_order ON tbl_order.edited_enq_id= tbl_web_enq_edit.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source
WHERE tbl_web_enq.mark_as_completed = 0 
$searchRecord";
$total_rows_result = DB::select($sql_count);
$num_rows = $total_rows_result[0]->total_count ?? 0;  
$assigned_today						= "0";
	return response()->json([ 
			'enquiry_data' => $result_enq,
			'export_enquiry_data' => $result_enq,
			'num_rows_count' => $num_rows,
			'assigned_today' => $num_rows_enq_assigned_today
		]);
		
}







public function assigned_today_listing(Request $request)
    {
$todays_enq_date		=	date("Y-m-d");	   
	   
"<br>".		  $alarm_1_days=fetchAlarm_config("alarm_1");
"<br>".		  $alarm_2_days=fetchAlarm_config("alarm_2");
"<br>".		  $alarm_3_days=fetchAlarm_config("alarm_3");
	   		$acc_manager_request        = $request->acc_manager;
			$todays_enq        			= $request->todays_enq;
			$added_by_acc_manager       = $request->added_by_acc_manager;
			$lead_assigned_search 		= $request->lead_assigned_search;
			$AdminLoginID_SET			= $request->AdminLoginID_SET;
		    $admin_role_id				= $request->admin_role_id;
			$data_action				= $request->action;
			$overdue					= $request->overdue;
            $pcode						= $request->pcode;
			$search_by					= $request->search_by;
			$enq_id_search				= $request->enq_id;
            $order 						= $request->order;
            $cust_segment				= $request->cust_segment; 
            $sort_by	 				= $request->sort_by;
            $lead_created				= $request->lead_created;
			$offer_created				= $request->offer_created;
            $follow_up_order_by			= $request->follow_up_order_by;
            $offer_probability_search	= $request->enq_stage;
            $last_updated_on			= $request->last_updated_on;
            $app_cat_id_search			= $request->product_category;
            $hvc_search					= $request->hvc_search;
			$deleted_enquiries			= $request->deleted_enquiries;
            $hot_enquiry_search			= $request->hot_enquiry;	
//          $enq_id_search				= $request->enq_id_search;
            $enq_status					= $request->dead_duck;
			$hvc_search_filter			= $request->hvc_search_filter;
            $search_from 				= $request->date_from;
		    $datepicker_to 				= $request->date_to;
			
			$enq_type 					= $request->enq_type;
			//$ref_source_request	= $request->ref_source_request;
			if($enq_status=='' && $enq_status!='-1')
				{
					$enq_status='0';
				}
			$ref_source_request			= $request->ref_source;
			$lead_created_search		= "";
			$offer_created_search		= "";
/*$ref_source_search						= $request->ref_source_search;
$date_range_search						= $request->date_range_search;
//$enq_status_search= $request->enq_status_search;
$app_cat_id_search_search				= $request->app_cat_id_search_search;
$offer_probability_search_search		= $request->offer_probability_search_search;
$last_updated_on_search					= $request->last_updated_on_search;
$hot_enquiry_search_search				= $request->hot_enquiry_search_search;
*/
	if(!isset($request->pageno))
	{ 
    	$page = 1; 
	} 
	else 
	{ 
    	$page = $request->pageno; 
	} 
	if(!isset($request->records))
	{ 
    	$max_results = 100; 
	} 
	else 
	{ 
    	$max_results = $request->records; 
	} 
	@$from = (($page * $max_results) - $max_results);  
	if($from<0)
	{
		$from="0";
	}

 
 
	
$date_range_search_today_assigned_s=" AND DATE(tbl_web_enq.Enq_Date) = '$todays_enq_date'"; // -- Filters for current date onlycurrent date only

if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq.dead_duck='$enq_status'";
	
}
else
{
	$enq_status_search="";
}

 
if($sort_by!='')
{
if($sort_by=='date_asc')
{
	$order_by="tbl_web_enq_edit.Enq_Date";
	$order="asc";
}
if($sort_by=='date_desc')
{
		$order_by="tbl_web_enq_edit.Enq_Date";
		$order="desc";
}

}
else
{
	$order_by="ID";
	$order="desc";
}

if($acc_manager_request!='' && $acc_manager_request!='0')
	{
	$acc_manager_search="and tbl_web_enq_edit.acc_manager IN ($acc_manager_request) ";
	}
	else
	{
		$acc_manager_search=" ";
	}

//echo $added_by_acc_manager;
if($added_by_acc_manager!='' && $added_by_acc_manager!='0')
	{
	$added_by_acc_manager_search=" and tbl_web_enq.added_by_acc_manager IN ($added_by_acc_manager) ";
	}
	else
	{
		$added_by_acc_manager_search=" ";
	}



	if($ref_source_request!='' && $ref_source_request!='0')
	{
	$ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
	}
	else
	{
		$ref_source_search=" ";
	}		

if($sort_by!='')
{
if($sort_by=='date_asc')
{
	$order_by="tbl_web_enq.Enq_Date";
	$order="asc";
}
if($sort_by=='date_desc')
{
		$order_by="tbl_web_enq.Enq_Date";
		$order="desc";
}

}
else
{
	$order_by="tbl_web_enq.id";
	$order="desc";
}
			 

//$searchRecordtodayassigned	= $added_by_acc_manager_search.$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search_today_assigned_s.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$deleted_enquiries_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$hot_enquiry_search_search.$search_by_keyword.$offer_created_search.$overdue_filter;//"and acc_manager=$acc_manager_lead";








$date_range_search_today_assigned_s=" AND DATE(tbl_web_enq.Enq_Date) = '$todays_enq_date'"; // -- Filters for current date onlycurrent date only

if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq.dead_duck='$enq_status'";
	
}
else
{
	$enq_status_search="";
}



$sql="SELECT
tbl_web_enq.ID , 
tbl_web_enq_edit.ID as 'enq_id',
tbl_web_enq.Cus_name ,
tbl_web_enq.Cus_email ,
tbl_web_enq.Cus_mob ,
tbl_web_enq.mark_as_completed ,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq.page_link,
tbl_web_enq.page_name,
tbl_web_enq.Cus_msg ,
tbl_web_enq.ref_source ,
tbl_web_enq.product_category ,
tbl_web_enq.Enq_Date ,
tbl_web_enq.enq_remark ,
tbl_web_enq_edit.cust_segment,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq.dead_duck ,
tbl_web_enq.added_by_acc_manager ,
tbl_web_enq.deleteflag ,
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.order_id,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.country,
tbl_web_enq_edit.city,
tbl_web_enq_edit.state,
tbl_web_enq_edit.address,
tbl_web_enq_edit.acc_manager,
tbl_enq_source.enq_source_name,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
tbl_country.country_name as country_name, 
tbl_zones.zone_name as state_name, 
all_cities.city_name as city_name,
d.designation_name,
tbl_admin.admin_email,
tbl_admin.admin_telephone,
CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq.product_category    
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager

LEFT JOIN tbl_country     ON tbl_country.country_id = tbl_web_enq_edit.country
LEFT JOIN tbl_zones     ON tbl_zones.zone_id = tbl_web_enq_edit.state 
LEFT JOIN all_cities    ON all_cities.city_id = tbl_web_enq_edit.city 



INNER JOIN tbl_designation d on d.designation_id = tbl_admin.admin_designation
where   tbl_web_enq_edit.deleteflag='active' and tbl_web_enq_edit.ID IS NOT NULL 
$date_range_search_today_assigned_s

 order by $order_by  $order  LIMIT $from, $max_results";	 //exit;
 
$result_enq =  DB::select(($sql));
$num_rows_enq_assigned_today	= count($result_enq); 

 $sql_assigned_today="SELECT
tbl_web_enq.ID as 'enq_id', 
tbl_enq_source.enq_source_name,
tbl_application.application_name,
tbl_web_enq_edit.cust_segment,
CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
where tbl_web_enq_edit.deleteflag='active'
$date_range_search_today_assigned_s
 AND tbl_web_enq_edit.ID IS NOT NULL 
 order by $order_by  $order  LIMIT $from, $max_results";	 //exit;
 
$result_enq_assigned_today =  DB::select(($sql_assigned_today));
$num_rows_enq_assigned_today	= count($result_enq_assigned_today); 	  
$assigned_today						= "0";
	return response()->json([ 
			'assigned_enquiry_data' => $result_enq,
			'assigned_export_enquiry_data' => $result_enq,
'assigned_today' => $num_rows_enq_assigned_today
		]);
		
}







public function inside_sales_listing_overdue(Request $request)
    {
$todays_enq_date		=	date("Y-m-d");	   
	   
"<br>".		  $alarm_1_days=fetchAlarm_config("alarm_1");
"<br>".		  $alarm_2_days=fetchAlarm_config("alarm_2");
"<br>".		  $alarm_3_days=fetchAlarm_config("alarm_3");
	   		$acc_manager_request        = $request->acc_manager;
			$todays_enq        			= $request->todays_enq;
			$added_by_acc_manager       = $request->added_by_acc_manager;
			$lead_assigned_search 		= $request->lead_assigned_search;
			$AdminLoginID_SET			= $request->AdminLoginID_SET;
		    $admin_role_id				= $request->admin_role_id;
			$data_action				= $request->action;
			$overdue					= $request->overdue;
            $pcode						= $request->pcode;
			$search_by					= $request->search_by;
			$enq_id_search				= $request->enq_id;
            $order 						= $request->order;
            $cust_segment				= $request->cust_segment; 
            $sort_by	 				= $request->sort_by;
            $lead_created				= $request->lead_created;
			$offer_created				= $request->offer_created;
            $follow_up_order_by			= $request->follow_up_order_by;
            $offer_probability_search	= $request->enq_stage;
            $last_updated_on			= $request->last_updated_on;
            $app_cat_id_search			= $request->product_category;
            $hvc_search					= $request->hvc_search;
			$deleted_enquiries			= $request->deleted_enquiries;
            $hot_enquiry_search			= $request->hot_enquiry;	
//          $enq_id_search				= $request->enq_id_search;
            $enq_status					= $request->dead_duck;
			$hvc_search_filter			= $request->hvc_search_filter;
            $search_from 				= $request->date_from;
		    $datepicker_to 				= $request->date_to;
			
			$enq_type 					= $request->enq_type;
			//$ref_source_request	= $request->ref_source_request;
			if($enq_status=='' && $enq_status!='-1')
				{
					$enq_status='0';
				}
			$ref_source_request			= $request->ref_source;
			$lead_created_search		= "";
			$offer_created_search		= "";
/*$ref_source_search						= $request->ref_source_search;
$date_range_search						= $request->date_range_search;
//$enq_status_search= $request->enq_status_search;
$app_cat_id_search_search				= $request->app_cat_id_search_search;
$offer_probability_search_search		= $request->offer_probability_search_search;
$last_updated_on_search					= $request->last_updated_on_search;
$hot_enquiry_search_search				= $request->hot_enquiry_search_search;
*/
	if(!isset($request->pageno))
	{ 
    	$page = 1; 
	} 
	else 
	{ 
    	$page = $request->pageno; 
	} 
	if(!isset($request->records))
	{ 
    	$max_results = 100; 
	} 
	else 
	{ 
    	$max_results = $request->records; 
	} 
	@$from = (($page * $max_results) - $max_results);  
	if($from<0)
	{
		$from="0";
	}

if($enq_id_search!='' && $enq_id_search!='0')
{
//	$enq_id_search_filter=" and tbl_web_enq_edit.ID = '$enq_id_search' ";
$enq_id_search_filter=" and tbl_web_enq.ID = '$enq_id_search' ";
}

else
{
		$enq_id_search_filter="  ";
}


if($lead_assigned_search!='')
	{
		
		if($lead_assigned_search=='1')//yes is not null
		{
$search_lead_assigned_search=" AND tbl_web_enq_edit.ID IS NOT NULL  ";
		}
		else if($lead_assigned_search=='0')//no is null
		{
//$search_lead_assigned_search=" AND tbl_web_enq_edit.ID IS NULL  ";
$search_lead_assigned_search=" AND tbl_web_enq_edit.ID IS NOT NULL  ";
		}

else
{
	$search_lead_assigned_search=" ";
}
	}
	else
	{
	$search_lead_assigned_search=" ";		
	}
	

if($overdue!='' && $overdue!='0')
{
//	$enq_id_search_filter=" and tbl_web_enq_edit.ID = '$enq_id_search' ";
$overdue_filter=" AND tbl_web_enq.Enq_Date >= DATE_SUB(CURDATE(), INTERVAL $alarm_3_days DAY) AND tbl_web_enq.Enq_Date < CURDATE()";
}

else
{
		$overdue_filter="  ";
}



	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword=" AND tbl_web_enq_edit.Cus_name like '%".$search_by."%' OR tbl_web_enq_edit.Cus_email like '%".$search_by."%' OR tbl_web_enq_edit.Cus_mob like '%".$search_by."%' ";
	}

	else
	{
	$search_by_keyword="";
	}

	 	
	if($search_from!='' && $datepicker_to!='')
	{
		
$date_range_search=" AND (date( tbl_web_enq.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}
 
 	
if($todays_enq!='' && $todays_enq!='0')
	{
		
$date_range_search_today_assigned=" AND DATE(tbl_web_enq.Enq_Date) = '$todays_enq_date'"; // -- Filters for current date only
	}
	else
	{
$date_range_search_today_assigned=" "; // -- Filters for current date only
	}	
	
	
$date_range_search_today_assigned_s=" AND DATE(tbl_web_enq.Enq_Date) = '$todays_enq_date'"; // -- Filters for current date onlycurrent date only

if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq.dead_duck='$enq_status'";
	
}
else
{
	$enq_status_search="";
}

if($enq_type!='' && $enq_type!='-1')
{
	
	$enq_type_search=" AND tbl_web_enq_edit.enq_type='$enq_type'";

}
else
{
	$enq_type_search="";
}


if($deleted_enquiries!='' && $deleted_enquiries!='-1')
{
	
	$deleted_enquiries_search=" AND tbl_web_enq.deleteflag='$deleted_enquiries'";

}
else
{
$deleted_enquiries_search=" AND tbl_web_enq.deleteflag='active'";
}




if($offer_probability_search!='' && $offer_probability_search!='0')
{
	
	$offer_probability_search_search=" AND tbl_web_enq_edit.enq_stage='$offer_probability_search'";
}
else
{
	$offer_probability_search_search="";
}


if($hot_enquiry_search!='' )
{
	
	$hot_enquiry_search_search=" AND tbl_web_enq_edit.hot_enquiry='$hot_enquiry_search'";
}
else
{
	$hot_enquiry_search_search="";
}

if($last_updated_on!='' && $last_updated_on!='0' && $last_updated_on=='uil15d')
{
	
	$last_updated_on_search=" and tbl_web_enq_edit.mel_updated_on > now() - INTERVAL 15 day";
}
else if($last_updated_on=='nuil15d')
{
	$last_updated_on_search=" and tbl_web_enq_edit.mel_updated_on > now() - INTERVAL 115 day";
}

	else
	{
		$last_updated_on_search="";
	}


if($cust_segment!='')
{
	
	$cust_segment_search=" AND tbl_web_enq_edit.cust_segment='$cust_segment'";

}
else
{
	$cust_segment_search="";
}


if($app_cat_id_search!='')
{
	
	$app_cat_id_search_search=" AND tbl_web_enq_edit.product_category='$app_cat_id_search'";

}
else
{
	$app_cat_id_search_search="";
}

if($lead_created!='')
{
	if($lead_created=='Yes')
	{
	$lead_created_search=" AND tbl_web_enq_edit.lead_id!='0'";
	}
	
	else if($lead_created=='No')
	{
	$lead_created_search=" AND tbl_web_enq_edit.lead_id='0'";
	}
}
else
{
	$lead_created_search="";
}


if($offer_created!='')
{
	if($offer_created=='Yes')
	{
	$offer_created_search=" AND tbl_web_enq_edit.order_id!='0'";
	}
	
	else if($offer_created=='No')
	{
	$offer_created_search=" AND tbl_web_enq_edit.order_id='0'";
	}
}
else
{
	$offer_created_search="";
}

if($sort_by!='')
{
if($sort_by=='date_asc')
{
	$order_by="tbl_web_enq_edit.Enq_Date";
	$order="asc";
}
if($sort_by=='date_desc')
{
		$order_by="tbl_web_enq_edit.Enq_Date";
		$order="desc";
}

}
else
{
	$order_by="ID";
	$order="desc";
}

if($acc_manager_request!='' && $acc_manager_request!='0')
	{
	$acc_manager_search="and tbl_web_enq_edit.acc_manager IN ($acc_manager_request) ";
	}
	else
	{
		$acc_manager_search=" ";
	}

//echo $added_by_acc_manager;
if($added_by_acc_manager!='' && $added_by_acc_manager!='0')
	{
	$added_by_acc_manager_search=" and tbl_web_enq.added_by_acc_manager IN ($added_by_acc_manager) ";
	}
	else
	{
		$added_by_acc_manager_search=" ";
	}



	if($ref_source_request!='' && $ref_source_request!='0')
	{
	$ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
	}
	else
	{
		$ref_source_search=" ";
	}		

if($sort_by!='')
{
if($sort_by=='date_asc')
{
	$order_by="tbl_web_enq.Enq_Date";
	$order="asc";
}
if($sort_by=='date_desc')
{
		$order_by="tbl_web_enq.Enq_Date";
		$order="desc";
}

}
else
{
	$order_by="tbl_web_enq.id";
	$order="desc";
}
			 
$searchRecord				= $search_lead_assigned_search.$added_by_acc_manager_search.$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$deleted_enquiries_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$hot_enquiry_search_search.$search_by_keyword.$offer_created_search.$overdue_filter.$date_range_search_today_assigned;//"and acc_manager=$acc_manager_lead";
	
$searchRecordtodayassigned	= $added_by_acc_manager_search.$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search_today_assigned_s.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$deleted_enquiries_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$hot_enquiry_search_search.$search_by_keyword.$offer_created_search.$overdue_filter;//"and acc_manager=$acc_manager_lead";


$sql="SELECT
tbl_web_enq.ID as 'enq_id', 
tbl_web_enq.Cus_name ,
tbl_web_enq.Cus_email ,
tbl_web_enq.Cus_mob ,
tbl_web_enq.mark_as_completed ,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq.page_link,
tbl_web_enq.page_name,
tbl_web_enq.Cus_msg ,
tbl_web_enq.ref_source ,
tbl_web_enq.product_category ,
tbl_web_enq.Enq_Date ,
tbl_web_enq.enq_remark ,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq.dead_duck ,
tbl_web_enq.added_by_acc_manager ,
tbl_web_enq.deleteflag ,
tbl_web_enq_edit.ID as 'enq_id_edited',
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.order_id,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.cust_segment,
tbl_enq_source.enq_source_name,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
d.designation_name,
tbl_country.country_name as country_name, 
tbl_zones.zone_name as state_name, 
all_cities.city_name as city_name,
tbl_admin.admin_email,
tbl_admin.admin_telephone,
CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
INNER JOIN tbl_designation d on d.designation_id = tbl_admin.admin_designation

LEFT JOIN tbl_country     ON tbl_country.country_id = tbl_web_enq_edit.country
LEFT JOIN tbl_zones     ON tbl_zones.zone_id = tbl_web_enq_edit.state 
LEFT JOIN all_cities    ON all_cities.city_id = tbl_web_enq_edit.city 


where    tbl_web_enq.mark_as_completed=0
$searchRecord
and tbl_web_enq_edit.lead_id='0'
HAVING days_since_enq > $alarm_1_days AND days_since_enq <= 180
 order by $order_by  $order  LIMIT $from, $max_results";	 //exit;
 
$result_enq =  DB::select(($sql));

$sql_paging_and_export_excel	= "SELECT
tbl_web_enq.ID as 'enq_id', 
tbl_web_enq.Cus_name ,
tbl_web_enq.Cus_email ,
tbl_web_enq.Cus_mob ,
tbl_web_enq.mark_as_completed ,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq.page_link,
tbl_web_enq.page_name,
tbl_web_enq.Cus_msg ,
tbl_web_enq.ref_source ,
tbl_web_enq.product_category ,
tbl_web_enq.Enq_Date ,
tbl_web_enq.enq_remark ,
tbl_web_enq.dead_duck ,
tbl_web_enq.added_by_acc_manager ,
tbl_web_enq.deleteflag ,
tbl_web_enq_edit.ID as 'enq_id_edited',
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.order_id,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.cust_segment,
tbl_enq_source.enq_source_name,
tbl_application.application_name,
tbl_admin.admin_designation,
d.designation_name,
CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source

LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
INNER JOIN tbl_designation d on d.designation_id = tbl_admin.admin_designation
where    tbl_web_enq.mark_as_completed=0
$searchRecord
and tbl_web_enq_edit.lead_id='0'
HAVING days_since_enq > $alarm_1_days AND days_since_enq <= 180
 order by $order_by  $order   ";
$result_enq_paging_export_to_excel 	=  DB::select(($sql_paging_and_export_excel));					
$num_rows							= count($result_enq_paging_export_to_excel); 	  
$assigned_today						= "0";
	return response()->json([ 
			'over_due_enquiry_data' => $result_enq,
			'over_due_export_enquiry_data' => $result_enq,
			'num_rows_count' => $num_rows,
		]);
		
}

 public function  delete_enquiry(Request $request)
    {    
        $date 	  				= date('Y-m-d');
         $ID						= $request->ID;// exit;
//		$inside_sales_enq_delete	= DB::table('tbl_web_enq')->where('ID', $ID)->delete();
//		$sales_enq_delete			= DB::table('tbl_web_enq_edit')->where('enq_id', $ID)->delete();

		$ArrayDataedited['deleteflag'] 			= "inactive";
        $inside_sales_enq_delete= DB::table('tbl_web_enq')
            ->where('ID', $ID)
            ->update($ArrayDataedited);


        $sales_enq_delete= DB::table('tbl_web_enq_edit')
            ->where('enq_id', $ID)
            ->update($ArrayDataedited);



       if($inside_sales_enq_delete){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

        return response()->json([            
        'message' => $msg, 
        ]);
}


public function delete_multiple_enquiry(Request $request)
{
    $date = date('Y-m-d');
    $IDs = $request->ID; //exit;  // Assuming $ID is an array of IDs to be updated.

    // Check if IDs are provided
    if (empty($IDs)) {
        return response()->json(['message' => 'No IDs provided.']);
    }

    // Prepare the data to update the 'deleteflag' column
    $ArrayDataedited = ['deleteflag' => 'inactive'];

    // Update 'tbl_web_enq' where ID is in the provided list
    $inside_sales_enq_delete = DB::table('tbl_web_enq')
        ->whereIn('ID', $IDs)
        ->update($ArrayDataedited);

    // Update 'tbl_web_enq_edit' where enq_id is in the provided list
    $sales_enq_delete = DB::table('tbl_web_enq_edit')
        ->whereIn('enq_id', $IDs)
        ->update($ArrayDataedited);

    // Check if both updates were successful
    if ($inside_sales_enq_delete) {
        $msg = "true";
    } else {
        $msg = "false";
    }

    return response()->json([
        'message' => $msg,
    ]);
}



 public function  restore_enquiry(Request $request)
    {    
        $date 	  				= date('Y-m-d');
       $ID						= $request->ID;// exit;
//		$inside_sales_enq_delete	= DB::table('tbl_web_enq')->where('ID', $ID)->delete();
//		$sales_enq_delete			= DB::table('tbl_web_enq_edit')->where('enq_id', $ID)->delete();

		$ArrayDataedited['deleteflag'] 			= "active";
        $inside_sales_enq_delete= DB::table('tbl_web_enq')
            ->where('ID', $ID)
            ->update($ArrayDataedited);


        $sales_enq_delete= DB::table('tbl_web_enq_edit')
            ->where('enq_id', $ID)
            ->update($ArrayDataedited);



       if($inside_sales_enq_delete){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

        return response()->json([            
        'message' => $msg, 
        ]);
}



public function weekly_enquiry_metrics(Request $request)
    {

	 	$financial_year		= $request->financial_year; 
        $acc_manager		= $request->acc_manager;   
		$month				= $request->month;   
       
        if($financial_year=='')
        {
            if (date('m')>3) {
            $year = date('Y').'-'.(date('Y')+1);
        } else {
            $year = (date('Y')-1).'-'.date('Y');
        }

        $financial_year=$year;

       $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode=explode("-",$financial_year);
        $fin_yr=show_financial_year_id($financial_year);

        
        if($financial_year_explode[0]!='' && $financial_year_explode[0]!='0')
        { 
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q1_end_date_show=$financial_year_explode[0].'-06-30';
            $q1_start_date_show_may=$financial_year_explode[0].'-05-01';
            $q1_start_date_show_jun=$financial_year_explode[0].'-06-01';
            $q1_end_date_show_apr=$financial_year_explode[0].'-04-30';
            $q1_end_date_show_may=$financial_year_explode[0].'-05-31';


            $q2_start_date_show=$financial_year_explode[0].'-07-01';
            $q2_end_date_show=$financial_year_explode[0].'-09-30';

            $q2_start_date_show_aug=$financial_year_explode[0].'-08-01';
            $q2_start_date_show_sept=$financial_year_explode[0].'-09-01';
            $q2_end_date_show_jul=$financial_year_explode[0].'-07-30';
            $q2_end_date_show_aug=$financial_year_explode[0].'-08-31';


            $q3_start_date_show=$financial_year_explode[0].'-10-01';
            $q3_end_date_show=$financial_year_explode[0].'-12-31';

            $q3_start_date_show_nov=$financial_year_explode[0].'-11-01';
            $q3_start_date_show_dec=$financial_year_explode[0].'-12-01';
            $q3_end_date_show_nov=$financial_year_explode[0].'-11-30';
            $q3_end_date_show_oct=$financial_year_explode[0].'-10-31';

            $q4_start_date_show=$financial_year_explode[1].'-01-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';

            $q4_start_date_show_feb=$financial_year_explode[1].'-02-01';
            $q4_start_date_show_march=$financial_year_explode[1].'-03-01';
            $q4_end_date_show_feb=$financial_year_explode[1].'-02-29';
            $q4_end_date_show_jan=$financial_year_explode[1].'-01-31';
        }
        else
        {
            $financial_year_explode[0]="2016";
            $financial_year_explode[1]="2026";
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';
        }   

if($month!='' && $month!='0')
{
	
$month_search= " AND MONTH(ds.Enq_Date) IN ($month)";//  -- Filter for April & December
}
else
{
$month_search= " ";//  -- Filter for April & December
}


/*if($month!='' && $month!='0')
{
	
$month_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December

//$month_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE() ";  //-- Change this value for different months (e.g., 12 for December)
}
else
{
$month_search= " ";//  -- Filter for April & December
}*/

/*
if($acc_manager!='' && $acc_manager!='0')
{
	
$acc_manager_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
}
else
{
$acc_manager_search= " ";//  -- Filter for April & December
}*/


if($acc_manager!='' && $acc_manager!='0')
{
	
$acc_manager_search= " AND twee.acc_manager = '$acc_manager'";//  -- Filter for April & December
$group_by_admin= ",twee.acc_manager";
$join_by_acc_manager= "INNER JOIN tbl_web_enq_edit twee ON twe.ID = twee.enq_id";
$acc_manager_column= " ,twee.acc_manager";
}
else
{
$acc_manager_search= " ";//  -- Filter for April & December
$group_by_admin= "";
$join_by_acc_manager= "";
$acc_manager_column= " ";
}

//echo $q4_end_date_show;

$weekly_enquiry_metrics = "WITH RECURSIVE date_series AS (
    SELECT DATE('$q1_start_date_show') AS Enq_Date
    UNION ALL
    SELECT DATE_ADD(Enq_Date, INTERVAL 1 DAY)
    FROM date_series
    WHERE Enq_Date <= '$q4_end_date_show'
)
SELECT 
    YEAR(ds.Enq_Date) AS year,
	MONTH(ds.Enq_Date) AS month,
    MONTHNAME(ds.Enq_Date) AS month_name,
    WEEK(ds.Enq_Date, 1) - WEEK(DATE_SUB(ds.Enq_Date, INTERVAL DAY(ds.Enq_Date)-1 DAY), 1) + 1 AS week_in_month,
    DAYNAME(ds.Enq_Date) AS day_name,
    COUNT(twe.ID) AS inquiry_count $acc_manager_column

FROM date_series ds
LEFT JOIN tbl_web_enq twe ON ds.Enq_Date = DATE(twe.Enq_Date)
$join_by_acc_manager
where twe.dead_duck='0' $month_search  $acc_manager_search
GROUP BY year, month_name, week_in_month, day_name, ds.Enq_Date $group_by_admin
ORDER BY year, MONTH(ds.Enq_Date), week_in_month, ds.Enq_Date";



/*echo   $weekly_enquiry_metrics 	= "WITH RECURSIVE date_series AS (
    SELECT DATE('$q1_start_date_show') AS Enq_Date
    UNION ALL
    SELECT DATE_ADD(Enq_Date, INTERVAL 1 DAY)
    FROM date_series
    WHERE Enq_Date <= '$q4_end_date_show'
),
month_start_weeks AS (
    SELECT 
        YEAR(Enq_Date) AS year,
        MONTH(Enq_Date) AS month,
        WEEK(Enq_Date, 1) AS first_week_of_month,
        ROW_NUMBER() OVER (ORDER BY MONTH(Enq_Date)) AS month_index  
    FROM date_series
    WHERE DAY(Enq_Date) = 1
)
SELECT 
    YEAR(ds.Enq_Date) AS year,
    MONTH(ds.Enq_Date) AS month,
    MONTHNAME(ds.Enq_Date) AS month_name,
    ((msw.month_index - 1) * 4) + (WEEK(ds.Enq_Date, 1) - msw.first_week_of_month) + 1 AS week_in_month, 
    WEEK(ds.Enq_Date, 1) AS week_of_year,
    DAYNAME(ds.Enq_Date) AS day_name,
    COUNT(twe.ID) AS inquiry_count
FROM date_series ds
LEFT JOIN tbl_web_enq twe ON ds.Enq_Date = DATE(twe.Enq_Date)
LEFT JOIN month_start_weeks msw ON YEAR(ds.Enq_Date) = msw.year AND MONTH(ds.Enq_Date) = msw.month
WHERE 1=1   $month_search
GROUP BY year, month, month_name, week_in_month, week_of_year, day_name, ds.Enq_Date
ORDER BY year, month, week_in_month, ds.Enq_Date";*/
	 
$rs_weekly_enquiry_metrics =  DB::select(($weekly_enquiry_metrics)); 
   return response()->json([            
            'weekly_enquiry_metrics' => $rs_weekly_enquiry_metrics,
			'fin_yr_id' => $fin_yr,
			
        ]);
    }


public function monthy_inbound_metrics(Request $request)
    {

	 	$financial_year		= $request->financial_year; 
        $acc_manager		= $request->acc_manager;   
		$month				= $request->month;   
       
        if($financial_year=='')
        {
            if (date('m')>3) {
            $year = date('Y').'-'.(date('Y')+1);
        } else {
            $year = (date('Y')-1).'-'.date('Y');
        }

        $financial_year=$year;

       $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode=explode("-",$financial_year);
        $fin_yr=show_financial_year_id($financial_year);

        
        if($financial_year_explode[0]!='' && $financial_year_explode[0]!='0')
        { 
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q1_end_date_show=$financial_year_explode[0].'-06-30';
            $q1_start_date_show_may=$financial_year_explode[0].'-05-01';
            $q1_start_date_show_jun=$financial_year_explode[0].'-06-01';
            $q1_end_date_show_apr=$financial_year_explode[0].'-04-30';
            $q1_end_date_show_may=$financial_year_explode[0].'-05-31';


            $q2_start_date_show=$financial_year_explode[0].'-07-01';
            $q2_end_date_show=$financial_year_explode[0].'-09-30';

            $q2_start_date_show_aug=$financial_year_explode[0].'-08-01';
            $q2_start_date_show_sept=$financial_year_explode[0].'-09-01';
            $q2_end_date_show_jul=$financial_year_explode[0].'-07-30';
            $q2_end_date_show_aug=$financial_year_explode[0].'-08-31';


            $q3_start_date_show=$financial_year_explode[0].'-10-01';
            $q3_end_date_show=$financial_year_explode[0].'-12-31';

            $q3_start_date_show_nov=$financial_year_explode[0].'-11-01';
            $q3_start_date_show_dec=$financial_year_explode[0].'-12-01';
            $q3_end_date_show_nov=$financial_year_explode[0].'-11-30';
            $q3_end_date_show_oct=$financial_year_explode[0].'-10-31';

            $q4_start_date_show=$financial_year_explode[1].'-01-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';

            $q4_start_date_show_feb=$financial_year_explode[1].'-02-01';
            $q4_start_date_show_march=$financial_year_explode[1].'-03-01';
            $q4_end_date_show_feb=$financial_year_explode[1].'-02-29';
            $q4_end_date_show_jan=$financial_year_explode[1].'-01-31';
        }
        else
        {
            $financial_year_explode[0]="2016";
            $financial_year_explode[1]="2026";
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';
        }   

/*if($month!='' && $month!='0')
{
	
$month_search= " WHERE MONTH(ds.Enq_Date) IN ($month)";//  -- Filter for April & December
}
else
{
$month_search= " ";//  -- Filter for April & December
}*/


if($acc_manager!='' && $acc_manager!='0')
{
	
$acc_manager_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
}
else
{
$acc_manager_search= " ";//  -- Filter for April & December
}

if($month!='' && $month!='0')
{
	
$month_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
}
else
{
$month_search= " ";//  -- Filter for April & December
}
//echo $q4_end_date_show;

    $monthy_inbound_metrics 	= "WITH RECURSIVE date_series AS (
    SELECT DATE('$q1_start_date_show') AS Enq_Date
    UNION ALL
    SELECT DATE_ADD(Enq_Date, INTERVAL 1 DAY)
    FROM date_series
    WHERE Enq_Date <= '$q4_end_date_show'
)
SELECT 
    YEAR(ds.Enq_Date) AS year,
    MONTHNAME(ds.Enq_Date) AS month_name,
    COUNT(twe.ID) AS inquiry_count
FROM date_series ds
LEFT JOIN tbl_web_enq twe ON ds.Enq_Date = DATE(twe.Enq_Date)
where twe.dead_duck='0'
GROUP BY year, month_name
ORDER BY year, MONTH(ds.Enq_Date)";
	 
$rs_monthy_inbound_metrics=  DB::select(($monthy_inbound_metrics)); 
   return response()->json([            
            'monthy_inbound_metrics' => $rs_monthy_inbound_metrics,
			'fin_yr_id' => $fin_yr,
			
        ]);
    }


public function qualified_vs_unqualified_enquiries_trend(Request $request)
    {

	 	$financial_year		= $request->financial_year; 
        $acc_manager		= $request->acc_manager;   
		$month				= $request->month;   
       
        if($financial_year=='')
        {
            if (date('m')>3) {
            $year = date('Y').'-'.(date('Y')+1);
        } else {
            $year = (date('Y')-1).'-'.date('Y');
        }

        $financial_year=$year;

       $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode=explode("-",$financial_year);
        $fin_yr=show_financial_year_id($financial_year);

        
        if($financial_year_explode[0]!='' && $financial_year_explode[0]!='0')
        { 
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q1_end_date_show=$financial_year_explode[0].'-06-30';
            $q1_start_date_show_may=$financial_year_explode[0].'-05-01';
            $q1_start_date_show_jun=$financial_year_explode[0].'-06-01';
            $q1_end_date_show_apr=$financial_year_explode[0].'-04-30';
            $q1_end_date_show_may=$financial_year_explode[0].'-05-31';


            $q2_start_date_show=$financial_year_explode[0].'-07-01';
            $q2_end_date_show=$financial_year_explode[0].'-09-30';

            $q2_start_date_show_aug=$financial_year_explode[0].'-08-01';
            $q2_start_date_show_sept=$financial_year_explode[0].'-09-01';
            $q2_end_date_show_jul=$financial_year_explode[0].'-07-30';
            $q2_end_date_show_aug=$financial_year_explode[0].'-08-31';


            $q3_start_date_show=$financial_year_explode[0].'-10-01';
            $q3_end_date_show=$financial_year_explode[0].'-12-31';

            $q3_start_date_show_nov=$financial_year_explode[0].'-11-01';
            $q3_start_date_show_dec=$financial_year_explode[0].'-12-01';
            $q3_end_date_show_nov=$financial_year_explode[0].'-11-30';
            $q3_end_date_show_oct=$financial_year_explode[0].'-10-31';

            $q4_start_date_show=$financial_year_explode[1].'-01-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';

            $q4_start_date_show_feb=$financial_year_explode[1].'-02-01';
            $q4_start_date_show_march=$financial_year_explode[1].'-03-01';
            $q4_end_date_show_feb=$financial_year_explode[1].'-02-29';
            $q4_end_date_show_jan=$financial_year_explode[1].'-01-31';
        }
        else
        {
            $financial_year_explode[0]="2016";
            $financial_year_explode[1]="2026";
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';
        }   

if($month!='' && $month!='0')
{
	
$month_search= " AND MONTH(ds.Enq_Date) IN ($month)";//  -- Filter for April & December
}
else
{
$month_search= " ";//  -- Filter for April & December
}



if($acc_manager!='' && $acc_manager!='0')
{
	
$acc_manager_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
}
else
{
$acc_manager_search= " ";//  -- Filter for April & December
}
//not done because it already handeldd on cliednt side by Yash
//if($month!='' && $month!='0')
//{
//	
//$month_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
//}
//else
//{
//$month_search= " ";//  -- Filter for April & December
//}
//echo $q4_end_date_show;

    $qualified_vs_unqualified_enquiries_trend 	= "SELECT 
    YEAR(tbl_web_enq.Enq_Date) AS year,
    MONTHNAME(tbl_web_enq.Enq_Date) AS month_name,
    MONTH(tbl_web_enq.Enq_Date) AS month,
    COUNT(CASE WHEN tbl_web_enq_edit.acc_manager IS NOT NULL THEN 1 END) AS qualified_enquiries,
    COUNT(CASE WHEN tbl_web_enq_edit.acc_manager IS NULL THEN 1 END) AS non_qualified_enquiries,
    COUNT(tbl_web_enq.ID) AS total_inquiries
FROM 
    tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
WHERE tbl_web_enq.dead_duck = '0' 
  AND tbl_web_enq.deleteflag = 'active'
  AND tbl_web_enq.Enq_Date BETWEEN '$q1_start_date_show' AND '$q4_end_date_show'  
GROUP BY 
    YEAR(tbl_web_enq.Enq_Date),
    MONTH(tbl_web_enq.Enq_Date)
ORDER BY 
     year ASC, month asc";
	 
$rs_qualified_vs_unqualified_enquiries_trend=  DB::select(($qualified_vs_unqualified_enquiries_trend)); 
   return response()->json([            
            'qualified_vs_unqualified_enquiries_trend' => $rs_qualified_vs_unqualified_enquiries_trend,
			'fin_yr_id' => $fin_yr,
			
        ]);
    }



public function enquiry_source_trend(Request $request)
    {

	 	$financial_year		= $request->financial_year; 
        $acc_manager		= $request->acc_manager;   
		$month				= $request->month;   
       
        if($financial_year=='')
        {
            if (date('m')>3) {
            $year = date('Y').'-'.(date('Y')+1);
        } else {
            $year = (date('Y')-1).'-'.date('Y');
        }

        $financial_year=$year;

       $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode=explode("-",$financial_year);
        $fin_yr=show_financial_year_id($financial_year);

        
        if($financial_year_explode[0]!='' && $financial_year_explode[0]!='0')
        { 
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q1_end_date_show=$financial_year_explode[0].'-06-30';
            $q1_start_date_show_may=$financial_year_explode[0].'-05-01';
            $q1_start_date_show_jun=$financial_year_explode[0].'-06-01';
            $q1_end_date_show_apr=$financial_year_explode[0].'-04-30';
            $q1_end_date_show_may=$financial_year_explode[0].'-05-31';


            $q2_start_date_show=$financial_year_explode[0].'-07-01';
            $q2_end_date_show=$financial_year_explode[0].'-09-30';

            $q2_start_date_show_aug=$financial_year_explode[0].'-08-01';
            $q2_start_date_show_sept=$financial_year_explode[0].'-09-01';
            $q2_end_date_show_jul=$financial_year_explode[0].'-07-30';
            $q2_end_date_show_aug=$financial_year_explode[0].'-08-31';


            $q3_start_date_show=$financial_year_explode[0].'-10-01';
            $q3_end_date_show=$financial_year_explode[0].'-12-31';

            $q3_start_date_show_nov=$financial_year_explode[0].'-11-01';
            $q3_start_date_show_dec=$financial_year_explode[0].'-12-01';
            $q3_end_date_show_nov=$financial_year_explode[0].'-11-30';
            $q3_end_date_show_oct=$financial_year_explode[0].'-10-31';

            $q4_start_date_show=$financial_year_explode[1].'-01-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';

            $q4_start_date_show_feb=$financial_year_explode[1].'-02-01';
            $q4_start_date_show_march=$financial_year_explode[1].'-03-01';
            $q4_end_date_show_feb=$financial_year_explode[1].'-02-29';
            $q4_end_date_show_jan=$financial_year_explode[1].'-01-31';
        }
        else
        {
            $financial_year_explode[0]="2016";
            $financial_year_explode[1]="2026";
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';
        }   

/*if($month!='' && $month!='0')
{
	
$month_search= " AND MONTH(ds.Enq_Date) IN ($month)";//  -- Filter for April & December
}
else
{
$month_search= " ";//  -- Filter for April & December
}*/


if($month!='' && $month!='0')
{
	
$month_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
}
else
{
$month_search= " ";//  -- Filter for April & December
}

if($acc_manager!='' && $acc_manager!='0')
{
	
$acc_manager_search= " AND tbl_web_enq_edit.acc_manager = '$acc_manager'";//  -- Filter for April & December
}
else
{
$acc_manager_search= " ";//  -- Filter for April & December
}

//echo $q4_end_date_show;

      $enquiry_source_trend 	= "WITH RECURSIVE date_series AS (
    SELECT DATE('$q1_start_date_show') AS Enq_Date
    UNION ALL
    SELECT DATE_ADD(Enq_Date, INTERVAL 1 MONTH)
    FROM date_series
    WHERE Enq_Date < '$q4_end_date_show'
)

SELECT 
    tbl_web_enq_edit.ref_source,
	tbl_enq_source.enq_source_colour,
	tbl_web_enq_edit.acc_manager,
    YEAR(ds.Enq_Date) AS year,
    MONTHNAME(ds.Enq_Date) AS month_name,
    MONTH(ds.Enq_Date) AS month,
	tbl_enq_source.enq_source_name,
    IFNULL(COUNT(tbl_web_enq.ID), 0) AS inquiry_count
FROM 
    date_series ds
LEFT JOIN 
    tbl_web_enq ON YEAR(ds.Enq_Date) = YEAR(tbl_web_enq.Enq_Date) 
    AND MONTH(ds.Enq_Date) = MONTH(tbl_web_enq.Enq_Date)
LEFT JOIN 
    tbl_enq_source ON tbl_enq_source.enq_source_description = tbl_web_enq.ref_source
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID	
	
WHERE 
    ds.Enq_Date BETWEEN '$q1_start_date_show' AND '$q4_end_date_show'
	AND tbl_web_enq_edit.dead_duck = '0' 
	$month_search
	$acc_manager_search
GROUP BY 
    tbl_web_enq.ref_source,
    YEAR(ds.Enq_Date), 
    MONTH(ds.Enq_Date)
ORDER BY 
    year, month, tbl_web_enq.ref_source";
	 
//$rs_enquiry_source_trend=  DB::select(($enquiry_source_trend)); 
// Fetch the data from the query
$rs_enquiry_source_trend = DB::select(($enquiry_source_trend));

// Convert the object result to an associative array
$rs_enquiry_source_trend = json_decode(json_encode($rs_enquiry_source_trend), true);

//// Initialize an empty array for grouping
//$groupedData = [];
//
//// Loop through the data and group by ref_source
//foreach ($rs_enquiry_source_trend as $row) {
//    $ref_source = $row["ref_source"] ?? "Unknown"; // Handle NULL values
//
//    if (!isset($groupedData[$ref_source])) {
//        $groupedData[$ref_source] = []; // Initialize as an empty array
//    }
//
//    // Append month-wise data under the respective ref_source
//    $groupedData[$ref_source][] = [
//        "year" => $row["year"],
//        "month_name" => $row["month_name"],
//        "month" => $row["month"],
//        "enq_source_name" => $row["enq_source_name"],
//        "inquiry_count" => $row["inquiry_count"]
//    ];
//}

 



   return response()->json([            
           // 'enquiry_source_trend' => $groupedData,
			'enquiry_source_trend_normal' => $rs_enquiry_source_trend,
			'fin_yr_id' => $fin_yr,
			
        ]);
    }

public function assign_enq_to_selected_acc_manager(Request $request)
    {
	    $enq_array			= $request->all();
		$state				= $request->state;
		$city				= $request->city;
		$cust_segment		= $request->cust_segment;	
		$country			= $request->country;
		$app_cat_id 		= $request->product_category;
		
		if($country!= '')		
		{
//			$state_search = " and allowed_states  LIKE '%$state%' OR allowed_states LIKE '%0%' ";
//	$state_search = " and CONCAT(',', allowed_states, ',') like '%,$state,%' OR CONCAT(',', allowed_states, ',') like '%,0,%'";			
//	$state_search = " and allowed_states IN(0,$state)";		
		$country_search = " and FIND_IN_SET ($country,taa.allowed_country)";		
		$country_search_tbl_admin = " and FIND_IN_SET ($country,allowed_country)";		
		}
		
		else
		{
			$country_search = "";
		$country_search_tbl_admin = " ";					
		}
		
		if($state!= '' && is_numeric($state) && $country=='99' )		
		{
		$state_search = " and FIND_IN_SET ($state,taa.allowed_states)";		
		$state_search_tbl_admin = " and FIND_IN_SET ($state,allowed_states)";				
		}
		
		else
		{
			$state_search = "";
		$state_search_tbl_admin = " ";							
		}

		if($city!= '' && $country=='99' )		
		{
		$city_search = " and FIND_IN_SET ($city,taa.allowed_city)  ";					
		$city_search_tbl_admin = " and FIND_IN_SET ($city,allowed_city)  ";					
		// OR  FIND_IN_SET (0,allowed_city)
		}
		
		else
		{
			$city_search = "";
		$city_search_tbl_admin = " ";								
		}


		if($app_cat_id!= '' && $country=='99' )		
		{
		$app_cat_id_search = " and FIND_IN_SET ($app_cat_id,taa.allowed_category)  ";							
		$app_cat_id_search_tbl_admin = " and FIND_IN_SET ($app_cat_id,allowed_category)  ";									
		}
		
		else
		{
			$app_cat_id_search = "";
		$app_cat_id_search_tbl_admin = " ";												
		}


		if($cust_segment!= '' && $country=='99' )		
		{

		$cust_segment_search = " and FIND_IN_SET ($cust_segment,taa.allowed_segments)  ";							
		$cust_segment_search_tbl_admin = " and FIND_IN_SET ($cust_segment,allowed_segments)  ";									
		}
		
		else
		{
			$cust_segment_search = "";
		$cust_segment_search_tbl_admin = " ";												
		}


$pro_id_search="";
       $query="Select DISTINCT(taa.admin_id), ta.admin_fname, ta.admin_lname, ta.admin_status,ta.admin_email,ta.admin_telephone from tbl_admin_allowed_state taa INNER JOIN tbl_admin ta on taa.admin_id=ta.admin_id where 1=1 $country_search $state_search $city_search $cust_segment_search  $pro_id_search $app_cat_id_search and taa.deleteflag = 'active' and ta.admin_status='active' order by taa.admin_id";
	//$rs_role  = mysqli_query($GLOBALS["___mysqli_ston"],  $query);
$rs_role = DB::select(($query));	
  "<br>NUM: ".$num_rows	= count($rs_role); 	  
//echo "<br>NUM: ".$num_rows=mysqli_num_rows($rs_role);


if($num_rows=='0' || $num_rows=='')
{
	
/*	 echo "<br><br>NONE:". $query="Select * from tbl_admin_allowed_state  where 1=1 $country_search $state_search $cust_segment_search  $pro_id_search $app_cat_id_search  AND    deleteflag = 'active' and admin_status='active' order by admin_id";*/

     "<br><br>".	  $query="Select DISTINCT(admin_id), admin_fname, admin_lname, admin_status, admin_email, admin_telephone from tbl_admin where 1=1 $country_search_tbl_admin $state_search_tbl_admin $cust_segment_search_tbl_admin  $pro_id_search $app_cat_id_search_tbl_admin  and deleteflag = 'active' and admin_status='active' order by admin_status,admin_fname";

	$rs_role = DB::select(($query));	
     "<br>NUMss: ".$num_rows	= count($rs_role); 	  
//echo    "<br>NUMss: ".$num_rows=mysqli_num_rows($rs_role);

}

   return response()->json([            
            'assign_enq_to_selected_acc_manager_list' => $rs_role,
			'num_rows' => $num_rows,
			
        ]);
    }




public function team_directory(Request $request)
    {
 $enq_array			= $request->all();
 
		$state				= $request->state;
		$city				= $request->city;
		$cust_segment		= $request->cust_segment;	
		$country			= $request->country;
		$app_cat_id 		= $request->product_category;
	//	$pro_id1 		= $request->pro_id;

//print_r($enq_array);
       
/*if($pro_id1!= '')		
		{
			$pro_id_search = " and tbl_products.pro_id = '$ProID' ";
		}
		else
		{
			$pro_id_search = "";
		}*/
		
		if($country!= '')		
		{
//			$state_search = " and allowed_states  LIKE '%$state%' OR allowed_states LIKE '%0%' ";
//	$state_search = " and CONCAT(',', allowed_states, ',') like '%,$state,%' OR CONCAT(',', allowed_states, ',') like '%,0,%'";			
//	$state_search = " and allowed_states IN(0,$state)";		
		$country_search = " and FIND_IN_SET ($country,taa.allowed_country)";		
		$country_search_tbl_admin = " and FIND_IN_SET ($country,ta.allowed_country)";		
		}
		
		else
		{
			$country_search = "";
		$country_search_tbl_admin = " ";					
		}
		
		if($state!= '' && is_numeric($state) && $country=='99' )		
		{
		$state_search = " and FIND_IN_SET ($state,taa.allowed_states)";		
		$state_search_tbl_admin = " and FIND_IN_SET ($state,ta.allowed_states)";				
		}
		
		else
		{
			$state_search = "";
		$state_search_tbl_admin = " ";							
		}

		if($city!= '' && $country=='99' )		
		{
		$city_search = " and FIND_IN_SET ($city,taa.allowed_city)  ";					
		$city_search_tbl_admin = " and FIND_IN_SET ($city,ta.allowed_city)  ";					
		// OR  FIND_IN_SET (0,allowed_city)
		}
		
		else
		{
			$city_search = "";
		$city_search_tbl_admin = " ";								
		}


		if($app_cat_id!= '' && $country=='99' )		
		{
		$app_cat_id_search = " and FIND_IN_SET ($app_cat_id,taa.allowed_category)  ";							
		$app_cat_id_search_tbl_admin = " and FIND_IN_SET ($app_cat_id,ta.allowed_category)  ";									
		}
		
		else
		{
			$app_cat_id_search = "";
		$app_cat_id_search_tbl_admin = " ";												
		}


		if($cust_segment!= '' && $country=='99' )		
		{

		$cust_segment_search = " and FIND_IN_SET ($cust_segment,taa.allowed_segments)  ";							
		$cust_segment_search_tbl_admin = " and FIND_IN_SET ($cust_segment,ta.allowed_segments)  ";									
		}
		
		else
		{
			$cust_segment_search = "";
		$cust_segment_search_tbl_admin = " ";												
		}


$pro_id_search="";
       $query="Select DISTINCT(taa.admin_id), 
	   ta.admin_fname, 
	   ta.admin_lname, 
	   ta.admin_status,
	   ta.admin_email,
   	 ta.admin_team, 
	   ta.admin_telephone, 
	   ta.admin_designation,
	   d.designation_name  
	   from tbl_admin_allowed_state taa 
	   INNER JOIN tbl_admin ta on taa.admin_id=ta.admin_id 
	   INNER JOIN tbl_designation d on d.designation_id = ta.admin_designation
	   
	   where 1=1 
	   $country_search 
	   $state_search 
	   $city_search 
	   $cust_segment_search  
	   $pro_id_search 
	   $app_cat_id_search 
	   and taa.deleteflag = 'active' 
	   and ta.admin_status='active' 
	   order by taa.admin_id";
	//$rs_role  = mysqli_query($GLOBALS["___mysqli_ston"],  $query);
$rs_role = DB::select(($query));	
  "<br>NUM: ".$num_rows	= count($rs_role); 	  
//echo "<br>NUM: ".$num_rows=mysqli_num_rows($rs_role);


if($num_rows=='0' || $num_rows=='')
{
	
/*	 echo "<br><br>NONE:". $query="Select * from tbl_admin_allowed_state  where 1=1 $country_search $state_search $cust_segment_search  $pro_id_search $app_cat_id_search  AND    deleteflag = 'active' and admin_status='active' order by admin_id";*/

     "<br><br>".	  $query="Select DISTINCT(ta.admin_id), 
	 ta.admin_fname, 
	 ta.admin_lname, 
	 ta.admin_status, 
	 ta.admin_email, 
	 ta.admin_team, 
	 ta.admin_telephone, 
	 ta.admin_designation, 
	 d.designation_name
	 from tbl_admin ta
	INNER JOIN tbl_designation d on d.designation_id = ta.admin_designation
	 where 1=1 
	 $country_search_tbl_admin 
	 $state_search_tbl_admin 
	 $cust_segment_search_tbl_admin  
	 $pro_id_search 
	 $app_cat_id_search_tbl_admin  
	 and ta.deleteflag = 'active' and 
	 ta.admin_status='active' 
	 order by ta.admin_status,ta.admin_fname";

	$rs_role = DB::select(($query));	
     "<br>NUMss: ".$num_rows	= count($rs_role); 	  
//echo    "<br>NUMss: ".$num_rows=mysqli_num_rows($rs_role);

}

//echo "===".$query;
//allowed_city_name_by_admin_id();

$sql_offer 						= collect(DB::select($query)); // Convert to Collection
$admin_details 						= $sql_offer->map(function ($row) {

 return [
   
   
'admin_id' =>$row->admin_id,
'admin_fname' =>$row->admin_fname,
'admin_lname' =>$row->admin_lname,
'designation' =>$row->designation_name,
'contact_no' =>$row->admin_telephone,
'email' =>$row->admin_email,
'team_name' =>team_name($row->admin_team),
'country' =>allowed_country_name_by_admin_id($row->admin_id),
'state' =>allowed_state_name_by_admin_id($row->admin_id),
'cities' =>allowed_city_name_by_admin_id($row->admin_id),




	
	
	 
    ];
});


$rs_role 				= json_decode($admin_details);


//print_r($result_enq);

//exit;
   return response()->json([            
            'assign_enq_to_selected_acc_manager_list' => $rs_role,
			'num_rows' => $num_rows,
			
        ]);
    }


public function assigned_enquiry_conversion_funnel(Request $request)
    {

	 	$financial_year		= $request->financial_year; 
        $acc_manager		= $request->acc_manager;   
		$month				= $request->month;   
       
        if($financial_year=='')
        {
            if (date('m')>3) {
            $year = date('Y').'-'.(date('Y')+1);
        } else {
            $year = (date('Y')-1).'-'.date('Y');
        }

        $financial_year=$year;

       $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode=explode("-",$financial_year);
        $fin_yr=show_financial_year_id($financial_year);

        
        if($financial_year_explode[0]!='' && $financial_year_explode[0]!='0')
        { 
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q1_end_date_show=$financial_year_explode[0].'-06-30';
            $q1_start_date_show_may=$financial_year_explode[0].'-05-01';
            $q1_start_date_show_jun=$financial_year_explode[0].'-06-01';
            $q1_end_date_show_apr=$financial_year_explode[0].'-04-30';
            $q1_end_date_show_may=$financial_year_explode[0].'-05-31';


            $q2_start_date_show=$financial_year_explode[0].'-07-01';
            $q2_end_date_show=$financial_year_explode[0].'-09-30';

            $q2_start_date_show_aug=$financial_year_explode[0].'-08-01';
            $q2_start_date_show_sept=$financial_year_explode[0].'-09-01';
            $q2_end_date_show_jul=$financial_year_explode[0].'-07-30';
            $q2_end_date_show_aug=$financial_year_explode[0].'-08-31';


            $q3_start_date_show=$financial_year_explode[0].'-10-01';
            $q3_end_date_show=$financial_year_explode[0].'-12-31';

            $q3_start_date_show_nov=$financial_year_explode[0].'-11-01';
            $q3_start_date_show_dec=$financial_year_explode[0].'-12-01';
            $q3_end_date_show_nov=$financial_year_explode[0].'-11-30';
            $q3_end_date_show_oct=$financial_year_explode[0].'-10-31';

            $q4_start_date_show=$financial_year_explode[1].'-01-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';

            $q4_start_date_show_feb=$financial_year_explode[1].'-02-01';
            $q4_start_date_show_march=$financial_year_explode[1].'-03-01';
            $q4_end_date_show_feb=$financial_year_explode[1].'-02-29';
            $q4_end_date_show_jan=$financial_year_explode[1].'-01-31';
        }
        else
        {
            $financial_year_explode[0]="2016";
            $financial_year_explode[1]="2026";
            $q1_start_date_show=$financial_year_explode[0].'-04-01';
            $q4_end_date_show=$financial_year_explode[1].'-03-31';
        }   

if($month!='' && $month!='0')
{
	
$month_search= " AND tbl_web_enq.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
}
else
{
$month_search= " ";//  -- Filter for April & December
}

if($acc_manager!='' && $acc_manager!='0')
{
	
$acc_manager_search= " AND tbl_web_enq_edit.acc_manager = '$acc_manager' ";//  -- Filter for April & December
}
else
{
$acc_manager_search= " ";//  -- Filter for April & December
}
//echo $q4_end_date_show;

     $assigned_enquiry_conversion_funnel 	= "SELECT 
    YEAR(tbl_web_enq.Enq_Date) AS year,
    MONTHNAME(tbl_web_enq.Enq_Date) AS month_name,
    MONTH(tbl_web_enq.Enq_Date) AS month,
    
    COUNT(CASE WHEN tbl_web_enq_edit.order_id IS NOT NULL and tbl_web_enq_edit.enq_stage > 4   THEN 1 END) AS offers_confirmed_count,
    COUNT(CASE WHEN tbl_web_enq_edit.lead_id IS NOT NULL AND tbl_web_enq_edit.order_id != 0 THEN 1 END) AS offers_sent_count,
    COUNT(CASE WHEN tbl_web_enq_edit.lead_id IS NOT NULL AND tbl_web_enq_edit.order_id = 0 THEN 1 END) AS leads_count,
    COUNT(CASE WHEN tbl_web_enq_edit.acc_manager IS NOT NULL THEN 1 END) AS assigned_count,
    
    COUNT(tbl_web_enq.ID) AS total_inquiries
FROM 
    tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
WHERE tbl_web_enq.dead_duck = '0' 
  AND tbl_web_enq.deleteflag = 'active'
  $month_search
  $acc_manager_search
  AND tbl_web_enq.Enq_Date BETWEEN '$q1_start_date_show' AND '$q4_end_date_show'  
GROUP BY 
    YEAR(tbl_web_enq.Enq_Date),
    MONTH(tbl_web_enq.Enq_Date)
ORDER BY 
     year ASC, month asc";
	 
$rs_assigned_enquiry_conversion_funnel=  DB::select(($assigned_enquiry_conversion_funnel)); 
   return response()->json([            
            'assigned_enquiry_conversion_funnel' => $rs_assigned_enquiry_conversion_funnel,
			'fin_yr_id' => $fin_yr,
			
        ]);
    }
	
	
	
 public function  inside_set_a_reminder(Request $request)
    {
    
        $date 	  				= date('Y-m-d');
        $ID						= $request->ID;
        $EID					= $request->enq_id;
        $follow_up_date			= $request->follow_up_date;
		$current_user_id		= $request->current_user_id;
		$enq_remark_edited		= "Remind me on this date: ".$follow_up_date;
        $snooze_days			= "0";

		$ArrayDataedited['remind_me'] 			= $follow_up_date;	
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

       $query = DB::table('tbl_web_enq_edit')
            ->where('enq_id', $EID)
            ->update($ArrayDataedited);
			
			
/*$query = DB::table('tbl_web_enq_edit')
    ->where('enq_id', $EID);

$sql = $query->toSql();
$bindings = $query->getBindings();

dd(vsprintf(str_replace('?', '%s', $sql), $bindings));	*/		

  /*      DB::table('tbl_web_enq')
            ->where('ID', $ID)
            ->update($ArrayData);
*/        
        //$currentuserid = Auth::user()->id; 
        $fileArray_tbl_enq_remarks["enq_id"]					= $EID;
        $fileArray_tbl_enq_remarks["remarks"]					= $enq_remark_edited;
    //    $fileArray_tbl_enq_remarks["dead_duck"]					= $dead_duck;
        $fileArray_tbl_enq_remarks["added_by"]					= $current_user_id;
        $fileArray_tbl_enq_remarks["remarks_added_date_time"]	= date("Y-m-d H:i:s");
//        $fileArray_tbl_enq_remarks["snooze_days"]				= $snooze_days;
  //      $fileArray_tbl_enq_remarks["snooze_date"]				= $snooze_date;

       $insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 
       if($insId){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

        return response()->json([            
        'message' => $msg, 
        ]);
    }	
	
}//class closed