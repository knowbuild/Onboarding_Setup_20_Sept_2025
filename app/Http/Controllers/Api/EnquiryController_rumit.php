<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Services\SendGridService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;

class EnquiryController extends Controller
{
	
    protected $sendGridService;

    public function __construct(SendGridService $sendGridService)
    {
        $this->sendGridService = $sendGridService;
    }
	
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
       
	 $categories = [];
        $condArr = [];
        $orderArr = [];
//        $AdminLoginID_SET = Auth::user()->id;
	   $acc_manager_request            = $request->acc_manager;
       // $hot_offer_month= $request->hot_offer_month;         
        //$financial_year		= $request->financial_year; 

			$AdminLoginID_SET			= $request->AdminLoginID_SET;
		    $admin_role_id				= $request->admin_role_id;
			$data_action				= $request->action;
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
            $hot_enquiry_search			= $request->hot_enquiry;	
//            $enq_id_search				= $request->enq_id_search;
            $enq_status					= $request->dead_duck;
			$hvc_search_filter			= $request->hvc_search_filter;
            $search_from 				= $request->date_from;
		    $datepicker_to 				= $request->date_to;
			$enq_type 					= $request->enq_type;
			$price_type 				= $request->Price_type;			

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
	$enq_id_search_filter=" and tbl_web_enq_edit.ID = '$enq_id_search' ";
}

else
{
		$enq_id_search_filter="  ";
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
		
$date_range_search=" AND (date( tbl_web_enq_edit.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}

if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq_edit.dead_duck='$enq_status'";
	
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
	if($ref_source_request!='' && $ref_source_request!='0')
	{
	$ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
	}
	else
	{
		$ref_source_search=" ";
	}
	 
  	$searchRecord=$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$hot_enquiry_search_search.$search_by_keyword.$offer_created_search;//"and acc_manager=$acc_manager_lead";
/*$sql	= "SELECT 
tbl_order.hot_offer, 
tbl_order.orders_id, 
tbl_order.offercode,
tbl_order.total_order_cost,
tbl_order.follow_up_date,
tbl_order.Price_value,
tbl_order.orders_status,
tbl_order.date_ordered,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq_edit.remind_me, 
tbl_web_enq_edit.ID,
tbl_web_enq_edit.enq_id,
tbl_web_enq_edit.hot_enquiry,
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.Cus_name,
tbl_web_enq_edit.Cus_email,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.Cus_mob,
tbl_web_enq_edit.Cus_msg,
tbl_web_enq_edit.city,
tbl_web_enq_edit.state,
tbl_web_enq_edit.ref_source,
tbl_web_enq_edit.cust_segment,
DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
tbl_web_enq_edit.old_enq_date,  
tbl_web_enq_edit.hot_productnote,
tbl_web_enq_edit.hot_productnoteother,
tbl_web_enq_edit.dead_duck,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.product_category,
tbl_web_enq_edit.deleteflag,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.price_type,
tbl_web_enq_edit.mel_updated_on,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
tbl_enq_source.enq_source_name,
tbl_admin.admin_fname,
tbl_admin.admin_lname
FROM tbl_order
RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq_edit.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
where tbl_web_enq_edit.deleteflag='active' $searchRecord order by $order_by  $order  LIMIT $from, $max_results";
$result_enq =  DB::select(($sql));*/


$sql	= "SELECT 
tbl_web_enq_edit.order_id, 
tbl_web_enq_edit.lead_id,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq_edit.remind_me, 
tbl_web_enq_edit.ID,
tbl_web_enq_edit.enq_id,
tbl_web_enq_edit.price_type,
tbl_web_enq_edit.hot_enquiry,
tbl_web_enq_edit.Cus_name,
tbl_web_enq_edit.Cus_email,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.Cus_mob,
tbl_web_enq_edit.Cus_msg,
tbl_web_enq_edit.city,
tbl_web_enq_edit.state,
tbl_web_enq_edit.ref_source,
tbl_web_enq_edit.cust_segment,
DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
tbl_web_enq_edit.old_enq_date,  
tbl_web_enq_edit.hot_productnote,
tbl_web_enq_edit.hot_productnoteother,
tbl_web_enq_edit.dead_duck,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.product_category,
tbl_web_enq_edit.deleteflag,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.mel_updated_on
FROM tbl_web_enq_edit 

where tbl_web_enq_edit.deleteflag='active' $searchRecord order by $order_by  $order  LIMIT $from, $max_results";
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

$cust_segment					= $row->cust_segment;//lead_cust_segment($lead_id);	
$cust_segment_name				= lead_cust_segment_name($cust_segment);
if(!empty($lead_id))
{
$comp_person_id					= get_comp_person_id_by_lead_id($lead_id);
$app_cat_id						= get_app_cat_id_by_lead_id($lead_id);
$application_name				= application_name($app_cat_id);
}
else
{
$comp_person_id					= "0";//get_comp_person_id_by_lead_id($lead_id);
$app_cat_id						= "0";//get_app_cat_id_by_lead_id($lead_id);
$application_name				= "0";//application_name($app_cat_id);

}
$sales_cycle_duration			= case_duration_as_per_segment($cust_segment);
$enq_source_name				= enq_source_name($row->ref_source);	
$acc_manager_name				= admin_name($row->acc_manager);
	


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
$tasks_details_array 			= getTaskList($orders_id);

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
}


