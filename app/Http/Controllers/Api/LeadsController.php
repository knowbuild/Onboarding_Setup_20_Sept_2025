<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   #############################################Leads#############################################
//Leads listing
   public function leadslisting(Request $request)
    {
       
//$AdminLoginID_SET = Auth::user()->id;
$acc_manager        = $request->acc_manager;
$estimated_value 	= $request->estimated_value;
$lead_stage 		= $request->lead_stage;
$sort_by 			= $request->sort_by;
$app_cat_id 		= $request->product_category;
$ref_source 		= $request->ref_source;
$cust_segment 		= $request->cust_segment;
$AdminLoginID_SET	= $request->AdminLoginID_SET;
$admin_role_id		= $request->admin_role_id;
$hot_enquiry_search	= $request->hot_enquiry;	
$application_search = $request->admin_role_id;
$lead_id 			= $request->lead_id;
$enq_id 			= $request->enq_id;
$enq_status			= $request->dead_duck;
$min_value			= $request->min_value;
$max_value			= $request->max_value;
$offer_created		= $request->offer_created;
$last_updated_on	= $request->last_updated_on;
$action				= $request->action;
$search_by			= $request->search_by;
$search_from 		= $request->date_from;
$datepicker_to 		= $request->date_to;

	if($search_from!='' && $datepicker_to!='')
	{
		
$date_range_search=" AND (date( tbl_web_enq_edit.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}

/*if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword=" AND tbl_lead.lead_fname like '%".$search_by."%' OR tbl_lead.lead_email like '%".$search_by."%' OR tbl_lead.lead_phone like '%".$search_by."%' OR tbl_comp.comp_name like '%".$search_by."%'  ";
	}*/
	
	
if ($search_by != '' && $search_by != '0') {
    $search_by_keyword = " AND (
        tbl_lead.lead_fname LIKE '%" . $search_by . "%' 
        OR tbl_lead.lead_email LIKE '%" . $search_by . "%' 
        OR tbl_lead.lead_phone LIKE '%" . $search_by . "%'
		OR tbl_comp.comp_name LIKE '%" . $search_by . "%'
    )";
}	
	

else
{
$search_by_keyword="";
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


if($enq_status=='' && $enq_status!='-1')
	{
		
		if($action=='lead_details')
		{
		
		$ord_enq_status='  ';
		}
		else
		{
		$ord_enq_status='  and tbl_web_enq_edit.order_id="0"';
		}
	}

//$data_action		= $request->action;
//$pcode				= $request->pcode;
//$order 				= $request->order;
$order 				= $request->order;
if($order=='')
{
	$order="desc";
}


if ($sort_by == 'date_asc')
{
    $order_by = "tbl_lead.time_lead_added";
    $order = "asc";
}
if ($sort_by == 'date_desc')
{
    $order_by = "tbl_lead.time_lead_added";
    $order = "desc";
}

if ($sort_by == 'amt_desc')
{
    $order_by = "tbl_lead.estimated_value";
    $order = "asc";
}

if ($sort_by == 'amt_asc')
{
    $order_by = "tbl_lead.estimated_value";
    $order = "desc";
}

if ($min_value != '' && $max_value != '')
{
 
    $estimated_value_search = " and  tbl_lead.estimated_value BETWEEN $min_value AND $max_value ";
}
else
{
	   $estimated_value_search = " ";
}

if ($app_cat_id != '' && $app_cat_id != '0' && $app_cat_id != ';')
{
    //$orders_status='Pending';
    $application_search = "and tbl_lead.app_cat_id='$app_cat_id'";
}
else
{
	   $application_search = " ";
}
if ($lead_stage != '' && $lead_stage != '0')
{ //orders_id
    //$orders_status='Pending';
    if ($lead_stage == '1')
    { //orders_id
        //$orders_status='Pending';
        $lead_stage_search = "and tbl_order.orders_status='Order Received'";
    }

    if ($lead_stage == '2')
    { //orders_id
        //$orders_status='Pending';
        $lead_stage_search = "and tbl_order.orders_status='Pending'";
    }

    if ($lead_stage == '3')
    { //orders_id
        //$orders_status='Pending';
        $lead_stage_search = "and tbl_order.orders_id IS NULL";
    }

}
else
{
    $lead_stage_search = "";
}

if ($ref_source != '' && $ref_source != '0')
{
    //$orders_status='Pending';
    $ref_source_search = "and tbl_lead.ref_source='$ref_source'";
}
else
{
	   $ref_source_search = " ";
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


if ($cust_segment != '' && $cust_segment != '0')
{
    //$orders_status='Pending';
    $cust_segment_search = "and tbl_lead.cust_segment='$cust_segment'";
}
else
{
	   $cust_segment_search = " ";
}

if ($acc_manager != '' && $acc_manager != '0')
{
    //$orders_status='Pending';
    $acc_manager_search = "and tbl_lead.acc_manager IN ($acc_manager)";
}
else
{
	   $acc_manager_search = "";
}


if ($lead_id != '' && $lead_id != '0' )
{
    //$orders_status='Pending';
    $lead_id_search = "and tbl_lead.id='$lead_id'";
}
else
{
	   $lead_id_search = " ";
}

if ($enq_id != '' && $enq_id != '0' )
{
    //$orders_status='Pending';
    $enq_id_search = "and tbl_web_enq_edit.ID='$enq_id'";
}
else
{
	   $enq_id_search = " ";
}



if($enq_status!='' && $enq_status!='-1' && $enq_status!='0')
{
	
	$enq_status_search=" AND tbl_web_enq_edit.dead_duck='$enq_status'";
	
}
else
{
	$enq_status_search="";
}



//$comp_name_search = "and tbl_lead.comp_name!='' and tbl_lead.comp_name!='0' ";
//paging
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
$search_from= $request->search_from; 
$datepicker_to= $request->datepicker_to; 
		
	if($search_from!='' && $datepicker_to!='')
	{
		
$date_range_search="AND (date( tbl_web_enq_edit.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}


$order_by = $request->order_by; 
if ($order_by != '')
{
    $searchRecord = "$application_search $search_by_keyword $date_range_search $estimated_value_search  $last_updated_on_search $hot_enquiry_search_search $ref_source_search $enq_status_search $ord_enq_status $cust_segment_search $offer_created_search $acc_manager_search $lead_id_search $enq_id_search";
}
else
{
	$order_by="tbl_lead.id";
	$order="desc";
    $searchRecord = "$application_search $search_by_keyword $date_range_search $estimated_value_search $last_updated_on_search $hot_enquiry_search_search $ref_source_search $enq_status_search $ord_enq_status  $offer_created_search $cust_segment_search $acc_manager_search $lead_id_search $enq_id_search";
}

     $sql_lead="SELECT  
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name)
        ELSE tbl_comp.comp_name
END AS company_full_name,
    tbl_lead.comp_person_id,
	tbl_lead.competition as potential_competition,
    tbl_lead.lead_desc as customer_comments,
    tbl_lead.acc_manager, 
	tbl_web_enq_edit.Enq_Date,
    tbl_lead.id as lead_id,
	tbl_lead.enq_id,
	tbl_web_enq_edit.ID, 	
    tbl_lead.offer_type, 	
    tbl_lead.lead_fname,
    tbl_lead.lead_lname,
    tbl_lead.lead_email,
    tbl_lead.lead_phone,
    tbl_lead.cust_segment,
    tbl_lead.ref_source, 
    tbl_lead.comp_name, 
    tbl_lead.app_cat_id,
    tbl_lead.estimated_value,
    tbl_lead.time_lead_added,
    DATEDIFF(CURDATE(), tbl_lead.time_lead_added) AS days_since_lead,
    tbl_application.application_name,
	tbl_application_service.application_service_name,
    tbl_cust_segment.cust_segment_name,
    tbl_enq_source.enq_source_name,
	CONCAT(tbl_admin.admin_fname, ' ', tbl_admin.admin_lname) as admin_name,
	tbl_web_enq_edit.enq_id as enq_id, 
	tbl_web_enq_edit.remind_me, 
	tbl_web_enq_edit.order_id,
	tbl_web_enq_edit.price_type,
	tbl_web_enq_edit.enq_type, 
	tbl_web_enq_edit.hot_enquiry, 
	tbl_web_enq_edit.enq_remark_edited, 	
    tbl_web_enq_edit.dead_duck,
	tbl_web_enq_edit.Cus_msg		
FROM 
    tbl_lead  
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.lead_id = tbl_lead.id 
	
LEFT JOIN 
    tbl_comp ON tbl_comp.id = tbl_lead.comp_name 
LEFT JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id 
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = tbl_lead.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = tbl_lead.app_cat_id  
LEFT JOIN 
    tbl_application_service ON tbl_application_service.application_service_id = tbl_lead.app_cat_id AND tbl_lead.app_cat_id > 0	
LEFT JOIN 
    tbl_enq_source ON tbl_enq_source.enq_source_description = tbl_lead.ref_source
LEFT JOIN 
    tbl_admin ON tbl_admin.admin_id = tbl_lead.acc_manager 
WHERE  
    tbl_lead.deleteflag = 'active' 
	$searchRecord 
	

 
ORDER BY $order_by  $order  
LIMIT $from, $max_results";// exit;

//echo "<br><br>".$sql_lead = "Select * FROM tbl_lead where  tbl_lead.deleteflag='active' $searchRecord GROUP BY tbl_lead.id ORDER BY $order_by  $order  LIMIT $from, $max_results"; //exit;
$result_lead =  DB::select(($sql_lead));					
 $sql_lead_paging="SELECT  
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name)
        ELSE tbl_comp.comp_name
END AS company_full_name,
    tbl_lead.comp_person_id,
	tbl_lead.competition as potential_competition,
    tbl_lead.lead_desc as customer_comments,
    tbl_lead.acc_manager, 
	tbl_web_enq_edit.Enq_Date,
    tbl_lead.id as lead_id,
	tbl_lead.enq_id,
	tbl_web_enq_edit.ID, 	
    tbl_lead.offer_type, 	
    tbl_lead.lead_fname,
    tbl_lead.lead_lname,
    tbl_lead.lead_email,
    tbl_lead.lead_phone,
    tbl_lead.cust_segment,
    tbl_lead.ref_source, 
    tbl_lead.comp_name, 
    tbl_lead.app_cat_id,
    tbl_lead.estimated_value,
    tbl_lead.time_lead_added,
    DATEDIFF(CURDATE(), tbl_lead.time_lead_added) AS days_since_lead,
	tbl_web_enq_edit.enq_id as enq_id, 
	tbl_web_enq_edit.remind_me, 
	tbl_web_enq_edit.order_id,
	tbl_web_enq_edit.price_type,
	tbl_web_enq_edit.enq_type, 
	tbl_web_enq_edit.hot_enquiry, 
	tbl_web_enq_edit.enq_remark_edited, 	
    tbl_web_enq_edit.dead_duck,
	tbl_web_enq_edit.Cus_msg		
FROM 
    tbl_lead  
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.lead_id = tbl_lead.id 
	
LEFT JOIN 
    tbl_comp ON tbl_comp.id = tbl_lead.comp_name 
LEFT JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id 

WHERE  
    tbl_lead.deleteflag = 'active' 
	$searchRecord 
	 and tbl_web_enq_edit.order_id='0'
 
 
ORDER BY $order_by  $order  ";

$result_lead_paging =  DB::select(($sql_lead_paging));					
$leads_num_rows	= count($result_lead_paging); 	  
$max_estimated_value_lead=max_estimated_value_lead();
	return response()->json([ 
			'lead_data' => $result_lead,
			'num_rows_count' => $leads_num_rows,
			'max_estimated_value_lead'=>$max_estimated_value_lead
		]);

}


public function lead_listing_export_to_excel(Request $request)
    {
       
//$AdminLoginID_SET = Auth::user()->id;
$acc_manager        = $request->acc_manager;
$estimated_value 	= $request->estimated_value;
$lead_stage 		= $request->lead_stage;
$sort_by 			= $request->sort_by;
$app_cat_id 		= $request->product_category;
$ref_source 		= $request->ref_source;
$cust_segment 		= $request->cust_segment;
$AdminLoginID_SET	= $request->AdminLoginID_SET;
$admin_role_id		= $request->admin_role_id;
$hot_enquiry_search	= $request->hot_enquiry;	
$application_search = $request->admin_role_id;
$lead_id 			= $request->lead_id;
$enq_status			= $request->dead_duck;
$min_value			= $request->min_value;
$max_value			= $request->max_value;
$offer_created		= $request->offer_created;
$last_updated_on	= $request->last_updated_on;
$action				= $request->action;
$search_by			= $request->search_by;
$search_from 		= $request->date_from;
$datepicker_to 		= $request->date_to;

	if($search_from!='' && $datepicker_to!='')
	{
		
$date_range_search=" AND (date( tbl_web_enq_edit.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}




	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword=" AND tbl_lead.lead_fname like '%".$search_by."%' OR tbl_lead.lead_email like '%".$search_by."%' OR tbl_lead.lead_phone like '%".$search_by."%' OR tbl_comp.comp_name like '%".$search_by."%'  ";
	}

else
{
$search_by_keyword="";
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
	$last_updated_on_search=" and tbl_web_enq_edit.mel_updated_on < now() - INTERVAL 115 day";
}

	else
	{
		$last_updated_on_search="";
	}

echo $last_updated_on_search;
if($enq_status=='' && $enq_status!='-1')
	{
		
		if($action=='lead_details')
		{
		
		$enq_status='';
		$abc= "AND tbl_web_enq_edit.order_id='0'";		
		}
		else
		{
		$enq_status='0';
		
		$abc= " ";
		}
	}

//$data_action		= $request->action;
//$pcode				= $request->pcode;
//$order 				= $request->order;
$order 				= $request->order;
if($order=='')
{
	$order="desc";
}


if ($sort_by == 'date_asc')
{
    $order_by = "tbl_lead.time_lead_added";
    $order = "asc";
}
if ($sort_by == 'date_desc')
{
    $order_by = "tbl_lead.time_lead_added";
    $order = "desc";
}

if ($sort_by == 'amt_desc')
{
    $order_by = "tbl_lead.estimated_value";
    $order = "asc";
}

if ($sort_by == 'amt_asc')
{
    $order_by = "tbl_lead.estimated_value";
    $order = "desc";
}

if ($min_value != '' && $max_value != '')
{
 
    $estimated_value_search = " and  tbl_lead.estimated_value BETWEEN $min_value AND $max_value ";
}
else
{
	   $estimated_value_search = " ";
}

if ($app_cat_id != '' && $app_cat_id != '0' && $app_cat_id != ';')
{
    //$orders_status='Pending';
    $application_search = "and tbl_lead.app_cat_id='$app_cat_id'";
}
else
{
	   $application_search = " ";
}
if ($lead_stage != '' && $lead_stage != '0')
{ //orders_id
    //$orders_status='Pending';
    if ($lead_stage == '1')
    { //orders_id
        //$orders_status='Pending';
        $lead_stage_search = "and tbl_order.orders_status='Order Received'";
    }

    if ($lead_stage == '2')
    { //orders_id
        //$orders_status='Pending';
        $lead_stage_search = "and tbl_order.orders_status='Pending'";
    }

    if ($lead_stage == '3')
    { //orders_id
        //$orders_status='Pending';
        $lead_stage_search = "and tbl_order.orders_id IS NULL";
    }

}
else
{
    $lead_stage_search = "";
}

if ($ref_source != '' && $ref_source != '0')
{
    //$orders_status='Pending';
    $ref_source_search = "and tbl_lead.ref_source='$ref_source'";
}
else
{
	   $ref_source_search = " ";
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


if ($cust_segment != '' && $cust_segment != '0')
{
    //$orders_status='Pending';
    $cust_segment_search = "and tbl_lead.cust_segment='$cust_segment'";
}
else
{
	   $cust_segment_search = " ";
}

if ($acc_manager != '' && $acc_manager != '0')
{
    //$orders_status='Pending';
    $acc_manager_search = "and tbl_lead.acc_manager='$acc_manager'";
}
else
{
	   $acc_manager_search = "";
}


if ($lead_id != '' && $lead_id != '0' )
{
    //$orders_status='Pending';
    $lead_id_search = "and tbl_lead.id='$lead_id'";
}
else
{
	   $lead_id_search = " ";
}
if($enq_status!='' && $enq_status!='-1')
{
	
	$enq_status_search=" AND tbl_web_enq_edit.dead_duck='$enq_status'";
	
}
else
{
	$enq_status_search="";
}



//$comp_name_search = "and tbl_lead.comp_name!='' and tbl_lead.comp_name!='0' ";
//paging
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
$search_from= $request->search_from; 
$datepicker_to= $request->datepicker_to; 
		
	if($search_from!='' && $datepicker_to!='')
	{
		
$date_range_search="AND (date( tbl_web_enq_edit.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}


$order_by = $request->order_by; 
if ($order_by != '')
{
    $searchRecord = "$application_search $search_by_keyword $date_range_search $estimated_value_search  $last_updated_on_search $hot_enquiry_search_search $ref_source_search $enq_status_search $cust_segment_search $offer_created_search $acc_manager_search $lead_id_search $abc";
}
else
{
	$order_by="id";
	$order="desc";
    $searchRecord = "$application_search $search_by_keyword $date_range_search $estimated_value_search $last_updated_on_search $hot_enquiry_search_search $ref_source_search $enq_status_search  $offer_created_search $cust_segment_search $acc_manager_search $lead_id_search $abc";
}

/* $sql_lead="SELECT  
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, '', tbl_company_extn.company_extn_name)
        ELSE tbl_comp.comp_name
END AS company_full_name,
    tbl_lead.comp_person_id,
	tbl_lead.competition as potential_competition,
    tbl_lead.lead_desc as customer_comments,
    tbl_lead.acc_manager, 
	tbl_web_enq_edit.Enq_Date,
    tbl_lead.id as lead_id,
	tbl_lead.enq_id,
	tbl_web_enq_edit.ID, 	
    tbl_lead.offer_type, 	
    tbl_lead.lead_fname,
    tbl_lead.lead_lname,
    tbl_lead.lead_email,
    tbl_lead.lead_phone,
    tbl_lead.cust_segment,
    tbl_lead.ref_source, 
    tbl_lead.comp_name, 
    tbl_lead.app_cat_id,
    tbl_lead.estimated_value,
    tbl_lead.time_lead_added,
    DATEDIFF(CURDATE(), tbl_lead.time_lead_added) AS days_since_lead,
    tbl_application.application_name,
	tbl_application_service.application_service_name,
    tbl_cust_segment.cust_segment_name,
    tbl_enq_source.enq_source_name,
	CONCAT(tbl_admin.admin_fname, ' ', tbl_admin.admin_lname) as admin_name,
	tbl_web_enq_edit.enq_id as enq_id, 
	tbl_web_enq_edit.remind_me, 
	tbl_web_enq_edit.order_id,
	tbl_web_enq_edit.enq_type, 
	tbl_web_enq_edit.hot_enquiry, 
	tbl_web_enq_edit.enq_remark_edited, 	
    tbl_web_enq_edit.dead_duck,
	tbl_web_enq_edit.Cus_msg		
FROM 
    tbl_lead  
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.lead_id = tbl_lead.id

LEFT JOIN 
    tbl_comp ON tbl_comp.id = tbl_lead.comp_name 
LEFT JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id 
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = tbl_lead.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = tbl_lead.app_cat_id  
LEFT OUTER JOIN 
    tbl_application_service ON tbl_application_service.application_service_id = tbl_lead.app_cat_id  and  tbl_lead.app_cat_id >0	
LEFT JOIN 
    tbl_enq_source ON tbl_enq_source.enq_source_description = tbl_lead.ref_source
LEFT JOIN 
    tbl_admin ON tbl_admin.admin_id=tbl_lead.acc_manager 
WHERE  
    tbl_lead.deleteflag = 'active' 
	$searchRecord 
 
    GROUP BY 
    tbl_lead.id,
    tbl_lead.comp_person_id,
    tbl_lead.lead_desc,
    tbl_lead.acc_manager, 
    tbl_lead.enq_id,
    tbl_lead.offer_type, 
    tbl_lead.lead_fname,
    tbl_lead.lead_lname,
    tbl_lead.lead_email,
    tbl_lead.lead_phone,
    tbl_lead.cust_segment,
    tbl_lead.ref_source, 
    tbl_lead.comp_name, 
    tbl_lead.app_cat_id,
    tbl_lead.estimated_value,
    tbl_lead.time_lead_added,
    tbl_application.application_name,
    tbl_cust_segment.cust_segment_name,
    tbl_enq_source.enq_source_name,
    tbl_admin.admin_fname,
    tbl_admin.admin_lname,
    tbl_company_extn.company_extn_id,
    tbl_comp.comp_name,
    tbl_company_extn.company_extn_name 
ORDER BY $order_by  $order  
LIMIT $from, $max_results";

//echo "<br><br>".$sql_lead = "Select * FROM tbl_lead where  tbl_lead.deleteflag='active' $searchRecord GROUP BY tbl_lead.id ORDER BY $order_by  $order  LIMIT $from, $max_results"; //exit;
$result_lead =  DB::select(($sql_lead));		*/			
$sql_lead_paging="SELECT  
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name)
        ELSE tbl_comp.comp_name
END AS company_full_name,

	tbl_lead.competition,
    tbl_lead.id as lead_id,
	tbl_lead.enq_id,
    tbl_lead.offer_type, 	
    tbl_lead.lead_fname,
    tbl_lead.lead_lname,
    tbl_lead.lead_email,
    tbl_lead.lead_phone,
    tbl_lead.estimated_value,
    tbl_lead.time_lead_added,
    DATEDIFF(CURDATE(), tbl_lead.time_lead_added) AS days_since_lead,
    tbl_application.application_name,
	tbl_application_service.application_service_name,
    tbl_cust_segment.cust_segment_name,
    tbl_enq_source.enq_source_name,
	CONCAT(tbl_admin.admin_fname, ' ', tbl_admin.admin_lname) as acc_manager_name,
	tbl_web_enq_edit.ID , 
	tbl_web_enq_edit.enq_id as enq_id, 
	tbl_web_enq_edit.order_id, 
	tbl_web_enq_edit.hot_enquiry, 
	tbl_web_enq_edit.enq_remark_edited, 	
    tbl_web_enq_edit.dead_duck	
FROM 
    tbl_lead  
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.lead_id = tbl_lead.id

LEFT JOIN 
    tbl_comp ON tbl_comp.id = tbl_lead.comp_name 
LEFT JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id 
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = tbl_lead.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = tbl_lead.app_cat_id  
LEFT OUTER JOIN 
    tbl_application_service ON tbl_application_service.application_service_id = tbl_lead.app_cat_id   and 	 tbl_lead.app_cat_id >0
LEFT JOIN 
    tbl_enq_source ON tbl_enq_source.enq_source_description = tbl_lead.ref_source
LEFT JOIN 
    tbl_admin ON tbl_admin.admin_id=tbl_lead.acc_manager 
WHERE  
    tbl_lead.deleteflag = 'active' 
	$searchRecord 
 
    GROUP BY 
    tbl_lead.id,
    tbl_lead.comp_person_id,
    tbl_lead.lead_desc,
    tbl_lead.acc_manager, 
    tbl_lead.enq_id,
    tbl_lead.offer_type, 
    tbl_lead.lead_fname,
    tbl_lead.lead_lname,
    tbl_lead.lead_email,
    tbl_lead.lead_phone,
    tbl_lead.cust_segment,
    tbl_lead.ref_source, 
    tbl_lead.comp_name, 
    tbl_lead.app_cat_id,
    tbl_lead.estimated_value,
    tbl_lead.time_lead_added,
    tbl_application.application_name,
    tbl_cust_segment.cust_segment_name,
    tbl_enq_source.enq_source_name,
    tbl_admin.admin_fname,
    tbl_admin.admin_lname,
    tbl_company_extn.company_extn_id,
    tbl_comp.comp_name,
    tbl_company_extn.company_extn_name 
ORDER BY $order_by  $order  ";

//echo "<br><br>".$sql_lead = "Select * FROM tbl_lead where  tbl_lead.deleteflag='active' $searchRecord GROUP BY tbl_lead.id ORDER BY $order_by  $order  LIMIT $from, $max_results"; //exit;
$result_lead_paging =  DB::select(($sql_lead_paging));					
$leads_num_rows	= count($result_lead_paging); 	  
//$max_estimated_value_lead=max_estimated_value_lead();
	return response()->json([ 
			'export_lead_data' => $result_lead_paging,
			'num_rows_count' => $leads_num_rows
		]);
		
}


//add lead
public function add_lead(Request $request)
    {
 $lead_array			= $request->all();
   
   
 // print_r($lead_array); 
 $date 	  				= date('Y-m-d');
 $Cus_email				= $lead_array["enquirydata"]["Cus_email"];
 $Cus_mob				= $lead_array["enquirydata"]["Cus_mob"];
 $Cus_msg				= $lead_array["enquirydata"]["Cus_msg"];
 $Cus_name				= $lead_array["enquirydata"]["Cus_name"];
 $Enq_Date				= $lead_array["enquirydata"]["Enq_Date"];
 $enq_id				= $lead_array["enquirydata"]["ID"];
 $acc_manager			= $lead_array["enquirydata"]["acc_manager"];
 $offer_type			= $lead_array["enquirydata"]["offer_type"];

/* if($lead_array["enquirydata"]["offer_type"]=='' && $lead_array["enquirydata"]["offer_type"]=='0' && $lead_array["enquirydata"]["offer_type"]=='product' )
 {
	 $offer_type		= "product";
 }
 
 else
 {
	  $offer_type		= "service";
 }
*/ 
 $ref_source			=$lead_array["enquirydata"]["ref_source"];
 $cust_segment			=$lead_array["cust_segment"];

 $comp_person_id		=$lead_array["contact_details"]["comp_person_id"];
 $salutation			=$lead_array["contact_details"]["salutation"];
 $lead_fname			=$lead_array["contact_details"]["fname"];
 $lead_lname			=$lead_array["contact_details"]["lname"];
 $designation_id		=$lead_array["contact_details"]["designation_id"];
 $designation_name		=$lead_array["contact_details"]["designation_name"];
 $lead_email			=$lead_array["contact_details"]["email"];
 $lead_phone			=$lead_array["contact_details"]["telephone"];
 $mobile_no				=$lead_array["contact_details"]["mobile_no"];
 $lead_contact_address1	=$lead_array["contact_details"]["address"];
 $city					=$lead_array["contact_details"]["city"];
 $lead_contact_country	=$lead_array["contact_details"]["country"];
 $state					=$lead_array["contact_details"]["state"];
 $zip					=$lead_array["contact_details"]["zip"];
 $department_id			=$lead_array["contact_details"]["department_id"];
 $status				=$lead_array["contact_details"]["status"];
 $deleteflag			=$lead_array["contact_details"]["deleteflag"];
 $comp_name				=$lead_array["company_id"];
// $lead_array["contact_details"]["country_name"];
// $lead_array["contact_details"]["comp_person_id"];
$ctr								= count($lead_array["productData"]["productDetails"]);
$potential_competitions_ctr			= count($lead_array["productData"]["potential_competitions"]);


$notes								=$lead_array["productData"]["notes"];
//$lead_array["productData"]["potential_competitions"]["0"];

$app_cat_id=$lead_array["productData"]["productDetails"][0]["productCategory"];
//$ctr=3;

for($p=0; $p<$potential_competitions_ctr; $p++)
{
 $lead_array["productData"]["potential_competitions"][$p];
}
  "competition_rumit".$competition = implode(",",$lead_array["productData"]["potential_competitions"]);
//exit;

$estimated_value								= "0";
$priority										= "Urgent";
$no_of_emp										= "0";

   	$fileArrayist["lead_fname"]          		= $lead_fname; 
    $fileArrayist["enq_id"]          			= $enq_id;
   	if($comp_person_id=='')
   {
   		$fileArrayist["comp_person_id"]         = "0"; 
   }
   else
   {
   	$fileArrayist["comp_person_id"]         	= $comp_person_id; 
   }
   //	$fileArrayist["comp_person_id"]         = $_REQUEST["comp_person_id"]; 
   		$fileArrayist["offer_type"]          	= $offer_type; 
		$fileArrayist["lead_desc"]          	= $notes; 
        $fileArrayist["ref_source"]          	= $ref_source;
        $fileArrayist["date_opened"]          	= $date; 
        $fileArrayist["cust_segment"]           = $cust_segment;
        $fileArrayist["desc_details"]           = $Cus_msg; 
   		$fileArrayist["competition"]            = $competition; 
        $fileArrayist["dec_time_frame"]         = "15";//$dec_time_frame;
   		$fileArrayist["estimated_value"]        = $estimated_value;
        $fileArrayist["acc_manager"]          	= $acc_manager;
        $fileArrayist["status"]          		= $status;
        $fileArrayist["priority"]          		= $priority;
        $fileArrayist["lead_fname"]          	= $lead_fname; 
        $fileArrayist["lead_lname"]          	= $lead_lname; 
        $fileArrayist["lead_contact_address1"]  = $lead_contact_address1;
        $fileArrayist["lead_contact_address2"]  = "";//$lead_contact_address2; 
        $fileArrayist["salutation"]          	= $salutation;
        $fileArrayist["lead_title"]          	= "";//$lead_title;
        $fileArrayist["lead_contact_address3"]  = "";//$lead_contact_address3; 
        $fileArrayist["lead_contact_address4"]  = "";//$lead_contact_address4; 
        $fileArrayist["lead_email"]          	= $lead_email; 
        $fileArrayist["lead_phone"]          	= $lead_phone; 
        $fileArrayist["lead_contact_city"]      = $city; 
        $fileArrayist["lead_contact_zip_code"]  = $zip; 
		$fileArrayist["lead_contact_state"]    	= $state; 
		$fileArrayist["lead_contact_country"]  	= $lead_contact_country; 
		$fileArrayist["cust_segment"]  			= $cust_segment; 
		$fileArrayist["comp_website"]  			= "0";///$comp_website; 
		$fileArrayist["no_of_emp"]  			= $no_of_emp; 
		$lead_area_code							= "0";
		$lead_fax								= "0";
		$comp_revenue							= "0";		

   	if($lead_area_code!='')
   	{
       $fileArrayist["lead_area_code"]         = $lead_area_code; 
   	}
   	else
   	{
   	$fileArrayist["lead_area_code"]         = "0"; 
   	}
   	if($lead_fax!='')
   	{
       $fileArrayist["lead_fax"]          	= $lead_fax; 
   	}
   	else
   	{
   		$fileArrayist["lead_fax"]          = "0"; 
   	}
   if($comp_revenue!='')
   {
   	$fileArrayist["comp_revenue"]  			= $comp_revenue; 
   }
   else
   {
   	$fileArrayist["comp_revenue"]  			= "0"; 
   }
   	$fileArrayist["status"]					= "active";
   	$fileArrayist["comp_name"]  			= $comp_name; 
   	$fileArrayist["app_cat_id"]  			= $app_cat_id; 
	  $previous_lead_id_check					= lead_id_from_enq_edit_table($enq_id);//exit;

//insert data in lead table	
//exit;
if($previous_lead_id_check=='0')
{
    $inserted_lead_id 						= DB::table('tbl_lead')->insertGetId($fileArrayist);		
	$fileArrayEnq["lead_id"]  			 	= $inserted_lead_id; 
	$fileArrayEnq["enq_stage"]				= 2;

for($i=0; $i<$ctr; $i++)
{
$pro_id										= $lead_array["productData"]["productDetails"][$i]["product"];	
$pro_category								= $lead_array["productData"]["productDetails"][$i]["productCategory"];	
//echo "oooooooooooookewto".$offer_type;
/*if($offer_type=='' && $offer_type=='product' && $offer_type=='product_offer')
{
$gst_percentage								= ApplicationTax($pro_category);
}
else
{
$gst_percentage								= ApplicationTaxService($pro_category);
}*/
if (in_array($offer_type, ['', 'product', 'product_offer'])) {
    $gst_percentage = ApplicationTax($pro_category);
} else {
    $gst_percentage = ApplicationTaxService($pro_category);
}
// "TAX pecentage".$gst_percentage;  //exit;
$pro_id										= $lead_array["productData"]["productDetails"][$i]["product"];	
$upc_code									= $lead_array["productData"]["productDetails"][$i]["upc_code"];	
$price_list									= $lead_array["productData"]["productDetails"][$i]["price_list"];	
$proidentry									= $lead_array["productData"]["productDetails"][$i]["pro_id_entry"];	
$pro_model									= $lead_array["productData"]["productDetails"][$i]["pro_model"];	
$pro_price									= $lead_array["productData"]["productDetails"][$i]["pro_price"];	
$pro_name									= $lead_array["productData"]["productDetails"][$i]["pro_title"];	
if($lead_array["productData"]["productDetails"][$i]["hsn_code"]=='')
{
$hsn_code									= "0";	
}
else
{
$hsn_code									= 	$lead_array["productData"]["productDetails"][$i]["hsn_code"];
}
$pro_quantity								= $lead_array["productData"]["productDetails"][$i]["quantity"];	
$freight_amount								= "0.00";	
$pro_sort									= "0";

if($lead_array["productData"]["productDetails"][$i]["servicePeriod"]=='' && $lead_array["productData"]["productDetails"][$i]["servicePeriod"]=='0')
{
$service_period								= "1";	
}
else
{
	$service_period							= $lead_array["productData"]["productDetails"][$i]["servicePeriod"];	
}


if($lead_array["productData"]["productDetails"][$i]["servicePeriodId"]=='' && $lead_array["productData"]["productDetails"][$i]["servicePeriodId"]=='0')
{
$service_period_id								= "0";	
}
else
{
	$service_period_id							= $lead_array["productData"]["productDetails"][$i]["servicePeriodId"];	
}


$fileArrayist_items["lead_id"]  			= $inserted_lead_id; 
$fileArrayist_items["pro_id"]  				= $pro_id; 
$fileArrayist_items["upc_code"]  			= $upc_code; 
$fileArrayist_items["price_list"]  			= $price_list; 
$fileArrayist_items["pro_category"]  		= $pro_category; 
$fileArrayist_items["proidentry"]  			= $proidentry; 
$fileArrayist_items["customers_id"]  		= $comp_name; 
$fileArrayist_items["pro_model"]  			= $pro_model; 
$fileArrayist_items["pro_name"]  			= $pro_name; 
$fileArrayist_items["pro_price"]  			= $pro_price; 
$fileArrayist_items["pro_tax"]  			= $gst_percentage; 
$fileArrayist_items["gst_percentage"]  		= $gst_percentage; 
$fileArrayist_items["freight_amount"]  		= $freight_amount; 
$fileArrayist_items["hsn_code"]  			= $hsn_code; 
$fileArrayist_items["pro_quantity"]  		= $pro_quantity;
$fileArrayist_items["pro_sort"]  			= $pro_sort; 
$fileArrayist_items["service_period"]  		= $service_period; 
$fileArrayist_items["service_period_id"]	= $service_period_id;   
//print_r($fileArrayist_items);
//exit;
$lead_products_table						= DB::table('tbl_lead_product')->insert($fileArrayist_items);			
	
/* "productCategory". $lead_array["productData"]["productDetails"][$i]["productCategory"];
 "product". $lead_array["productData"]["productDetails"][$i]["product"];
"quantity". $lead_array["productData"]["productDetails"][$i]["quantity"];*/
}

$sql_tbl_lead_product						= "SELECT lead_id, sum(pro_price* pro_quantity) as estimated_value FROM tbl_lead_product where lead_id='$inserted_lead_id' "; //exit;
$result_tbl_lead_products					=  DB::select(($sql_tbl_lead_product));					
$num_rows									=  count($result_tbl_lead_products); 
//echo $result_tbl_lead_products[0]->estimated_value;
//echo "est:::".$result_tbl_lead_products["estimated_value"]; exit;
//edit estimated value i.e. total of products tlb_lead_products
$fileArrayLead_estimatedvalue["estimated_value"]  			 	= $result_tbl_lead_products[0]->estimated_value; //exit;

 DB::table('tbl_lead')
            ->where('id', $inserted_lead_id)
          ->update($fileArrayLead_estimatedvalue);


 DB::table('tbl_web_enq_edit')
            ->where('ID', $enq_id)
          ->update($fileArrayEnq);

           $msg = array("msg"=>"true","lead_id"=>$inserted_lead_id);
}
else
{
           $msg = array("msg"=>"false","lead_id"=>0);
}
      //$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 

        return response()->json([            
        'message' => $msg, 
        ]);  
  }


//edit lead
  public function edit_lead(Request $request)
    {
 $lead_array			= $request->all();
// print_r($lead_array); //exit;

 $lead_id				=$lead_array["lead_id"];
 $cust_segment			=$lead_array["cust_segment"];
 $comp_person_id		=$lead_array["contact_details"]["comp_person_id"];
 $salutation			=$lead_array["contact_details"]["salutation"];
 $lead_fname			=$lead_array["contact_details"]["fname"];
 $lead_lname			=$lead_array["contact_details"]["lname"];
 $designation_id		=$lead_array["contact_details"]["designation_id"];
 $designation_name		=$lead_array["contact_details"]["designation_name"];
 $lead_email			=$lead_array["contact_details"]["email"];
 $lead_phone			=$lead_array["contact_details"]["telephone"];
 $mobile_no				=$lead_array["contact_details"]["mobile_no"];
 $lead_contact_address1	=$lead_array["contact_details"]["address"];
 $city					=$lead_array["contact_details"]["city"];
 $lead_contact_country	=$lead_array["contact_details"]["country"];
 $state					=$lead_array["contact_details"]["state"];
 $zip					=$lead_array["contact_details"]["zip"];
 $department_id			=$lead_array["contact_details"]["department_id"];
 $status				=$lead_array["contact_details"]["status"];
 $deleteflag			=$lead_array["contact_details"]["deleteflag"];
 $comp_name				=$lead_array["company_id"];

 
//edit array

if($comp_person_id=='')
   {
   		$fileArrayist["comp_person_id"]         = "0"; 
   }
   else
   {
   	$fileArrayist["comp_person_id"]         	= $comp_person_id; 
   }
   //	$fileArrayist["comp_person_id"]         = $_REQUEST["comp_person_id"]; 
//   		$fileArrayist["offer_type"]          	= $offer_type; 
	//	$fileArrayist["lead_desc"]          	= $notes; 
//        $fileArrayist["ref_source"]          	= $ref_source;
//        $fileArrayist["date_opened"]          	= $date; 
        $fileArrayist["cust_segment"]           = $cust_segment;
//        $fileArrayist["desc_details"]           = $Cus_msg; 
//   		$fileArrayist["competition"]            = $competition; 
//        $fileArrayist["dec_time_frame"]         = "15";//$dec_time_frame;
//   		$fileArrayist["estimated_value"]        = $estimated_value;
//        $fileArrayist["acc_manager"]          	= $acc_manager;
//        $fileArrayist["status"]          		= $status;
//        $fileArrayist["priority"]          		= $priority;
        $fileArrayist["lead_fname"]          	= $lead_fname; 
        $fileArrayist["lead_lname"]          	= $lead_lname; 
        $fileArrayist["lead_contact_address1"]  = $lead_contact_address1;
        $fileArrayist["lead_contact_address2"]  = "";//$lead_contact_address2; 
        $fileArrayist["salutation"]          	= $salutation;
        $fileArrayist["lead_title"]          	= "";//$lead_title;
        $fileArrayist["lead_contact_address3"]  = "";//$lead_contact_address3; 
        $fileArrayist["lead_contact_address4"]  = "";//$lead_contact_address4; 
        $fileArrayist["lead_email"]          	= $lead_email; 
        $fileArrayist["lead_phone"]          	= $lead_phone; 
        $fileArrayist["lead_contact_city"]      = $city; 
        $fileArrayist["lead_contact_zip_code"]  = $zip; 
		$fileArrayist["lead_contact_state"]    	= $state; 
		$fileArrayist["lead_contact_country"]  	= $lead_contact_country; 
		$fileArrayist["cust_segment"]  			= $cust_segment; 
		$fileArrayist["comp_website"]  			= "0";///$comp_website; 
//		$fileArrayist["no_of_emp"]  			= $no_of_emp; 
		$lead_area_code							= "0";
		$lead_fax								= "0";
		$comp_revenue							= "0";		

   	if($lead_area_code!='')
   	{
       $fileArrayist["lead_area_code"]         = $lead_area_code; 
   	}
   	else
   	{
   	$fileArrayist["lead_area_code"]         = "0"; 
   	}
   	if($lead_fax!='')
   	{
       $fileArrayist["lead_fax"]          	= $lead_fax; 
   	}
   	else
   	{
   		$fileArrayist["lead_fax"]          = "0"; 
   	}
   if($comp_revenue!='')
   {
   	$fileArrayist["comp_revenue"]  			= $comp_revenue; 
   }
   else
   {
   	$fileArrayist["comp_revenue"]  			= "0"; 
   }
   	$fileArrayist["status"]					= "active";
   	$fileArrayist["comp_name"]  			= $comp_name; 
//   	$fileArrayist["app_cat_id"]  			= $app_cat_id;    
   
//$fileArrayLead_estimatedvalue["estimated_value"]  			 	= $result_tbl_lead_products[0]->estimated_value; //exit;

$edit_lead_details_result= DB::table('tbl_lead')
            ->where('id', $lead_id)
          ->update($fileArrayist);
		  
if($edit_lead_details_result)		  
{
 $msg = array("msg"=>"true","result"=>$edit_lead_details_result);		  
}
else
{
	$msg = array("msg"=>"false","result"=>$edit_lead_details_result);		  
}

		  
  return response()->json([            
        'message' => $msg, 
        ]);  
  }

// add company while convert to lead popup


public function delete_lead_requirements(Request $request)
    {
$lead_array				= $request->all();
$date 	  				= date('Y-m-d');
$lead_pros_id			= $lead_array["lead_pros_id"];		

$result_tbl_lead_products_delete	= DB::table('tbl_lead_product')->where('lead_pros_id', $lead_pros_id)->delete();

if($result_tbl_lead_products_delete)		  
{
 $msg = array("msg"=>"true","result"=>$result_tbl_lead_products_delete);		  
}
else
{
	$msg = array("msg"=>"false","result"=>$result_tbl_lead_products_delete);		  
}

 return response()->json([            
         'lead_products_listing' => $msg, 
        ]); 

}

public function edit_lead_requirements(Request $request)
    {
$lead_array									= $request->all();
$date 	  									= date('Y-m-d');
$lead_id									= $lead_array["lead_id"];
 if($lead_array["offer_type"]=='' && $lead_array["offer_type"]=='0' && $lead_array["offer_type"]=='product')
 {
	 $offer_type		= "product";
 }
 
 else
 {
	  $offer_type		= "service";
 }


//echo "<pre>";
//print_r($lead_array); 
$ctr										= count($lead_array["productData"]["productDetails"]);
for($i=0; $i<$ctr; $i++)
{
$pro_id										= $lead_array["productData"]["productDetails"][$i]["product"];	
$pro_category								= $lead_array["productData"]["productDetails"][$i]["productCategory"];	



if($offer_type=='' && $offer_type=='product')
{
$gst_percentage								= ApplicationTax($pro_category);
}
else
{
$gst_percentage								= ApplicationTaxService($pro_category);
}




//$gst_percentage								= ApplicationTax($pro_category);
$pro_id										= $lead_array["productData"]["productDetails"][$i]["product"];	
$lead_pros_id								= $lead_array["productData"]["productDetails"][$i]["lead_pros_id"];	

$upc_code									= $lead_array["productData"]["productDetails"][$i]["upc_code"];	
$price_list									= $lead_array["productData"]["productDetails"][$i]["price_list"];	
$proidentry									= $lead_array["productData"]["productDetails"][$i]["pro_id_entry"];	
$pro_model									= $lead_array["productData"]["productDetails"][$i]["pro_model"];	
$pro_price									= $lead_array["productData"]["productDetails"][$i]["pro_price"];	

$pro_name									= $lead_array["productData"]["productDetails"][$i]["pro_title"];	


//$hsn_code									= $lead_array["productData"]["productDetails"][$i]["hsn_code"];	
if($lead_array["productData"]["productDetails"][$i]["hsn_code"]=='')
{
$hsn_code									= "0";	
}
else
{
$hsn_code									= 	$lead_array["productData"]["productDetails"][$i]["hsn_code"];
}

$pro_quantity								= $lead_array["productData"]["productDetails"][$i]["quantity"];	
$freight_amount								= "0.00";	
$pro_sort									= "0";
$service_period								= $lead_array["productData"]["productDetails"][$i]["servicePeriod"];	
$service_period_id							= $lead_array["productData"]["productDetails"][$i]["servicePeriodId"];	


$fileArrayist_items["lead_id"]  			= $lead_id; 
$fileArrayist_items["pro_id"]  				= $pro_id; 
$fileArrayist_items["upc_code"]  			= $upc_code; 
$fileArrayist_items["price_list"]  			= $price_list; 
$fileArrayist_items["pro_category"]  		= $pro_category; 
$fileArrayist_items["proidentry"]  			= $proidentry; 
$fileArrayist_items["customers_id"]  		= "0"; 
$fileArrayist_items["pro_model"]  			= $pro_model; 
$fileArrayist_items["pro_name"]  			= $pro_name; 
$fileArrayist_items["pro_price"]  			= $pro_price; 
$fileArrayist_items["pro_tax"]  			= $gst_percentage; 
$fileArrayist_items["gst_percentage"]  		= $gst_percentage; 
$fileArrayist_items["freight_amount"]  		= $freight_amount; 
$fileArrayist_items["hsn_code"]  			= $hsn_code; 
$fileArrayist_items["pro_quantity"]  		= $pro_quantity;
$fileArrayist_items["pro_sort"]  			= $pro_sort; 
$fileArrayist_items["service_period"]  		= $service_period; 
$fileArrayist_items["service_period_id"]  	= $service_period_id; 


if($lead_pros_id!='' && $lead_pros_id!='0')
{
$lead_products_table						= DB::table('tbl_lead_product')->where('lead_pros_id', $lead_pros_id)->update($fileArrayist_items);

}
else
{
$lead_products_table						= DB::table('tbl_lead_product')->insert($fileArrayist_items);			

}
	
}


//exit;

$sql_tbl_lead_product						= "SELECT lead_id, sum(pro_price* pro_quantity) as estimated_value FROM tbl_lead_product where lead_id='$lead_id' ";
$result_tbl_lead_products					=  DB::select(($sql_tbl_lead_product));					
$num_rows									= count($result_tbl_lead_products); 
//echo $result_tbl_lead_products[0]->estimated_value;
//echo "est:::".$result_tbl_lead_products["estimated_value"]; exit;
//edit estimated value i.e. total of products tlb_lead_products
$fileArrayLead_estimatedvalue["estimated_value"]  			 	= $result_tbl_lead_products[0]->estimated_value; //exit;

 DB::table('tbl_lead')
            ->where('id', $lead_id)
          ->update($fileArrayLead_estimatedvalue);


/*$edit_lead_details_result= DB::table('tbl_lead')
            ->where('id', $lead_id)
          ->update($fileArrayist);*/
		  
if($result_tbl_lead_products)		  
{
 $msg = array("msg"=>"true","result"=>$result_tbl_lead_products);		  
}
else
{
	$msg = array("msg"=>"false","result"=>$result_tbl_lead_products);		  
}

 return response()->json([            
         'lead_products_listing' => $msg, 
        ]); 
	}


public function lead_products_listing(Request $request)
    {
	$lead_id=$request->lead_id;
	$offer_type=$request->offer_type;
	  if(isset($lead_id))
	  {             

	  if($offer_type=='' || $offer_type=='0' || $offer_type=='product')
	  {        
$lead_products_listing	=	lead_products_listing_json($lead_id);
	  }
	  else
	  {
$lead_products_listing	=	lead_services_listing_json($lead_id);
	  }

$total_estimated_value	=	lead_products_estimated_value_json($lead_id);
   $msg = array("msg"=>"true","lead_products_listing"=>$lead_products_listing,
   "total_estimated_value"=>$total_estimated_value);
       }
	else
	{
 $msg = array("msg"=>"false","lead_products_listing"=>"Lead id is missing: please pass lead_id as parameter","total_estimated_value"=>$total_estimated_value);
	}
        // Logic for your dashboard, e.g., returning dashboard data.
  return response()->json([            
         'lead_products_listing' => $msg, 
        ]);
    }	

function latest_po(Request $request)
{
	$today_date					= date("Y-m-d");
	$qtr_start_date_show		= "2019-04-01";
	$qtr_end_date_show			= $today_date;//"2024-03-31";
	$cust_segment				= $request->cust_segment;
	$app_cat_id					= $request->product_id;
	$offer_type					= $request->offer_type;
//	$qtr_start_date_show		= $request->qtr_start_date_show;
	//$qtr_end_date_show			= $request->qtr_end_date_show;
//	echo "cccccccccccccccc--".$app_cat_id;
if($cust_segment!='' && $cust_segment!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$cust_segment_search=" ";
	}
if($app_cat_id!='' && $app_cat_id!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
		$app_cat_id_search=" ";
	}
	
	
if($offer_type!='' && $offer_type!='0')
	{
	//$orders_status='Pending';
	$offer_type_search=" and o.offer_type='$offer_type'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
		$offer_type_search=" ";
	}	
//enq_source_abbrv	
"<br>".$sql = "SELECT
o.orders_id, 
o.order_by,
o.billing_company,  
o.total_order_cost_new, 
top.pro_id,
tble_invoice.I_date,  
tbl_delivery_challan.po_date,
tbl_delivery_challan.u_invoice_no,
tbl_delivery_challan.PO_No,
l.cust_segment,
tbl_cust_segment.cust_segment_name, 
o.total_order_cost_new, 
o.customers_id,
a.admin_fname,
a.admin_lname
from tbl_order o ,
tbl_do_products tdp ,
tbl_admin a,
tbl_cust_segment,
tbl_lead l,
tbl_order_product top, 
tbl_delivery_challan,
tble_invoice
where 
o.orders_id=tdp.OID and 
o.orders_id=top.order_id and 
o.order_by=a.admin_id 
and l.id=o.lead_id  
and o.orders_id = tbl_delivery_challan.O_Id   
AND tble_invoice.o_id=o.orders_id 
and o.orders_status IN('Confirmed','Order Closed')
and tbl_cust_segment.cust_segment_id=l.cust_segment  
$cust_segment_search
$app_cat_id_search
$offer_type_search
AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
group by l.cust_segment 
order by tbl_delivery_challan.invoice_gen_date desc limit 0,1";


$latest_po						=  DB::select(($sql));					
$num_rows						= count($latest_po); 	  

if($num_rows>0)	  
{
   $msg = true;
$latest_po_orders_id			=  $latest_po[0]->orders_id;					 
$product_details				= product_name_generated_with_quantity_json($latest_po_orders_id);				
$do_total						= Get_POTOTALVALUE($latest_po_orders_id);
}
else
{
 $msg = false;
$latest_po_orders_id			=  "0";					 
$product_details				= "0";					 	
$do_total						= "0";//Get_POTOTALVALUE($latest_po_orders_id);
}
  return response()->json([            
         'msg' => $msg, 
		  'num_rows' => $num_rows, 
           "latest_po_details"=>array("latest_po"=>$latest_po, 'product_items_details' => $product_details,'do_total' => $do_total) 
        ]);		

}


function highest_value_po(Request $request)
{
	
	$today_date					= date("Y-m-d");
	$qtr_start_date_show		= "2019-04-01";
	$qtr_end_date_show			= $today_date;//"2024-03-31";
	$cust_segment				= $request->cust_segment;
	$app_cat_id					= $request->product_id;
	$offer_type					= $request->offer_type;	
//	$qtr_start_date_show		= $request->qtr_start_date_show;
	//$qtr_end_date_show			= $request->qtr_end_date_show;
//	echo "cccccccccccccccc--".$app_cat_id;
if($cust_segment!='' && $cust_segment!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$cust_segment_search=" ";
	}
if($app_cat_id!='' && $app_cat_id!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
		$app_cat_id_search=" ";
	}


if($offer_type!='' && $offer_type!='0')
	{
	//$orders_status='Pending';
	$offer_type_search=" and o.offer_type='$offer_type'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
		$offer_type_search=" ";
	}	
//enq_source_abbrv	
"<br>Highest Value PO <br>".$sql = "SELECT
o.orders_id, 
o.order_by,
o.billing_company, 
o.total_order_cost_new, 
top.pro_id,
tdp.Description, 
tdp.Price,
tdp.Quantity,
tble_invoice.id, 
tble_invoice.I_date,  
tbl_delivery_challan.id,
tbl_delivery_challan.po_date,
tbl_delivery_challan.u_invoice_no,
tbl_delivery_challan.PO_No,
l.cust_segment, 
o.total_order_cost_new, 
o.customers_id,
tdo.D_Order_Date,
a.admin_fname,
a.admin_lname,
tbl_cust_segment.cust_segment_name,
SUM(tdp.Quantity * tdp.Price) as total_price
from tbl_order o ,
tbl_delivery_order tdo ,
tbl_do_products tdp ,
tbl_admin a,
tbl_cust_segment,  
tbl_lead l,
tbl_order_product top, 
tbl_delivery_challan,
tble_invoice

where 
o.orders_id=tdp.OID and 
o.orders_id=top.order_id and 
o.order_by=a.admin_id 
and l.id=o.lead_id  
and  tdo.O_Id = tdp.OID
and tdo.O_Id = tbl_delivery_challan.O_Id   
AND tble_invoice.o_id=tdo.O_Id 
AND a.admin_id=o.order_by 
and tbl_cust_segment.cust_segment_id=l.cust_segment  
and o.orders_status IN('Confirmed','Order Closed')
$cust_segment_search
$app_cat_id_search
$offer_type_search
AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
GROUP by o.orders_id  
order by total_price desc limit 0,1 ";	
$highest_value_po					=  DB::select(($sql));	

$num_rows			= count($highest_value_po); 	  
	
		

if($num_rows>0)	  
{
   $msg = true;
$highest_value_po_by_orders_id				=  $highest_value_po[0]->orders_id;					 
$product_details							= product_name_generated_with_quantity_json($highest_value_po_by_orders_id);				
$do_total									= Get_POTOTALVALUE($highest_value_po_by_orders_id);
}
else
{
$msg = false;
$highest_value_po_by_orders_id			=  "0";					 
$product_details						= "0";					 	
$do_total								= "0";
}
  return response()->json([            
         'msg' => $msg, 
		  'num_rows' => $num_rows, 
         'highest_value_po_details' => array("highest_value_po"=>$highest_value_po, 'product_items_details' => $product_details,'do_total' => $do_total) 
        ]);		
}


function highest_value_po_by_item(Request $request)
{
	$today_date					= date("Y-m-d");
	$qtr_start_date_show		= "2019-04-01";
	$qtr_end_date_show			= $today_date;//"2024-03-31";
	$cust_segment				= $request->cust_segment;
	$app_cat_id					= $request->product_id;	
	$offer_type					= $request->offer_type;	
if($cust_segment!='' && $cust_segment!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
	$cust_segment_search=" ";		
	}

if($app_cat_id!='' && $app_cat_id!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
	$app_cat_id_search=" ";		
	}
if($offer_type!='' && $offer_type!='0')
	{
	//$orders_status='Pending';
	$offer_type_search=" and o.offer_type='$offer_type'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
		$offer_type_search=" ";
	}	
//enq_source_abbrv	
"<br>Highest Value PO of ITEM<br>".$sql = "SELECT
o.orders_id, 
o.order_by, 
o.billing_company, 
o.total_order_cost_new, 
top.pro_id,
tdp.Description, 
tdp.Price,
tdp.Quantity,
tble_invoice.I_date,  
tbl_delivery_challan.po_date,
tbl_delivery_challan.PO_No,
l.cust_segment, 
o.customers_id,
tbl_admin.admin_fname,
tbl_admin.admin_lname,
tbl_cust_segment.cust_segment_name,
SUM(tdp.Quantity * tdp.Price) as total_price
from tbl_order o ,
tbl_delivery_order tdo ,
tbl_do_products tdp ,
tbl_lead l,
tbl_admin,
tbl_cust_segment,  
tbl_order_product top, 
tbl_delivery_challan,
tble_invoice
where 
o.orders_id=tdp.OID and 
o.orders_id=top.order_id 
and l.id=o.lead_id  
and  tdo.O_Id = tdp.OID
and tdo.O_Id = tbl_delivery_challan.O_Id   
AND tble_invoice.o_id=tdo.O_Id 
AND tbl_admin.admin_id=o.order_by 
and o.orders_status IN('Confirmed','Order Closed')
and tbl_cust_segment.cust_segment_id=l.cust_segment  
$cust_segment_search
$app_cat_id_search
$offer_type_search
AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
GROUP by o.orders_id  
order by tdp.Price desc limit 0,1";	
$highest_value_po_by_item					=  DB::select(($sql));					
$num_rows									= count($highest_value_po_by_item); 	  
	
		
if($num_rows>0)	  
{
   $msg = true;
$highest_value_po_by_item_orders_id			=  $highest_value_po_by_item[0]->orders_id;					 
$product_details							= product_name_generated_with_quantity_json($highest_value_po_by_item_orders_id);

$do_total									= Get_POTOTALVALUE($highest_value_po_by_item_orders_id);
}
else
{
 $msg = false;
$highest_value_po_by_item_orders_id			=  "0";					 
$product_details							=  "0";					 
$do_total									="0";
}
  return response()->json([            
         'msg' => $msg, 
		  'num_rows' => $num_rows, 
         "highest_value_po_by_item_details"=>array('highest_value_po_by_item' => $highest_value_po_by_item, 'product_items_details' => $product_details,'do_total' => $do_total )
        ]);		
		
		
//		'highest_value_po' => array("highest_value_po"=>$highest_value_po, 'product_items_details' => $product_details,) 
}


function customer_po(Request $request)
{
	$today_date					= date("Y-m-d");
	$qtr_start_date_show		= "2019-04-01";
	$qtr_end_date_show			= $today_date;//"2024-03-31";
	$customers_id				= $request->customers_id;
	$app_cat_id					= $request->product_id;	
	$offer_type					= $request->offer_type;		

if($customers_id!='' && $customers_id!='0')
	{
	//$orders_status='Pending';
	$customers_id_search=" and o.customers_id='$customers_id'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
	$customers_id_search=" ";		
	}

if($offer_type!='' && $offer_type!='0')
	{
	//$orders_status='Pending';
	$offer_type_search=" and o.offer_type='$offer_type'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
		$offer_type_search=" ";
	}	

if($app_cat_id!='' && $app_cat_id!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}

else
{
		$app_cat_id_search=" ";
}


   "<br>Customer PO <br>".$sql ="SELECT 
    o.orders_id, 
    o.order_by,
    o.billing_company, 
	o.total_order_cost_new, 
    top.pro_id,
    l.cust_segment, 
    tdp.Price,
    tdp.Quantity,
    tbl_delivery_challan.po_date,
    tbl_delivery_challan.u_invoice_no,
    o.customers_id,
    tbl_admin.admin_fname,
    tbl_admin.admin_lname,
    tbl_cust_segment.cust_segment_name,
    SUM(DISTINCT(tdp.Quantity * tdp.Price)) AS total_price
FROM 
    tbl_order o
LEFT JOIN tbl_do_products tdp ON o.orders_id = tdp.OID
LEFT JOIN tbl_order_product top ON o.orders_id = top.order_id
LEFT JOIN tbl_lead l ON l.id = o.lead_id
LEFT JOIN tbl_delivery_order tdo ON tdo.O_Id = tdp.OID
LEFT JOIN tbl_delivery_challan ON tdo.O_Id = tbl_delivery_challan.O_Id
LEFT JOIN tbl_admin ON o.order_by = tbl_admin.admin_fname  
LEFT JOIN tbl_cust_segment ON l.cust_segment = tbl_cust_segment.cust_segment_name 
WHERE 
    o.orders_status IN ('Confirmed', 'Order Closed')   
$customers_id_search
$app_cat_id_search
$offer_type_search
GROUP BY 
    l.cust_segment
LIMIT 1"; //exit;
$customer_po					=  DB::select(($sql));					


$num_rows			= count($customer_po); 	  
if($num_rows>0)	  
{
   $msg = true;
   $customer_po_orders_id			=  $customer_po[0]->orders_id;					 
   $product_details					= product_name_generated_with_quantity_json($customer_po_orders_id);
   $do_total						= Get_POTOTALVALUE($customer_po_orders_id);   
}
else
{
 $msg = false;
	$customer_po_orders_id			= "0";					 
	$product_details				= "0";
	$do_total						= "";					 
}

  return response()->json([            
         'msg' => $msg, 
		  'num_rows' => $num_rows, 
        "customer_po_details"=> array('customer_po' =>$customer_po, 	'product_items_details' => $product_details,'do_total' => $do_total ), 
        ]);
		
//		'highest_value_po' => array("highest_value_po"=>$highest_value_po, 'product_items_details' => $product_details,) 		
		
}


function po_by_customer_segment(Request $request)
{
	$today_date					= date("Y-m-d");
	$qtr_start_date_show		= "2019-04-01";
	$qtr_end_date_show			= $today_date;//"2024-03-31";
	$cust_segment				= $request->cust_segment;
	$app_cat_id					= $request->product_id;	
	$offer_type					= $request->offer_type;	


if($offer_type!='' && $offer_type!='0')
	{
	//$orders_status='Pending';
	$offer_type_search=" and o.offer_type='$offer_type'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
		$offer_type_search=" ";
	}	

if($cust_segment!='' && $cust_segment!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
	$cust_segment_search=" ";
	}

if($app_cat_id!='' && $app_cat_id!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
	//$orders_status='Pending';
	$app_cat_id_search=" ";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}	
	
//enq_source_abbrv	
"<br>".$sql = "SELECT
o.orders_id, 
o.order_by,  
o.billing_company, 
o.total_order_cost_new, 
top.pro_id,
tdo.Cus_Com_Name,
tble_invoice.I_date,  
tdo.Prepared_by,
tdo.po_date,
l.cust_segment, 
o.customers_id,
a.admin_fname,
a.admin_lname,
tdo.D_Order_Date
from tbl_order o , 
tbl_delivery_order tdo ,
tbl_do_products tdp ,
tbl_lead l,
tbl_admin a,
tbl_order_product top, 
tbl_delivery_challan,
tble_invoice

where 
o.orders_id=tdp.OID 
and o.orders_id=top.order_id 
and o.order_by=a.admin_id 
and l.id=o.lead_id  

and  tdo.O_Id = tdp.OID
and tdo.O_Id = tbl_delivery_challan.O_Id   
AND tble_invoice.o_id=tdo.O_Id 
and o.orders_status IN('Confirmed','Order Closed')
$cust_segment_search
$app_cat_id_search
$offer_type_search
AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
group by l.cust_segment 

order by tbl_delivery_challan.invoice_gen_date desc";	
$po_by_customer_segment					=  DB::select(($sql));					
$num_rows			= count($po_by_customer_segment); 	  





if($num_rows>0)	  
{
   $msg = true;
   $po_by_customer_segment_orders_id			=  $po_by_customer_segment[0]->orders_id;					 
   $product_details								= product_name_generated_with_quantity_json($po_by_customer_segment_orders_id);
   $do_total									= Get_POTOTALVALUE($po_by_customer_segment_orders_id);      
}
else
{
 $msg = false;
	$po_by_customer_segment_orders_id			=  "0";					 
	$product_details							= "0";					 
   $do_total									= "0";      	
}

  return response()->json([            
         'msg' => $msg, 
		  'num_rows' => $num_rows, 
        "po_by_customer_segment_details"=> array('po_by_customer_segment' =>$po_by_customer_segment, 	'product_items_details' => $product_details, 'do_total' => $do_total ), 
        ]);



/*
if($num_rows>0)	  
{
   $msg = array("msg"=>"true","po_by_customer_segment"=>$po_by_customer_segment);
}
else
{
  $msg = array("msg"=>"false","po_by_customer_segment"=>"No records found");
}

  return response()->json([            
         'po_by_customer_segment' => $po_by_customer_segment, 
        ]);*/
}

 public function add_new_company(Request $request)
    {
//		exit;
// return response()->json($request->all());
 $comp_array= $request->all();
 /*echo "<pre>";
	print_r($comp_array);*/	
//		print_r($request);
   // exit;
	$date 	  				= date('Y-m-d');

    $comp_name 				= $comp_array["companyDetails"]["comp_name"]; //exit;
    $co_extn_id				= $comp_array["companyDetails"]["co_extn_id"]; //exit;	
    $no_of_emp				= "0";//$comp_array["companyDetails"]["no_of_emp"];
    $office_type			= $comp_array["companyDetails"]["office_type"];
 	$parent_id 				= $comp_array["companyDetails"]["parent_id"];
    $cust_segment			= $comp_array["companyDetails"]["cust_segment"];
    $tele_no_co				= $comp_array["companyDetails"]["tele_no_co"];  
	$gst_no					= $comp_array["companyDetails"]["gst_no"];  
    $comp_website			= $comp_array["companyDetails"]["comp_website"];
    $address				= $comp_array["companyDetails"]["address"];
    $country0				= $comp_array["companyDetails"]["country0"];	
    $state0					= $comp_array["companyDetails"]["state0"];
    $city0					= $comp_array["companyDetails"]["city0"];
    $zip0					= $comp_array["companyDetails"]["zip0"];	
    $fax_no					= "0";
    $acc_manager			= $comp_array["companyDetails"]["acc_manager"];
    $comp_revenue			= "0";//$comp_array["companyDetails"]["comp_revenue"];
	$no_of_emp				= "0";

    $situ 					= $comp_array["contactDetails"]["situ"];
    $fname					= $comp_array["contactDetails"]["fname"];
    $lname					= $comp_array["contactDetails"]["lname"];
    $designation_id			= $comp_array["contactDetails"]["designation"];
    $department				= $comp_array["contactDetails"]["department"];	
    $email					= $comp_array["contactDetails"]["email"];
    $mobile_no				= $comp_array["contactDetails"]["mobile_no"];
    $tele_no				= $comp_array["contactDetails"]["tele_no"];
	$p_address				= $comp_array["contactDetails"]["address"];	
    $country				= $comp_array["contactDetails"]["country"];
    $state					= $comp_array["contactDetails"]["state"];
    $city					= $comp_array["contactDetails"]["city"];	
    $zip					= $comp_array["contactDetails"]["zip"];
	
    $description			= "0";//$comp_array["companyDetails"]["descr"];
    $status					= "active";//$comp_array["companyDetails"]["status"];
	$quality_check			= "0";
	$india_mart_co			= "no";
	
   		$fileArrayist["salutation"]          	= $situ; 
		$fileArrayist["parent_id"]          	= $parent_id; 
		$fileArrayist["fname"]          		= $fname; 
        $fileArrayist["lname"]          		= $lname;
		$fileArrayist["comp_name"]          	= $comp_name;
		$fileArrayist["co_extn_id"]          	= $co_extn_id;
		$fileArrayist["co_extn"]          		= $co_extn_id;				
        $fileArrayist["no_of_emp"]          	= "0";//$no_of_emp; 
        $fileArrayist["office_type"]           	= $office_type;
        $fileArrayist["co_division"]           	= "0";//$co_division; 
   		$fileArrayist["email"]            		= $email; 
        $fileArrayist["address"]         		= $address;
        $fileArrayist["country"]          		= $country0;		
        $fileArrayist["state"]          		= $state0;
   		$fileArrayist["city"]        			= $city0;
        $fileArrayist["zip"]          			= $zip0;

        $fileArrayist["telephone"]          	= $tele_no_co;
        $fileArrayist["fax_no"]          		= "0";$fax_no; 
        $fileArrayist["ref_source"]          	= "0";//$ref_source; 
        $fileArrayist["cust_segment"]          	= $cust_segment;
        $fileArrayist["acc_manager"]          	= $acc_manager;
        $fileArrayist["comp_website"]  			= $comp_website; 
        $fileArrayist["comp_revenue"]  			= "0";//$comp_revenue; 
        $fileArrayist["mobile_no"]          	= $mobile_no; 
		
		$fileArrayist["designation_id"]			= $designation_id;
		$fileArrayist["department_id"]			= $department;		
        $fileArrayist["description"]          	= $description; 
        $fileArrayist["status"]      			= $status; 
		$fileArrayist["india_mart_co"]			= $india_mart_co;	
		$fileArrayist["quality_check"]			= $quality_check;	
        $fileArrayist["co_city"]      			= $city0; 
		$fileArrayist["gst_no"]					= $gst_no;	
	 	
		$previous_company_check					= company_name_check($comp_name,$tele_no_co);//exit;
	if($previous_company_check!='0')	 	
{
 $msg = array("msg"=>"false","comp_id"=>"This company already exists.");
}
else
{
	
	    $inserted_comp_id 					= DB::table('tbl_comp')->insertGetId($fileArrayist);		
		$fileArrayEnq["inserted_comp_id"]	= $inserted_comp_id; 
		$fileArrayCoPerson["company_id"]	= $inserted_comp_id;
		$fileArrayCoPerson["salutation"]	= $situ;
		$fileArrayCoPerson["fname"]			= $fname;
		$fileArrayCoPerson["lname"]			= $lname;
		$fileArrayCoPerson["email"]			= $email;
		$fileArrayCoPerson["address"]		= $p_address;
		$fileArrayCoPerson["city"]			= $city;
		$fileArrayCoPerson["country"]		= $country;
		$fileArrayCoPerson["state"]			= $state;
		$fileArrayCoPerson["zip"]			= $zip;
		$fileArrayCoPerson["telephone"]		= $tele_no;
		$fileArrayCoPerson["fax_no"]		= "0";$fax_no;
		$fileArrayCoPerson["acc_manager"]	= $acc_manager;
		$fileArrayCoPerson["designation_id"]= $designation_id;
		$fileArrayCoPerson["department_id"]	= $department;
		$fileArrayCoPerson["mobile_no"]		= $mobile_no;
		$fileArrayCoPerson["status"]		= "active";
		$fileArrayCoPerson["comp_website"] 	= $comp_website;

    	$inserted_comp_person_id 			= DB::table('tbl_comp_person')->insertGetId($fileArrayCoPerson);
	 
	
	
	
$search		= "SELECT 
    tbl_comp.id AS company_id, 
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name) 
        ELSE tbl_comp.comp_name 
    END AS company_full_name, 
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
	
AND (tbl_comp.id = '".$inserted_comp_id."' )
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
 
$result_enq =  DB::select(($search));					
$num_rows	= count($result_enq); 	  
	
		
if($inserted_comp_id){
           $msg = array("msg"=>"true","comp_id"=>$inserted_comp_id,"comp_person_id"=>$inserted_comp_person_id,"company_search_data"=>$result_enq);
}
else
{
           $msg = array("msg"=>"false","comp_id"=>0,"company_search_data"=>$result_enq);
}

}


//insert data in lead table	
//exit;
//$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 
        return response()->json([            
        'message' => $msg, 
        ]);  

}




public function edit_company(Request $request)
    {
//		exit;
// return response()->json($request->all());
 $comp_array= $request->all();
//  $lead_array			= $request->all();
/* echo "<pre>";
	//print_r($comp_array);	
		print_r($comp_array);
    exit;*/
	$date 	  				= date('Y-m-d');

    $comp_id 				= $comp_array["companyDetails"]["comp_id"]; //exit;
	$comp_name 				= $comp_array["companyDetails"]["comp_name"]; //exit;
    $co_extn_id				= $comp_array["companyDetails"]["co_extn_id"]; //exit;	
    $no_of_emp				= "0";//$comp_array["companyDetails"]["no_of_emp"];
    $office_type			= $comp_array["companyDetails"]["office_type"];
 	$parent_id 				= $comp_array["companyDetails"]["parent_id"];
    $cust_segment			= $comp_array["companyDetails"]["cust_segment"];
    $tele_no_co				= $comp_array["companyDetails"]["tele_no_co"];  
	$gst_no					= $comp_array["companyDetails"]["gst_no"];  
    $comp_website			= $comp_array["companyDetails"]["comp_website"];
    $address				= $comp_array["companyDetails"]["address"];
    $country0				= $comp_array["companyDetails"]["country0"];	
    $state0					= $comp_array["companyDetails"]["state0"];
    $city0					= $comp_array["companyDetails"]["city0"];
    $zip0					= $comp_array["companyDetails"]["zip0"];	
    $fax_no					= "0";
    $acc_manager			= $comp_array["companyDetails"]["acc_manager"];
    $comp_revenue			= "0";//$comp_array["companyDetails"]["comp_revenue"];
	$no_of_emp				= "0";

/*    $situ 					= $comp_array["contactDetails"]["situ"];
    $fname					= $comp_array["contactDetails"]["fname"];
    $lname					= $comp_array["contactDetails"]["lname"];
    $designation_id			= $comp_array["contactDetails"]["designation"];
    $department				= $comp_array["contactDetails"]["department"];	
    $email					= $comp_array["contactDetails"]["email"];
    $mobile_no				= $comp_array["contactDetails"]["mobile_no"];
    $tele_no				= $comp_array["contactDetails"]["tele_no"];
	$p_address				= $comp_array["contactDetails"]["address"];	
    $country				= $comp_array["contactDetails"]["country"];
    $state					= $comp_array["contactDetails"]["state"];
    $city					= $comp_array["contactDetails"]["city"];	
    $zip					= $comp_array["contactDetails"]["zip"];*/
	
    $description			= "0";//$comp_array["companyDetails"]["descr"];
    $status					= $comp_array["companyDetails"]["status"];
	$quality_check			= "0";
	$india_mart_co			= "no";
	
   		//$fileArrayist["salutation"]          	= $situ; 
		$fileArrayist["parent_id"]          	= $parent_id; 
		//$fileArrayist["fname"]          		= $fname; 
       // $fileArrayist["lname"]          		= $lname;
		$fileArrayist["comp_name"]          	= $comp_name;
		$fileArrayist["co_extn_id"]          	= $co_extn_id;
		$fileArrayist["co_extn"]          		= $co_extn_id;				
        $fileArrayist["no_of_emp"]          	= "0";//$no_of_emp; 
        $fileArrayist["office_type"]           	= $office_type;
        $fileArrayist["co_division"]           	= "0";//$co_division; 
   		//$fileArrayist["email"]            		= $email; 
        $fileArrayist["address"]         		= $address;
        $fileArrayist["country"]          		= $country0;		
        $fileArrayist["state"]          		= $state0;
   		$fileArrayist["city"]        			= $city0;
        $fileArrayist["zip"]          			= $zip0;

        $fileArrayist["telephone"]          	= $tele_no_co;
        $fileArrayist["fax_no"]          		= "0";//$fax_no; 
        $fileArrayist["ref_source"]          	= "0";//$ref_source; 
        $fileArrayist["cust_segment"]          	= $cust_segment;
        $fileArrayist["acc_manager"]          	= $acc_manager;
        $fileArrayist["comp_website"]  			= $comp_website; 
        $fileArrayist["comp_revenue"]  			= "0";//$comp_revenue; 
     //   $fileArrayist["mobile_no"]          	= $mobile_no; 
		
		////$fileArrayist["designation_id"]			= $designation_id;
		//$fileArrayist["department_id"]			= $department;		
      //  $fileArrayist["description"]          	= $description; 
        $fileArrayist["status"]      			= $status; 
		//$fileArrayist["india_mart_co"]			= $india_mart_co;	
	//	$fileArrayist["quality_check"]			= $quality_check;	
        $fileArrayist["co_city"]      			= $city0; 
		$fileArrayist["gst_no"]					= $gst_no;	
	 	
		$previous_company_check					= company_name_check($comp_name);//exit;
	 $edit_company_result= DB::table('tbl_comp')
            ->where('id', $comp_id)
          ->update($fileArrayist);	
	
		
if($edit_company_result){
           $msg = array("msg"=>"true","comp_id"=>$comp_id,"company_search_data"=>$edit_company_result);
}
else
{
           $msg = array("msg"=>"false","comp_id"=>0,"company_search_data"=>$edit_company_result);
}

 


//insert data in lead table	
//exit;
//$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 
        return response()->json([            
        'message' => $msg, 
        ]);  

}


public function add_new_person_in_company(Request $request)
    {
 $comp_person_array		= $request->all();
 $date 	  				= date('Y-m-d');


  // $dataArray['parent_id'] = $comp_person_array["parent_id"];
  
   	$comp_id 								= $comp_person_array["company_id"];
    $situ 									= $comp_person_array["situ"];
    $fname									= $comp_person_array["fname"];
    $lname									= $comp_person_array["lname"];
    $email									= $comp_person_array["email"];
    $address								= $comp_person_array["address"];
    $country								= $comp_person_array["country"];
    $state									= $comp_person_array["state"];
    $city									= $comp_person_array["city"];
    $zip									= $comp_person_array["zip"];
    $telephone								= $comp_person_array["tele_no"];
    $mobile_no								= $comp_person_array["mobile_no"];
    $fax_no									= "0";
    $designation_id							= $comp_person_array["designation"];	
	$department_id							= $comp_person_array["dept"];	
    $acc_manager							= $comp_person_array["acc_manager"];
    $status									= "active";//$comp_person_array["status"];
	$comp_website							= "0";


	$fileArrayist["company_id"]          	= $comp_id; 
	$fileArrayist["salutation"]          	= $situ; 
	$fileArrayist["fname"]          		= $fname; 
	$fileArrayist["lname"]          		= $lname;
	$fileArrayist["comp_website"]  			= $comp_website; 
	$fileArrayist["email"]            		= $email; 
	$fileArrayist["address"]         		= $address;
	$fileArrayist["country"]  				= $country; 
	$fileArrayist["state"]          		= $state;
	$fileArrayist["city"]        			= $city;
	$fileArrayist["zip"]          			= $zip;
	$fileArrayist["acc_manager"]          	= $acc_manager;
	$fileArrayist["telephone"]          	= $telephone;
	$fileArrayist["mobile_no"]          	= $mobile_no; 
	$fileArrayist["fax_no"]          		= $fax_no; 	
	$fileArrayist["designation_id"]  		= $designation_id; 
	$fileArrayist["department_id"]  		= $department_id; 	


//   		$previous_lead_id_check					= lead_id_from_enq_edit_table($enq_id);//exit;
$inserted_comp_person_id 					= DB::table('tbl_comp_person')->insertGetId($fileArrayist);		
$fileArrayEnq["inserted_comp_person_id"]	= $inserted_comp_person_id; 

if($inserted_comp_person_id){
	
$sql_comp_person_details="

SELECT 
    cp.id AS comp_person_id, -- Keep comp_person_id as it is for tbl_comp_person
    cp.salutation as salutation,
    cp.fname AS fname,
    cp.lname AS lname,
    cp.designation_id AS designation_id,
    d.designation_name AS designation_name, 
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
    tbl_country.country_name, 
    tbl_zones.zone_name, 
    all_cities.city_name
	
FROM 
    tbl_comp_person cp
LEFT JOIN 
    tbl_designation_comp d ON cp.designation_id = d.designation_id -- Join to get designation_name
LEFT JOIN tbl_country 
    ON tbl_country.country_id = cp.country 
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = cp.state 
LEFT JOIN all_cities 
    ON all_cities.city_id = cp.city 	
WHERE 
    cp.deleteflag = 'active' AND cp.company_id = '$comp_id'	";	


$result_comp_person_details 				=  DB::select(($sql_comp_person_details));					
$num_rows									= count($result_comp_person_details); 	  
	  
if($num_rows>0)	  
{
	
           $msg = array("msg"=>"true","comp_person_id"=>$inserted_comp_person_id,"num_rows"=>$num_rows,"company_search_data"=>$result_comp_person_details);
}
else
{
           $msg = array("msg"=>"false","comp_person_id"=>0,"company_search_data"=>0,"num_rows"=>$num_rows);
}
}

//insert data in lead table	
//exit;
//$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 
        return response()->json([            
        'message' => $msg, 
        ]);  

}

public function get_person_details(Request $request)
    {
	$company_person_id=$request->company_person_id;
	$company_id=$request->company_id;
	
	  if($company_person_id!='0')
	  {             
   $rs_person 	= DB::table('tbl_comp_person')->where('deleteflag', '=', 'active')->where('id', '=', $company_person_id)->orderby('fname','asc')->get();
     $num_rows	= count($rs_person); 	  
   $msg = array("msg"=>"true","comp_person_details"=>$rs_person);
       }
	   
	   
else  if($company_person_id=='0')
	  {             
  $rs_person 	= DB::table('tbl_comp')->where('deleteflag', '=', 'active')->where('id', '=', $company_id)->orderby('fname','asc')->get();
     $num_rows	= count($rs_person); 	  
   $msg = array("msg"=>"true","comp_person_details"=>$rs_person);
       }	   
	   
	   
	else
	{
 $msg = array("msg"=>"false","comp_person_details"=>"Company person id id is missing: please pass company_person_id as parameter");
	}
	
	  
   	  
if($num_rows>0)	  
{
   $msg = array("msg"=>"true","comp_person_details"=>$rs_person);
}
else
{
	  
	  $msg = array("msg"=>"false","comp_person_details"=>"No records found");
}	
        // Logic for your dashboard, e.g., returning dashboard data.
  return response()->json([            
         'comp_person_details' => $msg, 
        ]);
    }	


//edit person in company

public function edit_person_in_company(Request $request)
    {
//		exit;
// return response()->json($request->all());


 $comp_person_array= $request->all();
$date 	  				= date('Y-m-d');


  // $dataArray['parent_id'] = $comp_person_array["parent_id"];
 // print_r($comp_person_array); exit;
   	$comp_id 								= $comp_person_array["company_id"]; //exit;
	$company_person_id 						= $comp_person_array["company_person_id"];

    $situ 									= $comp_person_array["situ"];
    $fname									= $comp_person_array["fname"];
    $lname									= $comp_person_array["lname"];
    $email									= $comp_person_array["email"];
    $address								= $comp_person_array["address"];
    $country								= $comp_person_array["country"];
    $state									= $comp_person_array["state"];
    $city									= $comp_person_array["city"];
    $zip									= $comp_person_array["zip"];
    $telephone								= $comp_person_array["tele_no"];
    $mobile_no								= $comp_person_array["mobile_no"];
    $fax_no									= "0";
    $designation_id							= $comp_person_array["designation"];	
	$department_id							= $comp_person_array["dept"];	
    $acc_manager							= $comp_person_array["acc_manager"];
    $status									= "active";//$comp_person_array["status"];
	$comp_website							= "0";


if($company_person_id=='0')
{ 
	
	//$fileArrayist["company_id"]          	= $comp_id; 
	$fileArrayist["salutation"]          	= $situ; 
	$fileArrayist["fname"]          		= $fname; 
	$fileArrayist["lname"]          		= $lname;
	$fileArrayist["comp_website"]  			= $comp_website; 
	$fileArrayist["email"]            		= $email; 
	$fileArrayist["address"]         		= $address;
	$fileArrayist["country"]  				= $country; 
	$fileArrayist["state"]          		= $state;
	$fileArrayist["city"]        			= $city;
	$fileArrayist["zip"]          			= $zip;
	$fileArrayist["acc_manager"]          	= $acc_manager;
	$fileArrayist["telephone"]          	= $telephone;
	$fileArrayist["mobile_no"]          	= $mobile_no; 
	$fileArrayist["fax_no"]          		= $fax_no; 	
	$fileArrayist["designation_id"]  		= $designation_id; 
	$fileArrayist["department_id"]  		= $department_id;	
	
	
$edit_person_in_company_result= DB::table('tbl_comp')
            ->where('id', $comp_id)
          ->update($fileArrayist);	
}
else
{
	
		$fileArrayist["company_id"]          	= $comp_id; 
	$fileArrayist["salutation"]          	= $situ; 
	$fileArrayist["fname"]          		= $fname; 
	$fileArrayist["lname"]          		= $lname;
	$fileArrayist["comp_website"]  			= $comp_website; 
	$fileArrayist["email"]            		= $email; 
	$fileArrayist["address"]         		= $address;
	$fileArrayist["country"]  				= $country; 
	$fileArrayist["state"]          		= $state;
	$fileArrayist["city"]        			= $city;
	$fileArrayist["zip"]          			= $zip;
	$fileArrayist["acc_manager"]          	= $acc_manager;
	$fileArrayist["telephone"]          	= $telephone;
	$fileArrayist["mobile_no"]          	= $mobile_no; 
	$fileArrayist["fax_no"]          		= $fax_no; 	
	$fileArrayist["designation_id"]  		= $designation_id; 
	$fileArrayist["department_id"]  		= $department_id;
	
$edit_person_in_company_result= DB::table('tbl_comp_person')
            ->where('id', $company_person_id)
          ->update($fileArrayist);
}
		  
if($edit_person_in_company_result)		  
{
 $msg = array("msg"=>"true","result"=>$edit_person_in_company_result);		  
}
else
{
	$msg = array("msg"=>"false","result"=>$edit_person_in_company_result);		  
}

        return response()->json([            
        'message' => $msg, 
        ]);  

}

public function get_comp_list(Request $request)
    {
$search_by		=	$request->search_by;
$newsearch 		= 	addslashes(str_replace("_","&",$search_by));		
             
/*$search			= 	"SELECT 
    tbl_comp.id AS company_id, 
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5,6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name) 
        ELSE tbl_comp.comp_name 
    END AS company_full_name, 
    tbl_comp.comp_name,
	tbl_comp.acc_manager
	FROM tbl_comp 
	LEFT JOIN tbl_company_extn 
    ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id  
	WHERE 
    tbl_comp.deleteflag = 'active' 
	and tbl_comp.status = 'active' 	
	and tbl_comp.india_mart_co = 'no' 	
	and tbl_comp.parent_id = '0' 	
AND tbl_comp.comp_name like '".$newsearch."%' 	
 
 GROUP BY 
    tbl_comp.id, 
    company_full_name 
 order by tbl_comp.comp_name asc; ";*/	
 
 
 
$search			= "SELECT 
    tbl_comp.id AS company_id, 
    CASE 
        WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
        THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name) 
        ELSE tbl_comp.comp_name 
    END AS company_full_name, 
    MAX(tbl_comp_person.id) AS tbl_comp_person_id, -- Ensures only one person ID per company
    tbl_comp.comp_name,
	tbl_comp.acc_manager, 
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
       
 // Logic for your dashboard, e.g., returning dashboard data.
  return response()->json([            
         'company_search_data' => $msg, 
        ]);
 }	