return [
'ID' =>$row->ID,
'enq_id' =>$row->enq_id,
'order_id' =>$row->order_id,
'price_type' =>$row->price_type,
'hot_enquiry' =>$row->hot_enquiry,
'lead_id' =>$row->lead_id,
'Cus_name' =>$row->Cus_name,
'Cus_email' =>$row->Cus_email,
'enq_type' =>$row->enq_type,
'Cus_mob' =>$row->Cus_mob,
'Cus_msg' =>$row->Cus_msg,
'city' =>$row->city,
'state' =>$row->state,
'ref_source' =>$row->ref_source,
'cust_segment' =>$row->cust_segment,
'Enq_Date' =>$row->Enq_Date,
'old_enq_date' =>$row->old_enq_date,  
'hot_productnote' =>$row->hot_productnote,
'hot_productnoteother' =>$row->hot_productnoteother,
'dead_duck' =>$row->dead_duck,
'enq_remark_edited' =>$row->enq_remark_edited,
'acc_manager' =>$row->acc_manager,
'product_category' =>$row->product_category,
'deleteflag' =>$row->deleteflag,
'enq_stage' =>$row->enq_stage,
'mel_updated_on' =>$row->mel_updated_on,
'application_name' =>$application_name,
'cust_segment_name' =>$cust_segment_name,
'enq_source_name' =>$enq_source_name,
'admin_fname' =>$acc_manager_name,
'company_full_name' =>$company_full_name,
'days_since_enq' => $row->days_since_enq,
'app_cat_id' => $app_cat_id,
'comp_person_id' => $comp_person_id,
'price_type' => $row->price_type,
'ref_source' => $row->ref_source,
'cust_segment' => $row->cust_segment,
'enq_remark_edited' => $row->enq_remark_edited,
'orders_id' => $orders_id,
'lead_id' => $row->lead_id,
'proforma_invoice_id' => $pi_id,
"offer_details"=>$offer_details,
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
//$result_offer 					= DB::select(($sql_offer));	
//$data							= $offers;







$sql_paging_and_export_excel	= "SELECT 
tbl_web_enq_edit.order_id, 
tbl_web_enq_edit.enq_id
FROM tbl_web_enq_edit 
where tbl_web_enq_edit.deleteflag='active' $searchRecord order by $order_by  $order  ";
$result_enq_paging_export_to_excel =  DB::select(($sql_paging_and_export_excel));					
$num_rows	= count($result_enq_paging_export_to_excel); 	  
	return response()->json([ 
			'enquiry_data' => $result_enq,
			'export_enquiry_data' => $result_enq,
			'num_rows_count' => $num_rows
		]);
		
}


public function enq_listing_export_to_excel(Request $request)
    {
       
	 $categories = [];
        $condArr = [];
        $orderArr = [];
//        $AdminLoginID_SET = Auth::user()->id;
	   $acc_manager_request            = $request->acc_manager;
       // $hot_offer_month= $request->hot_offer_month;         
        //$financial_year		= $request->financial_year; 

			$AdminLoginID_SET			= $request->AdminLoginID_SET;
		    $admin_role_id				= $request->admin_role_id;
			$data_action				= $request->action;
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
            $hot_enquiry_search			= $request->hot_enquiry;	
//            $enq_id_search				= $request->enq_id_search;
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
    	$max_results = 500; 
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
	$enq_id_search_filter=" and tbl_web_enq_edit.ID = '$enq_id_search' ";
}

else
{
		$enq_id_search_filter="  ";
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
		
$date_range_search=" AND (date( tbl_web_enq_edit.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}

if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq_edit.dead_duck='$enq_status'";
	
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


if($cust_segment!='' && $cust_segment!='0')
{
	
	$cust_segment_search=" AND tbl_web_enq_edit.cust_segment='$cust_segment'";

}
else
{
	$cust_segment_search=" ";
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
	if($ref_source_request!='' && $ref_source_request!='0')
	{
	$ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
	}
	else
	{
		$ref_source_search=" ";
	}
	 
  	$searchRecord=$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$hot_enquiry_search_search.$search_by_keyword.$offer_created_search;//"and acc_manager=$acc_manager_lead";
/*$sql	= "SELECT 
tbl_order.hot_offer, 
tbl_order.orders_id, 
tbl_order.offercode,
tbl_order.total_order_cost,
tbl_order.follow_up_date,
tbl_order.Price_value,
tbl_order.orders_status,
tbl_order.date_ordered,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq_edit.remind_me, 
tbl_web_enq_edit.ID,
tbl_web_enq_edit.enq_id,
tbl_web_enq_edit.hot_enquiry,
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.Cus_name,
tbl_web_enq_edit.Cus_email,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.Cus_mob,
tbl_web_enq_edit.Cus_msg,
tbl_web_enq_edit.city,
tbl_web_enq_edit.state,
tbl_web_enq_edit.ref_source,
tbl_web_enq_edit.cust_segment,
DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
tbl_web_enq_edit.old_enq_date,  
tbl_web_enq_edit.hot_productnote,
tbl_web_enq_edit.hot_productnoteother,
tbl_web_enq_edit.dead_duck,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.product_category,
tbl_web_enq_edit.deleteflag,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.mel_updated_on,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
tbl_enq_source.enq_source_name,
tbl_admin.admin_fname,
tbl_admin.admin_lname
FROM tbl_order
RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq_edit.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
where tbl_web_enq_edit.deleteflag='active' $searchRecord order by $order_by  $order  LIMIT $from, $max_results";
$result_enq =  DB::select(($sql));*/



$sql_paging_and_export_excel	= "SELECT 
tbl_order.hot_offer, 

tbl_order.orders_id, 
tbl_order.offercode,
tbl_order.total_order_cost,
tbl_order.follow_up_date,
tbl_order.Price_value,
tbl_order.orders_status,
tbl_order.date_ordered,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq_edit.remind_me, 
tbl_web_enq_edit.ID,
tbl_web_enq_edit.enq_id,
tbl_web_enq_edit.hot_enquiry,
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.Cus_name,
tbl_web_enq_edit.Cus_email,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.Cus_mob,
tbl_web_enq_edit.Cus_msg,
tbl_web_enq_edit.city,
tbl_web_enq_edit.state,
tbl_web_enq_edit.ref_source,
tbl_web_enq_edit.cust_segment,
DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
tbl_web_enq_edit.old_enq_date,  
tbl_web_enq_edit.hot_productnote,
tbl_web_enq_edit.hot_productnoteother,
tbl_web_enq_edit.dead_duck,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.product_category,
tbl_web_enq_edit.deleteflag,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.price_type,
tbl_web_enq_edit.mel_updated_on,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
tbl_enq_source.enq_source_name,
CONCAT(tbl_admin.admin_fname,tbl_admin.admin_lname) as acc_manager_name,
tbl_stage_master.stage_name
FROM tbl_order
RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq_edit.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
LEFT JOIN tbl_stage_master ON tbl_stage_master.stage_id=tbl_web_enq_edit.enq_stage
where tbl_web_enq_edit.deleteflag='active' $searchRecord order by $order_by  $order  LIMIT $from, $max_results ";
$result_enq_paging_export_to_excel =  DB::select(($sql_paging_and_export_excel));					
$num_rows	= count($result_enq_paging_export_to_excel); 	  
	return response()->json([ 
			'export_enquiry_data' => $result_enq_paging_export_to_excel,
			'num_rows_count' => $num_rows
		]);
		
}
	
	
	
//new add_view_enq



    public function listing_add_view_enq(Request $request)
    {
       
	 	$categories 	= [];
        $condArr 		= [];
        $orderArr 		= [];
//        $AdminLoginID_SET = Auth::user()->id;
		  $acc_manager_request          = $request->acc_manager;
       // $hot_offer_month= $request->hot_offer_month;         
        //$financial_year		= $request->financial_year; 

			$AdminLoginID_SET			= $request->AdminLoginID_SET;
		    $admin_role_id				= $request->admin_role_id;
			$data_action				= $request->action;
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
            $hot_enquiry_search			= $request->hot_enquiry;	
//            $enq_id_search				= $request->enq_id_search;
            $enq_status					= $request->dead_duck;
			$hvc_search_filter			= $request->hvc_search_filter;
            $search_from 				= $request->date_from;
		    $datepicker_to 				= $request->date_to;
			$enq_type 					= $request->enq_type;
			$price_type 				= $request->Price_type;			

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
	$enq_id_search_filter=" and tbl_web_enq_edit.ID = '$enq_id_search' ";
}

else
{
		$enq_id_search_filter="  ";
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
		
$date_range_search=" AND (date( tbl_web_enq_edit.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}

if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq_edit.dead_duck='$enq_status'";
	
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
	if($ref_source_request!='' && $ref_source_request!='0')
	{
	$ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
	}
	else
	{
		$ref_source_search=" ";
	}
	 
  	$searchRecord=$acc_manager_search.$ref_source_search.$lead_created_search.$date_range_search.$enq_status_search.$enq_type_search.$app_cat_id_search_search.$cust_segment_search.$offer_probability_search_search.$last_updated_on_search.$hvc_search_filter.$enq_id_search_filter.$hot_enquiry_search_search.$hot_enquiry_search_search.$search_by_keyword.$offer_created_search;
	//"and acc_manager=$acc_manager_lead";
$sql	= "SELECT 
tbl_order.hot_offer, 
tbl_order.orders_id, 
tbl_order.offercode,
tbl_order.total_order_cost,
tbl_order.follow_up_date,
tbl_order.Price_value,
tbl_order.orders_status,
tbl_order.date_ordered,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq_edit.remind_me, 
tbl_web_enq_edit.ID,
tbl_web_enq_edit.enq_id,
tbl_web_enq_edit.hot_enquiry,
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.Cus_name,
tbl_web_enq_edit.Cus_email,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.Cus_mob,
tbl_web_enq_edit.Cus_msg,
tbl_web_enq_edit.city,
tbl_web_enq_edit.state,
tbl_web_enq_edit.ref_source,
tbl_web_enq_edit.cust_segment,
DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
tbl_web_enq_edit.old_enq_date,  
tbl_web_enq_edit.hot_productnote,
tbl_web_enq_edit.hot_productnoteother,
tbl_web_enq_edit.dead_duck,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.product_category,
tbl_web_enq_edit.deleteflag,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.price_type,
tbl_web_enq_edit.mel_updated_on,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
tbl_enq_source.enq_source_name,
tbl_admin.admin_fname,
tbl_admin.admin_lname
FROM tbl_order

INNER JOIN 
    tbl_order_product AS t2 ON tbl_order.orders_id = t2.order_id
INNER JOIN 
    tbl_lead AS t3 ON tbl_order.lead_id = t3.id
LEFT JOIN 
    tbl_comp ON tbl_comp.id = tbl_order.customers_id
INNER JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = t3.app_cat_id  
LEFT JOIN 
    tbl_enq_source ON tbl_enq_source.enq_source_description = t3.ref_source
LEFT JOIN 
    tbl_admin ON tbl_admin.admin_id = tbl_order.order_by
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_order.edited_enq_id
LEFT JOIN tbl_country 
    ON tbl_country.country_id = tbl_order.shipping_country_name
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = tbl_order.shipping_state 
LEFT JOIN all_cities 
    ON all_cities.city_id = tbl_order.shipping_city 
LEFT JOIN 
tbl_performa_invoice as tpi  ON tpi.O_Id = tbl_order.orders_id 	
LEFT JOIN events AS ev ON ev.lead_type = tbl_order.orders_id 	



RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq_edit.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
where tbl_web_enq_edit.deleteflag='active' $searchRecord order by $order_by  $order  LIMIT $from, $max_results";
$result_enq =  DB::select(($sql));



$sql_paging_and_export_excel	= "SELECT 
tbl_order.hot_offer, 
tbl_order.orders_id, 
tbl_order.offercode,
tbl_order.total_order_cost,
tbl_order.follow_up_date,
tbl_order.Price_value,
tbl_order.orders_status,
tbl_order.date_ordered,
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq_edit.remind_me, 
tbl_web_enq_edit.ID,
tbl_web_enq_edit.enq_id,
tbl_web_enq_edit.hot_enquiry,
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.Cus_name,
tbl_web_enq_edit.Cus_email,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.Cus_mob,
tbl_web_enq_edit.Cus_msg,
tbl_web_enq_edit.city,
tbl_web_enq_edit.state,
tbl_web_enq_edit.ref_source,
tbl_web_enq_edit.cust_segment,
DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
tbl_web_enq_edit.old_enq_date,  
tbl_web_enq_edit.hot_productnote,
tbl_web_enq_edit.hot_productnoteother,
tbl_web_enq_edit.dead_duck,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.product_category,
tbl_web_enq_edit.deleteflag,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.price_type,
tbl_web_enq_edit.mel_updated_on,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
tbl_enq_source.enq_source_name,
CONCAT(tbl_admin.admin_fname,tbl_admin.admin_lname) as acc_manager_name,
tbl_stage_master.stage_name
FROM tbl_order
RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq_edit.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
LEFT JOIN tbl_stage_master ON tbl_stage_master.stage_id=tbl_web_enq_edit.enq_stage
where tbl_web_enq_edit.deleteflag='active' $searchRecord order by $order_by  $order  ";
$result_enq_paging_export_to_excel =  DB::select(($sql_paging_and_export_excel));					
$num_rows	= count($result_enq_paging_export_to_excel); 	  
	return response()->json([ 
			'enquiry_data' => $result_enq,
			'export_enquiry_data' => $result_enq,
			'num_rows_count' => $num_rows
		]);
		
}	
	
 public function  generate_eid(Request $request)
    {
		
/*		echo "<pre>";
		print_r($_REQUEST);*/
		$currentuserid 							= $_REQUEST["currentuserid"];//Auth::user()->id;  exit;

		$today									= date("Y-m-d H:i:s");	
		$fileArray_e["Cus_name"]				= addslashes($_REQUEST["enq_cust_name"]);
		$fileArray_e["Cus_email"]				= addslashes($_REQUEST["enq_cust_email"]);
		$fileArray_e["Cus_mob"]					= addslashes($_REQUEST["enq_cust_mobile"]);		
		$fileArray_e["Cus_msg"]					= addslashes($_REQUEST["Cus_msg"]);		
		$fileArray_e["product_category"]		= $_REQUEST["app_cat_id"];		
		$fileArray_e["added_by_acc_manager"]	= $currentuserid;		
		$fileArray_e["ref_source"]				= $_REQUEST["ref_source"];
		$fileArray_e["Enq_Date"]				= $today;//"2019-01-30 05:10:36";//$_REQUEST["Cus_msg"];	
 		//$inserted_enq_id 						= DB::table('tbl_web_enq')->insert($fileArray_e);//$s->insertRecord('tbl_web_enq',$fileArray_e);	
	    $inserted_enq_id 						= DB::table('tbl_web_enq')->insertGetId($fileArray_e);	

//       $inserted_enq_id = DB::table('tbl_web_enq_edit')->insert($fileArray); 
	   
	   
	   	$fileArray["enq_id"]					= $inserted_enq_id;//$_REQUEST["enq_id"];
		$fileArray["acc_manager"]				= addslashes($_REQUEST["acc_manager"]);
		$fileArray["Cus_name"]					= addslashes($_REQUEST["enq_cust_name"]);
		$fileArray["Cus_email"]					= addslashes($_REQUEST["enq_cust_email"]);
		$fileArray["Cus_mob"]					= addslashes($_REQUEST["enq_cust_mobile"]);		
		$fileArray["Cus_msg"]					= addslashes($_REQUEST["Cus_msg"]);		
		$fileArray["country"]					= addslashes($_REQUEST["country"]);		
		$fileArray["city"]						= addslashes($_REQUEST["enq_cust_city"]);
		$fileArray["state"]						= addslashes($_REQUEST["enq_cust_state"]);
		$fileArray["acc_manager"]				= addslashes($_REQUEST["acc_manager"]);
		$fileArray["ref_source"]				= addslashes($_REQUEST["ref_source"]);
		$fileArray["cust_segment"]				= addslashes($_REQUEST["cust_segment"]);
		$fileArray["product_category"]			= addslashes($_REQUEST["app_cat_id"]);		
		$fileArray["enq_type"]					= addslashes($_REQUEST["enq_type"]);		
		$fileArray["price_type"]				= addslashes($_REQUEST["Price_type"]);		
		$fileArray["Enq_Date"]					= $today;//"2019-01-30 05:10:36";//$_REQUEST["Cus_msg"];
		$fileArray["old_enq_date"]				= $today;//"2019-01-30 05:10:36";//$_REQUEST["Cus_msg"];	
		$fileArray["assigned_by"]				= $currentuserid;//$_SESSION["AdminLoginID_SET"];
	    $insId 						= DB::table('tbl_web_enq_edit')->insertGetId($fileArray);
	   
		//$insId = $s->insertRecord('tbl_web_enq_edit',$fileArray);	
	   
       if($insId){
            $msg = array("msg"=>"true","EID"=>$insId);
			
        }else{
            $msg = array("msg"=>"false","EID"=>0);
       };  

        return response()->json([            
        'message' => $msg, 
        ]);
    }

 public function  add_new_enquiry(Request $request)
    {

		echo "curr id:".$currentuserid = $request->currentuserid;//Auth::user()->id;  exit;
		if($request->enq_cust_name!='' && $request->enq_cust_email!='' && $request->enq_cust_mobile!='' && $request->ref_source!='')
		{
		$today								= date("Y-m-d H:i:s");
//		$fileArray["enq_id"]			= $_REQUEST["enq_id"];
		$fileArray["Cus_name"]				= addslashes($request->enq_cust_name);
		$fileArray["Cus_email"]				= addslashes($request->enq_cust_email);
		$fileArray["Cus_mob"]				= addslashes($request->enq_cust_mobile);		
		$fileArray["Cus_msg"]				= addslashes($request->Cus_msg);		
		$fileArray["ref_source"]			= addslashes($request->ref_source);
		$fileArray["Enq_Date"]				= $today;//"2019-01-30 05:10:36";//$_REQUEST["Cus_msg"];	
		$fileArray["added_by_acc_manager"]	= $currentuserid;//addslashes($_SESSION["AdminLoginID_SET"]);		
	    $inserted_id 						= DB::table('tbl_web_enq')->insertGetId($fileArray);
		
	   
       if($inserted_id){
            $msg = array("msg"=>true,"enq_inserted_id"=>$inserted_id); 
        }else{
            $msg = array("false"=>true); 
       };  
		}
		else
		{
			$msg = array("msg"=>"Error: Parameters missing, all fields are required");
		}
        return response()->json([            
        'message' => $msg, 
        ]);
    }


    public function enq_stage()
    {
        $rs_enq_stage_master 	= DB::table('tbl_stage_master')->where('deleteflag', '=', 'active')->orderby('stage_id','asc')->select('stage_id','stage_name')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'enq_stage' => $rs_enq_stage_master, 
        ]);
    }












    public function service_period_master()
    {
        $rs_service_period_master 	= DB::table('tbl_service_master')->where('deleteflag', '=', 'active')->orderby('service_id','asc')->select('service_id','service_name','service_abbrv')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'service_period_master' => $rs_service_period_master, 
        ]);
    }

	
	    public function service_categories()
    {
        $rs_service_categories 	= DB::table('tbl_application_service')->where('deleteflag', '=', 'active')->orderby('application_service_name','asc')->select('application_service_id','application_service_name')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'service_categories' => $rs_service_categories, 
        ]);
    }



	    public function product_categories()
    {
        $rs_product_categories 	= DB::table('tbl_application')->where('deleteflag', '=', 'active')->orderby('application_name','asc')->select('application_id','application_name')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'product_categories' => $rs_product_categories, 
        ]);
    }

	
	
	    public function cust_segment()
    {
        $rs_cust_segment 	= DB::table('tbl_cust_segment')->where('deleteflag', '=', 'active')->orderby('cust_segment_name','asc')->select('cust_segment_id','cust_segment_name')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'cust_segment' => $rs_cust_segment, 
        ]);
    }


	    public function enquiry_source()
    {
        $rs_enquiry_source 	= DB::table('tbl_enq_source')->where('deleteflag', '=', 'active')->orderby('enq_source_name','asc')->select('enq_source_name','enq_source_name','enq_source_name','enq_source_description','enq_source_icon')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'enquiry_source' => $rs_enquiry_source, 
        ]);
    }


	    public function enquiry_status_master()
    {
        $rs_enquiry_status_master 	= DB::table('tbl_enq_status_master')->where('deleteflag', '=', 'active')->orderby('enq_status_name','asc')->select('enq_status_id','enq_status_name')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'enquiry_status_master' => $rs_enquiry_status_master, 
        ]);
    }

    public function get_edit_enquiry_data(Request $request)
    {
    
if(isset($request->id)){             
$ID					=	$request->id;
$sql_enq_details	= "SELECT 
DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
tbl_web_enq_edit.remind_me, 
tbl_web_enq_edit.acc_manager, 
tbl_web_enq_edit.ID,
tbl_web_enq_edit.enq_id,
tbl_web_enq_edit.hot_enquiry,
tbl_web_enq_edit.order_id,
tbl_web_enq_edit.lead_id,
tbl_web_enq_edit.Cus_name,
tbl_web_enq_edit.Cus_email,     
tbl_web_enq_edit.Cus_mob,
tbl_web_enq_edit.Cus_msg,
tbl_web_enq_edit.enq_type,
tbl_web_enq_edit.Price_type,
tbl_web_enq_edit.country,
tbl_web_enq_edit.state,
tbl_web_enq_edit.city,
tbl_web_enq_edit.address,
tbl_web_enq_edit.ref_source,
tbl_web_enq_edit.cust_segment,
DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
tbl_web_enq_edit.old_enq_date,  
tbl_web_enq_edit.hot_productnote,
tbl_web_enq_edit.hot_productnoteother,
tbl_web_enq_edit.dead_duck,
tbl_web_enq_edit.enq_remark_edited,
tbl_web_enq_edit.acc_manager,
tbl_web_enq_edit.product_category,
tbl_web_enq_edit.deleteflag,
tbl_web_enq_edit.enq_stage,
tbl_web_enq_edit.price_type,
tbl_web_enq_edit.mel_updated_on,
tbl_application.application_name,
tbl_cust_segment.cust_segment_name,
tbl_enq_source.enq_source_name
FROM tbl_web_enq_edit
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id=tbl_web_enq_edit.cust_segment  
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq_edit.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq_edit.ref_source
where tbl_web_enq_edit.deleteflag='active' 
and tbl_web_enq_edit.ID='$ID'";
$enq_details_data 		=  DB::select(($sql_enq_details));							
//	exit;

/*$enq_details_data = DB::table('tbl_web_enq_edit')->where('ID',$request->id)->get(['ID','enq_id','lead_id','order_id','Cus_name','Cus_email','Cus_mob','country','city','state','ref_source','cust_segment','country','hot_productnote','hot_productnoteother','Enq_Date','enq_remark_edited','enq_stage','snooze_days','snooze_date','hot_enquiry','Cus_msg','assigned_by','remind_me','acc_manager']);          */
$msg = array("msg"=>"true","get_edit_enquiry_data"=>$enq_details_data);

        }
		
else
{
          $msg = array("msg"=>"false","get_edit_enquiry_data"=>"Enquiry id is missing: please pass id as parameter");
}

 return response()->json([            
            'enquiry_source' => $msg, 
        ]);		       
    }