//get comp details on the baiss of comp_id;
public function get_comp_details(Request $request)
{
    $comp_id = $request->comp_id;

    if ($comp_id != '') {
        $comp_id_search = " AND id = '$comp_id'";
    } else {
        $comp_id_search = "";
    }

    $comp_details_query = "SELECT * FROM tbl_comp WHERE 1=1 $comp_id_search";
    $result_comp = DB::select($comp_details_query);

    if (count($result_comp) > 0) {
        $row = $result_comp[0];

        $parent_company_name = '';
        if (!empty($row->parent_id) && $row->parent_id != '0') {
            $parent_company_name = company_name($row->parent_id);
        }

        // Convert stdClass object to array
        $row_array = (array)$row;

        // Merge parent company name into the same array
        $company_data = $row_array;
        $company_data['parent_co_name'] = $parent_company_name;

        $msg = [
            "msg" => "true",
            "company_details" => $company_data,
            
        ];
    } else {
        $msg = [
            "msg" => "false",
            "company_details" => "No records found"
        ];
    }

    return response()->json([
        'company_details' => $msg
    ]);
}
	

public function comp_extn()
    {
        $rs_comp_extn 	= DB::table('tbl_company_extn')->where('deleteflag', '=', 'active')->orderby('company_extn_name','asc')->select('company_extn_id','company_extn_name')->get();
        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'comp_extn' => $rs_comp_extn, 
        ]);
    }	
	
public function comp_department()
    {
        $rs_comp_department 	= DB::table('tbl_department_comp')->where('deleteflag', '=', 'active')->orderby('department_name','asc')->select('department_id','department_name')->get();
        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'comp_department' => $rs_comp_department, 
        ]);
    }	

public function comp_designation()
    {
        $rs_comp_designation 	= DB::table('tbl_designation_comp')->where('deleteflag', '=', 'active')->orderby('designation_name','asc')->select('designation_id','designation_name')->get();
        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'comp_designation' => $rs_comp_designation, 
        ]);
    }		


public function salutation_master()
    {
        $rs_salutation_master 	= DB::table('tbl_salutation')->where('deleteflag', '=', 'active')->orderby('salutation_name','asc')->select('salutation_id','salutation_name')->get();
        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'salutation_master' => $rs_salutation_master, 
        ]);
    }		


	
}//class closed