//update enquiry details
    public function  edit_enquiry_details(Request $request)
    {
	    $ID						= $request->ID; 
        $date 	  				= date('Y-m-d');
        $dead_duck				= $request->dead_duck;
        $enq_remark_edited		= $request->enq_remark_edited;
        $Cus_name				= $request->Cus_name;
        $Cus_email				= $request->Cus_email;
        $Cus_mob				= $request->Cus_mob;
        $country				= $request->country;
        $city					= $request->city;
        $state					= $request->state;
		$address				= $request->address;
        $ref_source				= $request->ref_source;
        $cust_segment			= $request->cust_segment;
	    $acc_manager			= $request->acc_manager;
		$product_category		= $request->product_category;
        $hot_productnote		= $request->hot_productnote;
        $hot_productnoteother	= $request->hot_productnoteother;
        $enq_stage				= $request->enq_stage;
        $snooze_days			= $request->snooze_days;
        $hot_enquiry			= $request->hot_enquiry;
        $Cus_msg				= $request->Cus_msg;
        $assigned_by			= $request->assigned_by;//current user id
        $remind_me				= $request->remind_me;
		$enq_type				= $request->enq_type;
		$Price_type				= $request->Price_type;		
		


	//	echo "request:";
//exit;
        $ArrayDataedited['enq_type'] 			= $enq_type;
        $ArrayDataedited['price_type'] 			= $Price_type;
		$ArrayDataedited['dead_duck'] 			= $dead_duck;
        $ArrayDataedited['Cus_name'] 			= $Cus_name;	
        $ArrayDataedited['Cus_email'] 			= $Cus_email;	
        $ArrayDataedited['Cus_mob'] 			= $Cus_mob;	
        $ArrayDataedited['country'] 			= $country;	
        $ArrayDataedited['city'] 				= $city;	
        $ArrayDataedited['state'] 				= $state;	
        $ArrayDataedited['address'] 			= $address;			
        $ArrayDataedited['ref_source'] 			= $ref_source;			
        $ArrayDataedited['product_category'] 	= $product_category;			
        $ArrayDataedited['hot_productnote'] 	= $hot_productnote;			
        $ArrayDataedited['hot_productnoteother']= $hot_productnoteother;	
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;			
        $ArrayDataedited['hot_enquiry'] 		= $hot_enquiry;					
		$ArrayDataedited['Cus_msg'] 			= $Cus_msg;					
//		$ArrayDataedited['assigned_by'] 		= $assigned_by;							
        $ArrayDataedited['snooze_days'] 		= 0;
        $ArrayDataedited['snooze_date'] 		= date('Y-m-d h:m:s');
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

//print_r($ArrayDataedited);
        $insId= DB::table('tbl_web_enq_edit')
            ->where('ID', $ID)
            ->update($ArrayDataedited);

       
       if($insId){
	   $msg = array("msg"=>"true","edit_enquiry_details"=>"Details updated successfully!");
        }else{
        $msg =  array("msg"=>"false","edit_enquiry_details"=>"Parameters missing please provide all parameters");
       };  

        return response()->json([            
        'edit_enquiry_details' => $msg, 
        ]);
    }

//country list
   public function country()
    {
        $rs_country 	= DB::table('tbl_country')->where('deleteflag', '=', 'active')->orderby('country_name','asc')->select('country_id','country_name','country_code3')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'country' => $rs_country, 
        ]);
    }
	
	
//state list on the basis of country id
public function state(Request $request)
    {
	$country_id=$request->country_id;
	  if(isset($country_id))
	  {             
   $rs_state 	= DB::table('tbl_zones')->where('deleteflag', '=', 'active')->where('zone_country_id', '=', $country_id)->orderby('zone_name','asc')->select('zone_id','zone_name','state_code')->get();
   $msg = array("msg"=>"true","state"=>$rs_state);
       }
	else
	{
 $msg = array("msg"=>"false","state"=>"Country id is missing: please pass country_id as parameter");
	}
        // Logic for your dashboard, e.g., returning dashboard data.
  return response()->json([            
         'state' => $msg, 
        ]);
    }	


//city list on the basis of state id
public function city(Request $request)
    {
	$state_id=$request->state_id;
	  if(isset($state_id))
	  {             
   $rs_city 	= DB::table('all_cities')->where('deleteflag', '=', 'active')->where('state_code', '=', $state_id)->orderby('city_name','asc')->select('city_id','city_name','city_code')->get();
   $msg = array("msg"=>"true","city"=>$rs_city);
       } 
	else
	{
 $msg = array("msg"=>"false","city"=>"Country id is missing: please pass state_id as parameter");
	}
        // Logic for your dashboard, e.g., returning dashboard data.
  return response()->json([            
         'city' => $msg, 
        ]);
    }	

////Step 1-  search comp for convert to lead: search company on the basis of company name, mobile, sub person  name, emamil, mobile
public function getcompanyexists_table(Request $request)
    {
$search_by		=	$request->search_by;
$newsearch 		= 	addslashes(str_replace("_","&",$search_by));		
	  if(isset($search_by))
	  {             
  $search		="SELECT 
    tbl_comp.id AS company_id, 
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name) 
        ELSE tbl_comp.comp_name 
    END AS company_full_name, 
    MAX(tbl_comp_person.id) AS tbl_comp_person_id, -- Ensures only one person ID per company
    tbl_comp.comp_name, 
    tbl_comp.co_extn_id,
	tbl_comp.cust_segment, 
    tbl_comp.co_extn, 
    tbl_comp.office_type, 
    tbl_comp.co_division, 
    tbl_comp.address, 
	tbl_comp.zip, 
	tbl_country.country_name, 
    tbl_zones.zone_name, 
    all_cities.city_name,
	tbl_cust_segment.cust_segment_name
FROM tbl_comp 
LEFT JOIN tbl_comp_person 
    ON tbl_comp.id = tbl_comp_person.company_id 
LEFT JOIN tbl_cust_segment 
    ON tbl_cust_segment.cust_segment_id = tbl_comp.cust_segment 
LEFT JOIN tbl_country 
    ON tbl_country.country_id = tbl_comp.country 
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = tbl_comp.state 
LEFT JOIN all_cities 
    ON all_cities.city_id = tbl_comp.city 
LEFT JOIN tbl_company_extn 
    ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id  
WHERE 
    tbl_comp.deleteflag = 'active' 
	
AND (tbl_comp.comp_name like '%".$newsearch."%' OR tbl_comp.fname like '%".$newsearch."%' OR tbl_comp.mobile_no like '%".$newsearch."%' OR tbl_comp.zip like '%".$newsearch."%' OR tbl_comp.email like '%".$newsearch."%' 
OR tbl_comp_person.fname like '%".$newsearch."%'
OR tbl_comp_person.email like '%".$newsearch."%'
OR tbl_comp_person.mobile_no like '%".$newsearch."%')
 and india_mart_co='no' 
 
 GROUP BY 
    tbl_comp.id, 
    company_full_name, 
    tbl_comp.comp_name, 
    tbl_comp.co_extn_id, 
    tbl_comp.co_extn, 
    tbl_comp.office_type, 
    tbl_comp.co_division, 
    tbl_country.country_name, 
    tbl_zones.zone_name, 
    all_cities.city_name
 
 order by tbl_comp.comp_name asc limit 0,20";	  

/*echo $search		="select distinct(tbl_comp.id),tbl_comp_person.id as tbl_comp_person_id,
tbl_comp.comp_name,
tbl_comp.co_extn_id, tbl_comp.co_extn, tbl_comp.office_type,
tbl_comp.co_division,
tbl_comp.fname,
tbl_comp.lname,
tbl_comp.email,
tbl_comp.telephone,
tbl_comp.mobile_no,
tbl_comp.create_date
from tbl_comp 
LEFT join tbl_comp_person on tbl_comp.id=tbl_comp_person.company_id
LEFT join tbl_cust_segment on tbl_cust_segment.cust_segment_id=tbl_comp.cust_segment
LEFT join tbl_country on tbl_country.country_id=tbl_comp.country
LEFT join tbl_zones on tbl_zones.zone_id=tbl_comp.state
LEFT join all_cities on all_cities.city_id=tbl_comp.city

where tbl_comp.deleteflag = 'active' AND (tbl_comp.comp_name like '%".$newsearch."%' OR tbl_comp.fname like '%".$newsearch."%' OR tbl_comp.mobile_no like '%".$newsearch."%' OR tbl_comp.zip like '%".$newsearch."%' OR tbl_comp.email like '%".$newsearch."%' 
OR tbl_comp_person.fname like '%".$newsearch."%'
OR tbl_comp_person.email like '%".$newsearch."%'
OR tbl_comp_person.mobile_no like '%".$newsearch."%')
 and india_mart_co='no' order by tbl_comp.comp_name asc limit 0,10";	  */

$result_enq =  DB::select(($search));					
$num_rows	= count($result_enq); 	  
	  
if($num_rows>0)	  
{
   $msg = array("msg"=>"true","company_search_data"=>$result_enq);
}
else
{
	  $msg = array("msg"=>"false","company_search_data"=>"No records found");
}
       }
	else
	{
 $msg = array("msg"=>"false","company_search_data"=>"Search criteria is missing: please pass search_by as parameter");
	}
        // Logic for your dashboard, e.g., returning dashboard data.
  return response()->json([            
         'company_search_data' => $msg, 
        ]);
    }	


//Step 2- convert to lead get company persons list on the basis on company id
public function getcompanyexists_table_person_list(Request $request)
    {
$comp_id		=	$request->company_id;

	  if(isset($comp_id))
	  {             
 $sql_comp_person_details="SELECT 
    0 AS comp_person_id, -- Set comp_person_id to 0 for the first row
	c.salutation AS salutation,
    c.fname AS fname,
    c.lname AS lname,
    c.designation_id AS designation_id,
    d.designation_name AS designation_name, -- Fetch designation_name from tbl_designation
    c.email AS email,
    c.telephone AS telephone,
    c.mobile_no AS mobile_no,
    c.address AS address,
    c.city AS city,
    c.country AS country,
    c.state AS state,
    c.zip AS zip,
    c.department_id AS department_id,
    c.status AS status,
    c.deleteflag AS deleteflag,
	c.acc_manager AS acc_manager,
    tbl_country.country_name, 
    tbl_zones.zone_name, 
    all_cities.city_name,
    ta.admin_team,	
	ta.admin_fname,	
    ta.admin_lname,
    tbt.team_name

FROM 
    tbl_comp c
LEFT JOIN 
    tbl_designation_comp d ON c.designation_id = d.designation_id -- Join to get designation_name
LEFT JOIN 
    tbl_admin ta ON c.acc_manager = ta.admin_id 
LEFT JOIN 
    tbl_team tbt ON ta.admin_team = tbt.team_id	
LEFT JOIN tbl_country 
    ON tbl_country.country_id = c.country 
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = c.state 
LEFT JOIN all_cities 
    ON all_cities.city_id = c.city 


WHERE 
    c.id = '$comp_id'

UNION ALL

SELECT 
    cp.id AS comp_person_id, -- Keep comp_person_id as it is for tbl_comp_person
    cp.salutation as salutation,
    cp.fname AS fname,
    cp.lname AS lname,
    cp.designation_id AS designation_id,
    d.designation_name AS designation_name, -- Fetch designation_name from tbl_designation
    cp.email AS email,
    cp.telephone AS telephone,
    cp.mobile_no AS mobile_no,
    cp.address AS address,
    cp.city AS city,
    cp.country AS country,
    cp.state AS state,
    cp.zip AS zip,
    cp.department_id AS department_id,
    cp.status AS status,
    cp.deleteflag AS deleteflag,
	cp.acc_manager AS acc_manager,	
    tbl_country.country_name, 
    tbl_zones.zone_name, 
    all_cities.city_name,
    ta.admin_team,		
    ta.admin_fname,	
    ta.admin_lname,
    tbt.team_name	
FROM 
    tbl_comp_person cp
LEFT JOIN 
    tbl_designation_comp d ON cp.designation_id = d.designation_id -- Join to get designation_name

LEFT JOIN 
    tbl_admin ta ON cp.acc_manager = ta.admin_id  	
LEFT JOIN 
    tbl_team tbt ON ta.admin_team = tbt.team_id	
LEFT JOIN tbl_country 
    ON tbl_country.country_id = cp.country 
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = cp.state 
LEFT JOIN all_cities 
    ON all_cities.city_id = cp.city 	
WHERE 
    cp.deleteflag = 'active' AND cp.company_id = '$comp_id'	";	


$result_comp_person_details =  DB::select(($sql_comp_person_details));					
$num_rows	= count($result_comp_person_details); 	  
	  
if($num_rows>0)	  
{
   $msg = array("msg"=>"true","company_search_data"=>$result_comp_person_details);
}
else
{
	  $msg = array("msg"=>"false","company_search_data"=>"No records found");
}
       }
	else
	{
 $msg = array("msg"=>"false","company_search_data"=>"Search criteria is missing: please pass company_id as parameter");
	}
        // Logic for your dashboard, e.g., returning dashboard data.
  return response()->json([            
         'company_search_data' => $msg, 
        ]);
    }	



  public function  transfer_account_manager(Request $request)
    {
    
        $date 	  								= date('Y-m-d');
        $EID									= $request->enq_id;
		$lead_id 								= $request->lead_id;		
        $order_id								= $request->order_id;
		$transfer_account_manager_selected		= $request->transfer_account_manager_selected;
		
        $ArrayDataedited['acc_manager'] 		= $transfer_account_manager_selected;
		
if($EID!='' && $EID!='0')
{
$insId =  DB::table('tbl_web_enq_edit')
            ->where('ID', $EID)
            ->update($ArrayDataedited);
}
else
{
	$insId="1";
}

if($order_id!='' && $order_id!='0')
{
        $ArrayDataOrder['order_by'] 			= $transfer_account_manager_selected;		
		 $insIdo = DB::table('tbl_order')
		->where('orders_id', $order_id)
		->update($ArrayDataOrder);
	$insId="1";
}

if($lead_id!='' && $lead_id!='0')
{
        $ArrayDataLead['acc_manager'] 			= $transfer_account_manager_selected;		
		 $insIdlead = DB::table('tbl_lead')
		->where('id', $lead_id)
		->update($ArrayDataLead);
			$insId="1";
}


	if($insId){
	   $msg = array("msg"=>"true","transfer_enquiry"=>"Details updated successfully!");
        }else{
        $msg =  array("msg"=>"false","transfer_enquiry"=>"Parameters missing please provide all parameters");
       };  
 

        return response()->json([            
        'message' => $msg, 
        ]);
    }




public function  service_list_by_category_id(Request $request)
    {
   
$category_id		= $request->category_id;
$price_list			= $request->price_list;


if($category_id!='') {
//rumit
$search_cat_id		= " and tbl_index_s2.service_id='$category_id'";
	}
else {
$search_cat_id		= "";
	}	
	
	
if($price_list!='') {
//rumit

$search_price_list		= " and tbl_services_entry.price_list='$price_list'";
	}
else {
	$price_list				= "pvt";
$search_price_list		= " and tbl_services_entry.price_list='$price_list'";
	}	
	
$sql_services="Select 
tbl_services.service_id,
tbl_services_entry.service_id_entry,
tbl_services_entry.service_price_entry as service_price,
tbl_services_entry.price_list,
tbl_services_entry.model_no as service_model,
tbl_services_entry.hsn_code,
tbl_services.service_title ,
tbl_services.upc_code,
tbl_services.ware_house_stock,
tbl_services.service_max_discount,
tbl_index_s2.service_id as category_id,
tbl_application_service.application_service_name,
tbl_application_service.tax_class_id,
tbl_application_service.hsn_code,
tbl_application_service.cat_abrv

FROM 
tbl_services 
INNER JOIN tbl_index_s2 ON tbl_index_s2.match_service_id_s2=tbl_services.service_id 
INNER JOIN tbl_services_entry ON tbl_services_entry.service_id=tbl_services.service_id 
INNER JOIN tbl_application_service ON tbl_index_s2.service_id=tbl_application_service.application_service_id 
where tbl_services.deleteflag='active' and tbl_services.status='active'  
$search_price_list
$search_cat_id  GROUP by tbl_services.service_id  order by tbl_services.service_title asc ";


$result_services	=  DB::select(($sql_services));					
$num_rows			= count($result_services); 	  
if($num_rows>0)	  
{
   $msg = array("msg"=>"true","services_data"=>$result_services);
}
else
{
  $msg = array("msg"=>"false","services_data"=>"No records found");
}

  return response()->json([            
         'services_data' => $result_services, 
        ]);

}




public function  product_list_by_category_id(Request $request)
    {
   
$category_id		= $request->category_id;
$price_list			= $request->price_list;
if($category_id!='') {
//rumit
$search_cat_id		= " and tbl_index_g2.pro_id='$category_id'";
	}
else {
$search_cat_id		= "";
	}	
	
	
if($price_list!='') {
//rumit

$search_price_list		= " and tbl_products_entry.price_list='$price_list'";
	}
else {
	$price_list			= "pvt";
$search_price_list		= " and tbl_products_entry.price_list='$price_list'";
	}		
//$search		= "where tbl_products.pro_title like '%".$_REQUEST['search_by_name']."%' AND tbl_products.deleteflag = 'active'";
//}
$sql_products="Select 
tbl_products.pro_id,
tbl_products.qty_slab,
tbl_products_entry.pro_id_entry,
tbl_products_entry.pro_price_entry as pro_price,
tbl_products_entry.price_list,
tbl_products_entry.model_no as pro_model,
tbl_products_entry.hsn_code,
tbl_products.pro_title ,
tbl_products.upc_code,
tbl_products.ware_house_stock,
tbl_products.pro_max_discount,
tbl_index_g2.pro_id as category_id,
tbl_application.application_name,
tbl_application.tax_class_id,
tbl_application.hsn_code,
tbl_application.cat_warranty,
tbl_application.cat_abrv
FROM 
tbl_products 
INNER JOIN tbl_index_g2 ON tbl_index_g2.match_pro_id_g2=tbl_products.pro_id 
INNER JOIN tbl_products_entry ON tbl_products_entry.pro_id=tbl_products.pro_id 
INNER JOIN tbl_application ON tbl_index_g2.pro_id=tbl_application.application_id 
where tbl_products.deleteflag='active' and tbl_products.status='active'  
$search_price_list
$search_cat_id  
GROUP by tbl_products.pro_id  order by tbl_products.pro_title asc ";
$result_products	=  DB::select(($sql_products));					

/*$qty_slab			= $result_products[0]->qty_slab;
$qty_slab_pro_id	= $result_products[0]->pro_id;

if($qty_slab=='Yes')
{
$qty_slab_table		= quantity_slab_max_discount_table($qty_slab_pro_id);
}
else
{
$qty_slab_table		= "0";
}
*/


$num_rows			= count($result_products); 	  
if($num_rows>0)	  
{
   $msg = array("msg"=>"true","products_data"=>$result_products);
}
else
{
  $msg = array("msg"=>"false","products_data"=>"No records found");
}

  return response()->json([            
         'products_data' => $result_products, 
        ]);


}

public function stage_colors()
    {
     $rs_stage_colors 	= DB::table('tbl_general_configuraction')->where('deleteflag', '=', 'active')->orderby('gen_config_id','asc')->select('enquiry_stage','enquiry_stage_curve', 'lead_stage', 'lead_stage_curve', 'offer_stage', 'offer_stage_curve','opportunity_stage', 'opportunity_stage_curve', 'confirmed_stage', 'confirmed_stage_curve','closed_stage', 'closed_stage_curve','kill_stage', 'kill_stage_curve','dead_stage', 'dead_stage_curve',)->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'stage_colors' => $rs_stage_colors, 
        ]);
    }


    public function price_type_list()
    {
        $currency_pricelist 	= DB::table('tbl_currency_pricelist')->orderby('price_list_name','asc')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'currency_pricelist' => $currency_pricelist, 
        ]);
    }

}
//class closed