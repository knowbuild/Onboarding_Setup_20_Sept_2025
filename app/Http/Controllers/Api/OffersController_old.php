<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class OffersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
   #############################################Leads#############################################





#############################################Leads#############################################
//offers listing	
 public function offerslisting(Request $request)
    {
       
	   
 	   
	   
//$AdminLoginID_SET = Auth::user()->id;
	$acc_manager        		= $request->acc_manager;
	$month_search  				= $request->month_search;
	$OrderNo  					= $request->order_no;
	$enq_id  					= $request->enq_id;
	$comp_name_search	  		= $request->comp_name_search;
	$state_search  				= $request->state_search;
	$app_cat_id_search  		= $request->product_category;
	$cust_segment_search  		= $request->cust_segment;
	$datevalid_from 			= $request->datevalid_from;
	$datevalid_to				= $request->datevalid_to;
	$follow_up_datevalid_to		= $request->follow_up_datevalid_to;
	$mobile_search				= $request->mobile_search;
	$hot_offer_search			= $request->hot_offer;	
	$orders_status				= $request->orders_status;
//	$payment_method				= $request->payment_method;
	$customers_name				= $request->customers_name;
	$customers_contact_no 		= $request->customers_contact_no;
	$pro_name 		  			= $request->pro_name;
	$sort_by	 				= $request->sort_by;
	$offer_probability			= $request->offer_probability;	
	$offer_type 				= $request->offer_type;	
 	$followup_offer_filter		= $request->followup_offer_filter;	
	$search_by					= $request->search_by;	
	$pro_name					= $request->pro_name;	
	$min_value					= $request->min_value;
	$max_value					= $request->max_value;		
	
	
if($followup_offer_filter=='' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" ";
	}
else if($followup_offer_filter=='1' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  >  CURDATE()";
	}

else if($followup_offer_filter=='2' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  <  CURDATE()";
	}
else
{
		$followup_offer_filter_cond=" ";
}

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}
	
		
if($sort_by=='date_asc')
{
	$order_by="date_ordered";
	$order="asc";
}

else
{
		$order_by="date_ordered";
	$order="desc";
}
if($sort_by=='date_desc')
{
		$order_by="date_ordered";
		$order="desc";
}

if($sort_by=='amt_desc')
{
		$order_by="total_order_cost";
		$order="asc";
}

if($sort_by=='amt_asc')
{
		$order_by="total_order_cost";
		$order="desc";
}


if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}
else
{
	$order_no_search="";
}


if($enq_id!='' )
	{
	//$orders_status='Pending';
	$enq_id_search=" and  edited_enq_id = '$enq_id'";
	}
else
{
	$enq_id_search="";
}

 

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}

if($app_cat_id_search!='' && $app_cat_id_search!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search_search=" and t3.app_cat_id='$app_cat_id_search'";
	}
	else
	{
		$app_cat_id_search_search=" ";
	}

if($cust_segment_search!='' && $cust_segment_search!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search_search=" and t3.cust_segment='$cust_segment_search'";
	}
else
{
	$cust_segment_search_search="";
}
 
 
if($month_search!='' && $month_search!='0')
	{
//$orders_status='Pending';
$month_search_search="and ensure_sale_month='$month_search'";
	}
	else
	{
$month_search_search=" ";
	}


if($comp_name_search!='' && $comp_name_search!='0')
	{
	//$orders_status='Pending';
//	$comp_name_search_search="and  t1.customers_id = '$comp_name_search'";
	$CateParent_search="and  t1.shipping_company LIKE '%$comp_name_search%'";
	}
	else
	{
		$CateParent_search ="";
	}

if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}

if($acc_manager!='' && $acc_manager!='0')
	{
	//$orders_status='Pending';
	$acc_manager_search="and order_by='$acc_manager'";
	}
	else
	{
		$acc_manager_search="";
	}

if($offer_probability!='0' && $offer_probability!='')
	{
	//$orders_status='Pending';
//echo 	$offer_probability_search_rec="and offer_probability='$offer_probability'";
$offer_probability_search_rec=" and offer_probability IN ($offer_probability)";	//view offer 
	}
else
{
$offer_probability_search_rec="  ";	//view offer 
}


if($offer_type!='' && $offer_type!='0') //default product
{
	$offer_type_search=" and t1.offer_type='$offer_type'";	

}
else
{
$offer_type_search=" and t1.offer_type='product'";	
}



	if($orders_status!='')
	{
	//$orders_status='Pending';
	$orders_status_search=" and orders_status='$orders_status'";
	}
	else
	{
	$orders_status_search=" ";		
	}

//c name
	if($customers_name!='')
	{
	//$orders_status='Pending';
	$customers_name_search=" and billing_company like '%$customers_name%'";
	}
	else
	{
			$customers_name_search=" ";
	}
//c NO
	if($customers_contact_no!='')
	{
	//$orders_status='Pending';
	$customers_contact_no_search=" and customers_contact_no='$customers_contact_no'";
	}
	else
	{
	$customers_contact_no_search=" ";		
	}
//Pro Name
	if($pro_name!='')
	{
	//$orders_status='Pending';
	$pro_name_search=" and t2.pro_name like '%$pro_name%'";
	}
	else
	{
	$pro_name_search=" ";		
	}
//$payment_method_search


if($datevalid_from!='' && $datevalid_to!='')
	{
		
$date_range_search=" AND (date( t1.date_ordered ) BETWEEN '$datevalid_from' AND '$datevalid_to')";
	}

else
{
	$date_range_search ="";
}
if($follow_up_datevalid_to!='')
	{
		
$follow_up_datevalid_to_search=" AND (date( t1.follow_up_date ) <= '$follow_up_datevalid_to' )";
	}
	else
	{
$follow_up_datevalid_to_search="";
	}

if($mobile_search!='')
	{
		
$mobile_search_search=" AND customers_contact_no = '$mobile_search' ";
	}
else
{
	$mobile_search_search="";
}

if($hot_offer_search!='')
	{
		
$hot_offer_search_search=" AND hot_offer = '$hot_offer_search' ";
	}
else
{
	$hot_offer_search_search="  ";
}


	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword= " AND t1.customers_name like '%".$search_by."%' OR t1.customers_email like '%".$search_by."%' OR t1.customers_contact_no like '%".$search_by."%' OR t1.shipping_company like '%".$search_by."%'   OR t2.pro_name like '%".$search_by."%'  ";
	}

else
{
$search_by_keyword="";
}


if ($min_value != '' && $max_value != '')
{
 
    $estimated_value_search = " and  t1.total_order_cost_new BETWEEN $min_value AND $max_value ";
}
else
{
	   $estimated_value_search = " ";
}


$searchRecord 	= " $orders_status_search $month_search_search $customers_name_search $app_cat_id_search_search  $customers_contact_no_search $pro_name_search   $acc_manager_search $offer_probability_search_rec $order_no_search $enq_id_search $pro_name_search $CateParent_search $date_range_search $follow_up_datevalid_to_search $mobile_search_search $offer_type_search $hot_offer_search_search  $cust_segment_search_search $followup_offer_filter_cond $search_by_keyword $estimated_value_search "; 
	if(strlen(trim($order))<=0)
	{
	$order 	 	 = 'desc';
	}
	if(strlen(trim($order_by))<=0)
	{
	$order_by 	 = 'orders_id';
	}	
	
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
	
	$from = (($page * $max_results) - $max_results);
 
		if($datevalid_from!='')
		{
			$fromDate 	  = $s->getDateformate($datevalid_from,'mdy','ymd','-');
			$datevalid_from = "";
		}
		else
		{
			$fromDate = '';
		}
		if($datevalid_to!= '')
		{
			$toDate	  	  = $s->getDateformate($datevalid_to,'mdy','ymd','-');
			$datevalid_to = "";
		}
		else
		{
			$toDate	= "";
		}
 

  $sql_offer= "SELECT 
    JSON_OBJECT(
        'company_full_name', CASE 
                                WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
                                THEN CONCAT(tbl_comp.comp_name, '', tbl_company_extn.company_extn_name)
                                ELSE tbl_comp.comp_name
                            END,
        'time_lead_added', t3.time_lead_added,
		'days_since_offer', DATEDIFF(t1.time_ordered, t3.time_lead_added),
        'app_cat_id', t3.app_cat_id,
        'comp_person_id', t3.comp_person_id,
		'ref_source', t3.ref_source,
		'Price_type', t3.Price_type,
        'cust_segment', t3.cust_segment,
        'customers_name', t1.customers_name,
        'customers_email', t1.customers_email,
		'shipping_address', t1.shipping_street_address,
        'shipping_country', t1.shipping_country_name,
		'shipping_zip_code', t1.shipping_zip_code,		
        'shipping_state', t1.shipping_state,
		'shipping_city', t1.shipping_city,
		'country_name', tbl_country.country_name, 
    	'state_name', tbl_zones.zone_name, 
    	'city_name', all_cities.city_name,
    	'ID', tbl_web_enq_edit.ID,
		'enq_remark_edited', tbl_web_enq_edit.enq_remark_edited,
		'shipping_company', t1.shipping_company,
		'billing_company', t1.billing_company,		
        'hot_offer', t1.hot_offer,
        'edited_enq_id', t1.edited_enq_id,
        'total_order_cost_new', t1.total_order_cost_new,
        'orders_id', t1.orders_id,
		'offer_type', t1.offer_type,
        'customers_id', t1.customers_id,
        'Price_type', t1.Price_type,
        'offercode', t1.offercode,
        'offer_currency', t1.offer_currency,
        'ensure_sale_month', t1.ensure_sale_month,
        'follow_up_date', t1.follow_up_date,
		'freight_amount', t1.freight_amount,
        'customers_contact_no', t1.customers_contact_no,
        'date_ordered', t1.date_ordered,
        'time_ordered', t1.time_ordered,
        'orders_status', t1.orders_status,
        'total_order_cost', t1.total_order_cost,
		'offer_warranty', t1.offer_warranty,
		'delivery_terms', t1.delivery_day,
		'offer_validity', t1.offer_validity,
		'show_discount', t1.show_discount,
		'subject', t1.offer_subject,
		'order_in_favor_of', t1.order_in_favor_of,		
		'payment_terms', t1.payment_terms,		
		'offer_probability', t1.offer_probability,		
        'order_by', t1.order_by,
        'lead_id', t1.lead_id,
        'offer_probability', t1.offer_probability,
        'application_name', tbl_application.application_name,
        'cust_segment_name', tbl_cust_segment.cust_segment_name,
        'enq_source_name', tbl_enq_source.enq_source_name,
        'proforma_invoice_id', tpi.pi_id,
		'proforma_invoice_send_for_approval', tpi.save_send,
        'proforma_invoice_status', tpi.pi_status,				
        'admin_name', CONCAT(tbl_admin.admin_fname, ' ', tbl_admin.admin_lname),
        'product_items_details', JSON_ARRAYAGG(
            JSON_OBJECT(
				'pro_id', t2.pro_id,
                'pro_name', t2.pro_name				
            )
        )
		,
    'offer_task_details', JSON_ARRAYAGG(
        JSON_OBJECT(
            'events_id', ev.id,
            	'events_title', ev.title,
            'lead_type', ev.lead_type
        )
    )

    ) AS offer_data
FROM 
    tbl_order AS t1
INNER JOIN 
    tbl_order_product AS t2 ON t1.orders_id = t2.order_id
INNER JOIN 
    tbl_lead AS t3 ON t1.lead_id = t3.id
LEFT JOIN 
    tbl_comp ON tbl_comp.id = t1.customers_id
INNER JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = t3.app_cat_id  
LEFT JOIN 
    tbl_enq_source ON tbl_enq_source.enq_source_description = t3.ref_source
LEFT JOIN 
    tbl_admin ON tbl_admin.admin_id = t1.order_by
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = t1.edited_enq_id
LEFT JOIN tbl_country 
    ON tbl_country.country_id = t1.shipping_country_name
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = t1.shipping_state 
LEFT JOIN all_cities 
    ON all_cities.city_id = t1.shipping_city 
LEFT JOIN 
tbl_performa_invoice as tpi  ON tpi.O_Id = t1.orders_id 	
LEFT JOIN events AS ev ON ev.lead_type = t1.orders_id 	
WHERE 1=1
   $searchRecord
GROUP BY 
 t1.orders_id, t1.customers_name, t1.customers_email, t1.shipping_street_address, 
  t1.shipping_country_name, t1.shipping_zip_code, t1.shipping_state, t1.shipping_city, 
  t1.shipping_company, t1.billing_company, t1.hot_offer, t1.edited_enq_id, 
  t1.total_order_cost_new, t1.offer_type, t1.customers_id, t1.Price_type, 
  t1.offercode, t1.offer_currency, t1.ensure_sale_month, t1.follow_up_date, 
  t1.freight_amount, t1.customers_contact_no, t1.date_ordered, t1.time_ordered, 
  t1.orders_status, t1.total_order_cost, t1.offer_warranty, t1.delivery_day, 
  t1.offer_validity, t1.show_discount, t1.offer_subject, t1.order_in_favor_of, 
  t1.payment_terms, t1.offer_probability, t1.order_by, t1.lead_id, t3.time_lead_added, 
  t3.app_cat_id, t3.comp_person_id, t3.ref_source, t3.cust_segment
ORDER BY 
    $order_by $order
LIMIT $from, $max_results";
$result_offer 	=  DB::select(($sql_offer));	

######################################################## Cache Implementation Start #################################################################
/*$cacheKey = 'offers_' . md5($sql_offer . json_encode($searchRecord) . $order_by . $order . $from . $max_results);
$cacheDuration = env('ENV_MYSQL_CACHE_TIME'); 
$result_offer = Cache::remember($cacheKey, $cacheDuration, function () use ($sql_offer) {    
    return DB::select(($sql_offer));
}); */
############################################################ Cache Implementation END ###############################################################

$qtr_start_date_show	= "2024-04-01";
$qtr_end_date_show		= "2025-03-31";	
/*$offer_probability		= "3";
$hot_offer				= "1";	
$acc_manager			= "99";	
$offer_type				= "product";	
$product_category		= "1";	*/






$product_category  		= $request->product_category;
$cust_segment_search  		= $request->cust_segment;
$datevalid_from 			= $request->datevalid_from;
$datevalid_to				= $request->datevalid_to;
$follow_up_datevalid_to		= $request->follow_up_datevalid_to;

if($follow_up_datevalid_to!='')
	{
		
$follow_up_datevalid_to_search=" AND (date( t1.follow_up_date ) <= '$follow_up_datevalid_to' )";
	}
	else
	{
$follow_up_datevalid_to_search="";
	}



	
$sql_offer_paging="SELECT 
t3.time_lead_added,
'days_since_offer', DATEDIFF(t1.time_ordered, t3.time_lead_added),
t3.app_cat_id,
t3.ref_source,
t3.cust_segment,
t1.shipping_state,
t1.hot_offer,
t1.edited_enq_id,
t1.total_order_cost_new,
t1.orders_id,
t1.customers_id,
t1.Price_type,
t1.offercode,
t1.offer_currency,
t1.ensure_sale_month,
t1.follow_up_date,
t1.customers_contact_no,
t1.date_ordered,
t1.time_ordered,
t1.orders_status,
t1.total_order_cost,
t1.order_by,
t1.lead_id,
t1.offer_probability,
t2.pro_id,
t2.pro_name
 from tbl_order as t1 
INNER JOIN 
    tbl_order_product AS t2 ON t1.orders_id = t2.order_id
INNER JOIN 
    tbl_lead AS t3 ON t1.lead_id = t3.id
LEFT JOIN 
    tbl_comp ON tbl_comp.id = t1.customers_id
INNER JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = t3.app_cat_id  
LEFT JOIN 
    tbl_enq_source ON tbl_enq_source.enq_source_description = t3.ref_source
LEFT JOIN 
    tbl_admin ON tbl_admin.admin_id = t1.order_by
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = t1.edited_enq_id
LEFT JOIN tbl_country 
    ON tbl_country.country_id = t1.shipping_country_name
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = t1.shipping_state 
LEFT JOIN all_cities 
    ON all_cities.city_id = t1.shipping_city 
LEFT JOIN 
tbl_performa_invoice as tpi  ON tpi.O_Id = t1.orders_id 	
LEFT JOIN events AS ev ON ev.lead_type = t1.orders_id 	
WHERE 1=1
   $searchRecord
GROUP BY 
 t1.orders_id, t1.customers_name, t1.customers_email, t1.shipping_street_address, 
  t1.shipping_country_name, t1.shipping_zip_code, t1.shipping_state, t1.shipping_city, 
  t1.shipping_company, t1.billing_company, t1.hot_offer, t1.edited_enq_id, 
  t1.total_order_cost_new, t1.offer_type, t1.customers_id, t1.Price_type, 
  t1.offercode, t1.offer_currency, t1.ensure_sale_month, t1.follow_up_date, 
  t1.freight_amount, t1.customers_contact_no, t1.date_ordered, t1.time_ordered, 
  t1.orders_status, t1.total_order_cost, t1.offer_warranty, t1.delivery_day, 
  t1.offer_validity, t1.show_discount, t1.offer_subject, t1.order_in_favor_of, 
  t1.payment_terms, t1.offer_probability, t1.order_by, t1.lead_id, t3.time_lead_added, 
  t3.app_cat_id, t3.comp_person_id, t3.ref_source, t3.cust_segment
ORDER BY 
    $order_by $order ";
$result_offer_paging 		=  DB::select(($sql_offer_paging));					
$offer_num_rows				= count($result_offer_paging); 	  
$max_estimated_value_offer	= max_estimated_value_offer();

	return response()->json([ 
		'offer_data' => $result_offer,
		'num_rows_count' => $offer_num_rows,
		'max_estimated_value_offer'=>$max_estimated_value_offer		

	]);
		
}

 

 public function offerslist_for_sales_cycle(Request $request)
    {
       
//$AdminLoginID_SET = Auth::user()->id;
	$acc_manager        		= $request->acc_manager;
	$month_search  				= $request->month_search;
	$OrderNo  					= $request->order_no;
	$comp_name_search	  		= $request->comp_name_search;
	$state_search  				= $request->state_search;
	$app_cat_id_search  		= $request->product_category;
	$cust_segment_search  		= $request->cust_segment;
	$datevalid_from 			= $request->datevalid_from;
	$datevalid_to				= $request->datevalid_to;
	$follow_up_datevalid_to		= $request->follow_up_datevalid_to;
	$mobile_search				= $request->mobile_search;
	$hot_offer_search			= $request->hot_offer;	
	$orders_status				= $request->orders_status;
//	$payment_method				= $request->payment_method;
	$customers_name				= $request->customers_name;
	$customers_contact_no 		= $request->customers_contact_no;
	$pro_name 		  			= $request->pro_name;
	$sort_by	 				= $request->sort_by;
	$offer_probability			= $request->offer_probability;	
	$offer_type 				= $request->offer_type;	
 	$followup_offer_filter		= $request->followup_offer_filter;	
	$search_by					= $request->search_by;	
	$pro_name					= $request->pro_name;	
	$min_value					= $request->min_value;
	$max_value					= $request->max_value;		
	
	
if($followup_offer_filter=='' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" ";
	}
else if($followup_offer_filter=='1' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  >  CURDATE()";
	}

else if($followup_offer_filter=='2' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  <  CURDATE()";
	}
else
{
		$followup_offer_filter_cond=" ";
}

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}
	
		
if($sort_by=='date_asc')
{
	$order_by="date_ordered";
	$order="asc";
}

else
{
		$order_by="date_ordered";
	$order="desc";
}
if($sort_by=='date_desc')
{
		$order_by="date_ordered";
		$order="desc";
}

if($sort_by=='amt_desc')
{
		$order_by="total_order_cost";
		$order="asc";
}

if($sort_by=='amt_asc')
{
		$order_by="total_order_cost";
		$order="desc";
}


if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}
else
{
	$order_no_search="";
}

 

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}

if($app_cat_id_search!='' && $app_cat_id_search!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search_search=" and t3.app_cat_id='$app_cat_id_search'";
	}
	else
	{
		$app_cat_id_search_search=" ";
	}

if($cust_segment_search!='' && $cust_segment_search!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search_search=" and t3.cust_segment='$cust_segment_search'";
	}
else
{
	$cust_segment_search_search="";
}
 
 
if($month_search!='' && $month_search!='0')
	{
	//$orders_status='Pending';
	$month_search_search="and ensure_sale_month='$month_search'";
	}
	else
	{
	$month_search_search=" ";
	}


if($comp_name_search!='' && $comp_name_search!='0')
	{
	//$orders_status='Pending';
//	$comp_name_search_search="and  t1.customers_id = '$comp_name_search'";
	$CateParent_search="and  t1.shipping_company LIKE '%$comp_name_search%'";
	}
	else
	{
		$CateParent_search ="";
	}

if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}

if($acc_manager!='' && $acc_manager!='0')
	{
	//$orders_status='Pending';
	$acc_manager_search="and order_by='$acc_manager'";
	}
	else
	{
		$acc_manager_search="";
	}

if($offer_probability!='0' && $offer_probability!='')
	{
	//$orders_status='Pending';
//echo 	$offer_probability_search_rec="and offer_probability='$offer_probability'";
$offer_probability_search_rec=" and offer_probability IN ($offer_probability)";	//view offer 
	}
else
{
$offer_probability_search_rec="  ";	//view offer 
}


if($offer_type!='' && $offer_type!='0') //default product
{
	$offer_type_search=" and t1.offer_type='$offer_type'";	

}
else
{
$offer_type_search=" and t1.offer_type='product'";	
}



	if($orders_status!='')
	{
	//$orders_status='Pending';
	$orders_status_search=" and orders_status='$orders_status'";
	}
	else
	{
	$orders_status_search=" ";		
	}

//c name
	if($customers_name!='')
	{
	//$orders_status='Pending';
	$customers_name_search=" and billing_company like '%$customers_name%'";
	}
	else
	{
			$customers_name_search=" ";
	}
//c NO
	if($customers_contact_no!='')
	{
	//$orders_status='Pending';
	$customers_contact_no_search=" and customers_contact_no='$customers_contact_no'";
	}
	else
	{
	$customers_contact_no_search=" ";		
	}
//Pro Name
	if($pro_name!='')
	{
	//$orders_status='Pending';
	$pro_name_search=" and t2.pro_name like '%$pro_name%'";
	}
	else
	{
	$pro_name_search=" ";		
	}
//$payment_method_search


if($datevalid_from!='' && $datevalid_to!='')
	{
		
$date_range_search=" AND (date( t1.date_ordered ) BETWEEN '$datevalid_from' AND '$datevalid_to')";
	}

else
{
	$date_range_search ="";
}
if($follow_up_datevalid_to!='')
	{
		
$follow_up_datevalid_to_search=" AND (date( t1.follow_up_date ) <= '$follow_up_datevalid_to' )";
	}
	else
	{
$follow_up_datevalid_to_search="";
	}

if($mobile_search!='')
	{
		
$mobile_search_search=" AND customers_contact_no = '$mobile_search' ";
	}
else
{
	$mobile_search_search="";
}

if($hot_offer_search!='')
	{
		
$hot_offer_search_search=" AND hot_offer = '$hot_offer_search' ";
	}
else
{
	$hot_offer_search_search="  ";
}


	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword= " AND t1.customers_name like '%".$search_by."%' OR t1.customers_email like '%".$search_by."%' OR t1.customers_contact_no like '%".$search_by."%' OR t1.shipping_company like '%".$search_by."%'   OR t2.pro_name like '%".$search_by."%'  ";
	}

else
{
$search_by_keyword="";
}


if ($min_value != '' && $max_value != '')
{
 
    $estimated_value_search = " and  t1.total_order_cost_new BETWEEN $min_value AND $max_value ";
}
else
{
	   $estimated_value_search = " ";
}


$searchRecord 	= " $orders_status_search $month_search_search $customers_name_search $app_cat_id_search_search  $customers_contact_no_search $pro_name_search   $acc_manager_search $offer_probability_search_rec $order_no_search $pro_name_search $CateParent_search $date_range_search $follow_up_datevalid_to_search $mobile_search_search $offer_type_search $hot_offer_search_search  $cust_segment_search_search $followup_offer_filter_cond $search_by_keyword $estimated_value_search "; 
	if(strlen(trim($order))<=0)
	{
	$order 	 	 = 'desc';
	}
	if(strlen(trim($order_by))<=0)
	{
	$order_by 	 = 'orders_id';
	}	
	
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
	
	$from = (($page * $max_results) - $max_results);
 
		if($datevalid_from!='')
		{
			$fromDate 	  = $s->getDateformate($datevalid_from,'mdy','ymd','-');
			$datevalid_from = "";
		}
		else
		{
			$fromDate = '';
		}
		if($datevalid_to!= '')
		{
			$toDate	  	  = $s->getDateformate($datevalid_to,'mdy','ymd','-');
			$datevalid_to = "";
		}
		else
		{
			$toDate	= "";
		}
 

$sql_offer= "SELECT 
    JSON_OBJECT(
        'company_full_name', CASE 
                                WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
                                THEN CONCAT(tbl_comp.comp_name, '', tbl_company_extn.company_extn_name)
                                ELSE tbl_comp.comp_name
                            END,
        'time_lead_added', t3.time_lead_added,
        'app_cat_id', t3.app_cat_id,
		'ref_source', t3.ref_source,
        'cust_segment', t3.cust_segment,
        'customers_name', t1.customers_name,
        'customers_email', t1.customers_email,
		'billing_company', t1.billing_company,		
        'hot_offer', t1.hot_offer,
        'edited_enq_id', t1.edited_enq_id,
        'total_order_cost_new', t1.total_order_cost_new,
        'orders_id', t1.orders_id,
		'offer_type', t1.offer_type,
        'customers_id', t1.customers_id,
        'Price_type', t1.Price_type,
        'offercode', t1.offercode,
        'offer_currency', t1.offer_currency,
        'ensure_sale_month', t1.ensure_sale_month,
        'follow_up_date', t1.follow_up_date,
        'customers_contact_no', t1.customers_contact_no,
        'date_ordered', t1.date_ordered,
        'time_ordered', t1.time_ordered,
        'orders_status', t1.orders_status,
        'total_order_cost', t1.total_order_cost,
        'order_by', t1.order_by,
        'lead_id', t1.lead_id,
        'offer_probability', t1.offer_probability,
		'application_name', tbl_application.application_name,
        'cust_segment_name', tbl_cust_segment.cust_segment_name
    ) AS offer_data
FROM 
    tbl_order AS t1
INNER JOIN 
    tbl_lead AS t3 ON t1.lead_id = t3.id
LEFT JOIN 
    tbl_comp ON tbl_comp.id = t1.customers_id
LEFT JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = t3.app_cat_id  
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = t1.edited_enq_id

WHERE 1=1
   $searchRecord
GROUP BY 
 t1.orders_id
ORDER BY 
    $order_by $order
LIMIT $from, $max_results";
$result_offer 	=  DB::select(($sql_offer));					

//$orders_id		=  $result_offer[0]->orders_id;




/*****************************************************************************************************************************************************************************************************/
#sales cycle starts
$qtr_start_date_show	= "2024-04-01";
$qtr_end_date_show		= "2025-03-31";	
/*$offer_probability		= "3";
$hot_offer				= "1";	
$acc_manager			= "99";	
$offer_type				= "product";	
$product_category		= "1";	*/
$product_category  		= $request->product_category;
$cust_segment_search  	= $request->cust_segment;
$datevalid_from 		= $request->datevalid_from;
$datevalid_to			= $request->datevalid_to;
$follow_up_datevalid_to	= $request->follow_up_datevalid_to;

if($follow_up_datevalid_to!='')
	{
		
$follow_up_datevalid_to_search=" AND (date( t1.follow_up_date ) <= '$follow_up_datevalid_to' )";
	}
	else
	{
$follow_up_datevalid_to_search="";
	}



$hot_offer_total		= sales_cycle_offer_total($qtr_start_date_show,$qtr_end_date_show,'3', '1',$acc_manager,$offer_type,$product_category,$cust_segment_search,$datevalid_to,$datevalid_from,$follow_up_datevalid_to);	
$offer_total			= sales_cycle_offer_total($qtr_start_date_show,$qtr_end_date_show,'3', '0',$acc_manager,$offer_type,$product_category,$cust_segment_search,$datevalid_to,$datevalid_from,$follow_up_datevalid_to);	
$opportunity_total		= sales_cycle_offer_total($qtr_start_date_show,$qtr_end_date_show,'4', $hot_offer_search,$acc_manager,$offer_type,$product_category,$cust_segment_search,$datevalid_to,$datevalid_from,$follow_up_datevalid_to);
#sales cycle ends
/******************************************************************************************************************************************************************************************************/

$sql_offer_paging="SELECT 
    
        'time_lead_added', t3.time_lead_added,
		'days_since_offer', DATEDIFF(t1.time_ordered, t3.time_lead_added),
        'app_cat_id', t3.app_cat_id
FROM 
    tbl_order AS t1
INNER JOIN 
    tbl_lead AS t3 ON t1.lead_id = t3.id
LEFT JOIN 
    tbl_comp ON tbl_comp.id = t1.customers_id
LEFT JOIN 
    tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN 
    tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment  
LEFT JOIN 
    tbl_application ON tbl_application.application_id = t3.app_cat_id  
LEFT JOIN 
    tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = t1.edited_enq_id

WHERE 1=1
   $searchRecord
GROUP BY 
 t1.orders_id
ORDER BY 
    $order_by $order ";

//echo "<br><br>".$sql_lead = "Select * FROM tbl_lead where  tbl_lead.deleteflag='active' $searchRecord GROUP BY tbl_lead.id ORDER BY $order_by  $order  LIMIT $from, $max_results"; //exit;
$result_offer_paging 		=  DB::select(($sql_offer_paging));					
$offer_num_rows				= count($result_offer_paging); 	  
$max_estimated_value_offer	= max_estimated_value_offer();
 
//return response()->json(['profile' => json_decode($result_offer)]);

	return response()->json([ 
			'offer_data' => $result_offer,
			'num_rows_count' => $offer_num_rows,
			'max_estimated_value_offer'=>$max_estimated_value_offer,
		"hot_offer_total"=>$hot_offer_total,
		"offer_total"=>$offer_total,
		"opportunity_total"=>$opportunity_total	
		]);
		
}



 public function offers_listing(Request $request)
    {
      
//	  echo Hash::make('Asian@#23456789');        
  //     exit;
//$AdminLoginID_SET = Auth::user()->id;
	$acc_manager        		= $request->acc_manager;
	$month_search  				= $request->month_search;
	$OrderNo  					= $request->order_no;
	$enq_id  					= $request->enq_id;
	$comp_name_search	  		= $request->comp_name_search;
	$state_search  				= $request->state_search;
	$app_cat_id_search  		= $request->product_category;
	$cust_segment_search  		= $request->cust_segment;
	$datevalid_from 			= $request->date_from;
	$datevalid_to				= $request->date_to;
	$follow_up_datevalid_to		= $request->follow_up_datevalid_to;
	$mobile_search				= $request->mobile_search;
	$hot_offer_search			= $request->hot_offer;	
	$orders_status				= $request->orders_status;
//	$payment_method				= $request->payment_method;
	$customers_name				= $request->customers_name;
	$customers_contact_no 		= $request->customers_contact_no;
	$pro_name 		  			= $request->pro_name;
	$sort_by	 				= $request->sort_by;
	$offer_probability			= $request->offer_probability;	
	$offer_type 				= $request->offer_type;	
 	$followup_offer_filter		= $request->followup_offer_filter;	
	$search_by					= $request->search_by;	
	$pro_name					= $request->pro_name;	
	$min_value					= $request->min_value;
	$max_value					= $request->max_value;		
	
	
if($followup_offer_filter=='' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" ";
	}
else if($followup_offer_filter=='1' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  >  CURDATE()";
	}

else if($followup_offer_filter=='2' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  <  CURDATE()";
	}
else
{
		$followup_offer_filter_cond=" ";
}

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}
	
		
if($sort_by=='date_asc')
{
	$order_by="date_ordered";
	$order="asc";
}

else
{
		$order_by="date_ordered";
	$order="desc";
}
if($sort_by=='date_desc')
{
		$order_by="date_ordered";
		$order="desc";
}

if($sort_by=='amt_desc')
{
		$order_by="total_order_cost";
		$order="asc";
}

if($sort_by=='amt_asc')
{
		$order_by="total_order_cost";
		$order="desc";
}


if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}
else
{
	$order_no_search="";
}


if($enq_id!='' )
	{
	//$orders_status='Pending';
	$enq_id_search=" and  edited_enq_id = '$enq_id'";
	}
else
{
	$enq_id_search="";
}

 

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}

if($app_cat_id_search!='' && $app_cat_id_search!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search_search=" and t3.app_cat_id='$app_cat_id_search'";
	}
	else
	{
		$app_cat_id_search_search=" ";
	}

if($cust_segment_search!='' && $cust_segment_search!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search_search=" and t3.cust_segment='$cust_segment_search'";
	}
else
{
	$cust_segment_search_search="";
}
 
 
if($month_search!='' && $month_search!='0')
	{
//$orders_status='Pending';
$month_search_search="and ensure_sale_month='$month_search'";
	}
	else
	{
$month_search_search=" ";
	}


if($comp_name_search!='' && $comp_name_search!='0')
	{
	//$orders_status='Pending';
//	$comp_name_search_search="and  t1.customers_id = '$comp_name_search'";
	$CateParent_search="and  t1.shipping_company LIKE '%$comp_name_search%'";
	}
	else
	{
		$CateParent_search ="";
	}

if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}

if($acc_manager!='' && $acc_manager!='0')
	{
	//$orders_status='Pending';
	$acc_manager_search="and order_by IN ($acc_manager)";
	}
	else
	{
		$acc_manager_search="";
	}

if($offer_probability!='0' && $offer_probability!='')
	{
	//$orders_status='Pending';
//echo 	$offer_probability_search_rec="and offer_probability='$offer_probability'";
$offer_probability_search_rec=" and offer_probability IN ($offer_probability)";	//view offer 
	}
else
{
$offer_probability_search_rec="  ";	//view offer 
}


if($offer_type!='' && $offer_type!='0') //default product
{
	$offer_type_search=" and t1.offer_type='$offer_type'";	

}
else
{
$offer_type_search=" and t1.offer_type='product'";	
}



	if($orders_status!='')
	{
	//$orders_status='Pending';
	$orders_status_search=" and orders_status='$orders_status'";
	}
	else
	{
	$orders_status_search=" ";		
	}

//c name
	if($customers_name!='')
	{
	//$orders_status='Pending';
	$customers_name_search=" and billing_company like '%$customers_name%'";
	}
	else
	{
			$customers_name_search=" ";
	}
//c NO
	if($customers_contact_no!='')
	{
	//$orders_status='Pending';
	$customers_contact_no_search=" and customers_contact_no='$customers_contact_no'";
	}
	else
	{
	$customers_contact_no_search=" ";		
	}
//Pro Name
	if($pro_name!='')
	{
	//$orders_status='Pending';
	$pro_name_search=" and t2.pro_name like '%$pro_name%'";
	}
	else
	{
	$pro_name_search=" ";		
	}
//$payment_method_search


if($datevalid_from!='' && $datevalid_to!='')
	{
		
$date_range_search=" AND (date( t1.date_ordered ) BETWEEN '$datevalid_from' AND '$datevalid_to')";
	}

else
{
	$date_range_search ="";
}
if($follow_up_datevalid_to!='')
	{
		
$follow_up_datevalid_to_search=" AND (date( t1.follow_up_date ) <= '$follow_up_datevalid_to' )";
	}
	else
	{
$follow_up_datevalid_to_search="";
	}

if($mobile_search!='')
	{
		
$mobile_search_search=" AND customers_contact_no = '$mobile_search' ";
	}
else
{
	$mobile_search_search="";
}

if($hot_offer_search!='')
	{
		
$hot_offer_search_search=" AND hot_offer = '$hot_offer_search' ";
	}
else
{
	$hot_offer_search_search="  ";
}


	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword= " AND t1.customers_name like '%".$search_by."%' OR t1.customers_email like '%".$search_by."%' OR t1.customers_contact_no like '%".$search_by."%' OR t1.shipping_company like '%".$search_by."%'   OR t2.pro_name like '%".$search_by."%'  ";
	}

else
{
$search_by_keyword="";
}


if ($min_value != '' && $max_value != '')
{
 
    $estimated_value_search = " and  t1.total_order_cost_new BETWEEN $min_value AND $max_value ";
}
else
{
	   $estimated_value_search = " ";
}


$searchRecord 	= " $orders_status_search $month_search_search $customers_name_search $app_cat_id_search_search  $customers_contact_no_search $pro_name_search   $acc_manager_search $offer_probability_search_rec $order_no_search $enq_id_search $pro_name_search $CateParent_search $date_range_search $follow_up_datevalid_to_search $mobile_search_search $offer_type_search $hot_offer_search_search  $cust_segment_search_search $followup_offer_filter_cond $search_by_keyword $estimated_value_search "; 
	if(strlen(trim($order))<=0)
	{
	$order 	 	 = 'desc';
	}
	if(strlen(trim($order_by))<=0)
	{
	$order_by 	 = 'orders_id';
	}	
	
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
	
	$from = (($page * $max_results) - $max_results);
 
/*		if($datevalid_from!='')
		{
			$fromDate 	  = $s->getDateformate($datevalid_from,'mdy','ymd','-');
			$datevalid_from = "";
		}
		else
		{
			$fromDate = '';
		}
		if($datevalid_to!= '')
		{
			$toDate	  	  = $s->getDateformate($datevalid_to,'mdy','ymd','-');
			$datevalid_to = "";
		}
		else
		{
			$toDate	= "";
		}
 
*/
 /* $sql_offer= "SELECT 
    JSON_OBJECT(
        'company_full_name', CASE 
                                WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
                                THEN CONCAT(tbl_comp.comp_name, '', tbl_company_extn.company_extn_name)
                                ELSE tbl_comp.comp_name
                            END,
        'time_lead_added', t3.time_lead_added,
		'days_since_offer', DATEDIFF(t1.time_ordered, t3.time_lead_added),
        'app_cat_id', t3.app_cat_id,
        'comp_person_id', t3.comp_person_id,
		'Price_type', t1.Price_type,
		'ref_source', t3.ref_source,
        'cust_segment', t3.cust_segment,
        'customers_name', t1.customers_name,
        'customers_email', t1.customers_email,
		'shipping_address', t1.shipping_street_address,
        'shipping_country', t1.shipping_country_name,
		'shipping_zip_code', t1.shipping_zip_code,		
        'shipping_state', t1.shipping_state,
		'shipping_city', t1.shipping_city,
		'country_name', tbl_country.country_name, 
    	'state_name', tbl_zones.zone_name, 
    	'city_name', all_cities.city_name,
    	'enq_remark_edited', tbl_web_enq_edit.enq_remark_edited,
		'shipping_company', t1.shipping_company,
		'billing_company', t1.billing_company,		
        'hot_offer', t1.hot_offer,
        'edited_enq_id', t1.edited_enq_id,
		'enq_id', tbl_web_enq_edit.ID,
		'master_enq_id', tbl_web_enq_edit.enq_id,
        'total_order_cost_new', t1.total_order_cost_new,
        'orders_id', t1.orders_id,
		'offer_type', t1.offer_type,
        'customers_id', t1.customers_id,
        'offercode', t1.offercode,
        'offer_currency', t1.offer_currency,
        'ensure_sale_month', t1.ensure_sale_month,
        'follow_up_date', t1.follow_up_date,
		'freight_amount', t1.freight_amount,
        'customers_contact_no', t1.customers_contact_no,
        'date_ordered', t1.date_ordered,
        'time_ordered', t1.time_ordered,
        'orders_status', t1.orders_status,
        'total_order_cost', t1.total_order_cost,
		'offer_warranty', t1.offer_warranty,
		'delivery_terms', t1.delivery_day,
		'offer_validity', t1.offer_validity,
		'show_discount', t1.show_discount,
		'dead_duck', tbl_web_enq_edit.dead_duck,
		
		'subject', t1.offer_subject,
		'order_in_favor_of', t1.order_in_favor_of,		
		'payment_terms', t1.payment_terms,		
		'offer_probability', t1.offer_probability,		
        'order_by', t1.order_by,
        'lead_id', t1.lead_id,
        'offer_probability', t1.offer_probability,
        'application_name', tbl_application.application_name,
        'cust_segment_name', tbl_cust_segment.cust_segment_name,
        'enq_source_name', tbl_enq_source.enq_source_name,
        'proforma_invoice_id', tpi.pi_id,
		'proforma_invoice_send_for_approval', tpi.save_send,
        'proforma_invoice_status', tpi.pi_status,				
        'admin_name', CONCAT(tbl_admin.admin_fname, ' ', tbl_admin.admin_lname)
) AS offer_data
FROM tbl_order AS t1
INNER JOIN tbl_order_product AS t2 ON t1.orders_id = t2.order_id
INNER JOIN tbl_lead AS t3 ON t1.lead_id = t3.id
LEFT JOIN tbl_comp ON tbl_comp.id = t1.customers_id
LEFT JOIN tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment
LEFT JOIN tbl_application ON tbl_application.application_id = t3.app_cat_id
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description = t3.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id = t1.order_by
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.ID = t1.edited_enq_id
LEFT JOIN tbl_country ON tbl_country.country_id = t1.shipping_country_name
LEFT JOIN tbl_zones ON tbl_zones.zone_id = t1.shipping_state
LEFT JOIN all_cities ON all_cities.city_id = t1.shipping_city
LEFT JOIN tbl_performa_invoice AS tpi ON tpi.O_Id = t1.orders_id
LEFT JOIN events AS ev ON ev.lead_type = t1.orders_id 	
WHERE 1=1
   $searchRecord
GROUP BY 
    t1.orders_id, t1.customers_name, t1.customers_email, t1.shipping_street_address, 
    t1.shipping_country_name, t1.shipping_zip_code, t1.shipping_state, t1.shipping_city, 
    t1.shipping_company, t1.billing_company, t1.hot_offer, t1.edited_enq_id, 
    t1.total_order_cost_new, t1.offer_type, t1.customers_id, t1.Price_type, 
    t1.offercode, t1.offer_currency, t1.ensure_sale_month, t1.follow_up_date, 
    t1.freight_amount, t1.customers_contact_no, t1.date_ordered, t1.time_ordered, 
    t1.orders_status, t1.total_order_cost, t1.offer_warranty, t1.delivery_day, 
    t1.offer_validity, t1.show_discount, t1.offer_subject, t1.order_in_favor_of, 
    t1.payment_terms, t1.offer_probability, t1.order_by, t1.lead_id, 
    t3.time_lead_added, t3.app_cat_id, t3.comp_person_id, t3.ref_source, t3.cust_segment
ORDER BY
    $order_by $order
LIMIT $from, $max_results"; //exit;

*/



$sql_offer = DB::table('tbl_order as t1')
    ->join('tbl_order_product as t2', 't1.orders_id', '=', 't2.order_id')
    ->join('tbl_lead as t3', 't1.lead_id', '=', 't3.id')
    ->leftJoin('tbl_comp', 'tbl_comp.id', '=', 't1.customers_id')
    ->leftJoin('tbl_company_extn', 'tbl_company_extn.company_extn_id', '=', 'tbl_comp.co_extn_id')
    ->leftJoin('tbl_cust_segment', 'tbl_cust_segment.cust_segment_id', '=', 't3.cust_segment')
    ->leftJoin('tbl_application', 'tbl_application.application_id', '=', 't3.app_cat_id')
    ->leftJoin('tbl_enq_source', 'tbl_enq_source.enq_source_description', '=', 't3.ref_source')
    ->leftJoin('tbl_admin', 'tbl_admin.admin_id', '=', 't1.order_by')
    ->leftJoin('tbl_web_enq_edit', 'tbl_web_enq_edit.ID', '=', 't1.edited_enq_id')
    ->leftJoin('tbl_country', 'tbl_country.country_id', '=', 't1.shipping_country_name')
    ->leftJoin('tbl_zones', 'tbl_zones.zone_id', '=', 't1.shipping_state')
    ->leftJoin('all_cities', 'all_cities.city_id', '=', 't1.shipping_city')
    ->leftJoin('tbl_performa_invoice as tpi', 'tpi.O_Id', '=', 't1.orders_id')
    ->leftJoin('events as ev', 'ev.lead_type', '=', 't1.orders_id')
    ->whereIn('t1.order_by', [99])
    ->whereIn('t1.offer_probability', [3])
    ->where('t1.offer_type', 'product')
    ->groupBy([
        't1.orders_id', 't1.customers_name', 't1.customers_email', 't1.shipping_street_address', 
        't1.shipping_country_name', 't1.shipping_zip_code', 't1.shipping_state', 't1.shipping_city', 
        't1.shipping_company', 't1.billing_company', 't1.hot_offer', 't1.edited_enq_id', 
        't1.total_order_cost_new', 't1.offer_type', 't1.customers_id', 't1.Price_type', 
        't1.offercode', 't1.offer_currency', 't1.ensure_sale_month', 't1.follow_up_date', 
        't1.freight_amount', 't1.customers_contact_no', 't1.date_ordered', 't1.time_ordered', 
        't1.orders_status', 't1.total_order_cost', 't1.offer_warranty', 't1.delivery_day', 
        't1.offer_validity', 't1.show_discount', 't1.offer_subject', 't1.order_in_favor_of', 
        't1.payment_terms', 't1.offer_probability', 't1.order_by', 't1.lead_id', 
        't3.time_lead_added', 't3.app_cat_id', 't3.comp_person_id', 't3.ref_source', 
        't3.cust_segment', 'tbl_country.country_name', 'tbl_zones.zone_name', 'all_cities.city_name', 
        'tbl_application.application_name', 'tbl_cust_segment.cust_segment_name', 'tbl_enq_source.enq_source_name',
        'tpi.pi_id', 'tpi.save_send', 'tpi.pi_status', 'tbl_admin.admin_fname', 'tbl_admin.admin_lname'
    ])
    ->orderByDesc('t1.date_ordered')
    ->limit($max_results)
    ->get();


$tasks_details_array		= 0;
$product_items_details		= 0;
$offers = $sql_offer->map(function ($row) {
//	 echo "eee---".$row->orders_id; 
	
	  $tasks_details_array		= getTaskList($row->orders_id);
	  $product_items_details 	= product_name_generated_with_quantity_json_tbl_order_product_listing_new_without_json_stringfy($row->orders_id);
	
    return [
        'company_full_name' => in_array($row->company_extn_id, [5, 6]) 
            ? $row->comp_name 
            : $row->comp_name . ' ' . $row->company_extn_name,
        'time_lead_added' => $row->time_lead_added,
        'days_since_offer' => \Carbon\Carbon::parse($row->time_ordered)->diffInDays($row->time_lead_added),
        'app_cat_id' => $row->app_cat_id,
        'comp_person_id' => $row->comp_person_id,
        'price_type' => $row->Price_type,
        'ref_source' => $row->ref_source,
        'cust_segment' => $row->cust_segment,
        'customers_name' => $row->customers_name,
        'customers_email' => $row->customers_email,
        'shipping_address' => $row->shipping_street_address,
        'shipping_country' => $row->shipping_country_name,
        'shipping_zip_code' => $row->shipping_zip_code,
        'shipping_state' => $row->shipping_state,
        'shipping_city' => $row->shipping_city,
        'country_name' => $row->country_name,
        'state_name' => $row->zone_name,
        'city_name' => $row->city_name,
        'enq_remark_edited' => $row->enq_remark_edited,
        'shipping_company' => $row->shipping_company,
        'billing_company' => $row->billing_company,
        'hot_offer' => $row->hot_offer,
        'edited_enq_id' => $row->edited_enq_id,
        'enq_id' => $row->ID,
        'master_enq_id' => $row->enq_id,
        'total_order_cost_new' => $row->total_order_cost_new,
        'orders_id' => $row->orders_id,
        'offer_type' => $row->offer_type,
        'customers_id' => $row->customers_id,
        'offercode' => $row->offercode,
        'offer_currency' => $row->offer_currency,
        'ensure_sale_month' => $row->ensure_sale_month,
        'follow_up_date' => $row->follow_up_date,
        'freight_amount' => $row->freight_amount,
        'customers_contact_no' => $row->customers_contact_no,
        'date_ordered' => $row->date_ordered,
        'time_ordered' => $row->time_ordered,
        'orders_status' => $row->orders_status,
        'total_order_cost' => $row->total_order_cost,
        'offer_warranty' => $row->offer_warranty,
        'delivery_terms' => $row->delivery_day,
        'offer_validity' => $row->offer_validity,
        'show_discount' => $row->show_discount,
        'dead_duck' => $row->dead_duck,
        'subject' => $row->offer_subject,
        'order_in_favor_of' => $row->order_in_favor_of,
        'payment_terms' => $row->payment_terms,
        'offer_probability' => $row->offer_probability,
        'order_by' => $row->order_by,
        'lead_id' => $row->lead_id,
        'application_name' => $row->application_name,
        'cust_segment_name' => $row->cust_segment_name,
        'enq_source_name' => $row->enq_source_name,
        'proforma_invoice_id' => $row->pi_id,
        'proforma_invoice_send_for_approvarowl' => $row->save_send,
        'proforma_invoice_status' => $row->pi_status,
        'admin_name' => $row->admin_fname . ' ' . $row->admin_lname,
		"date_ordered"=>$row->date_ordered,
		"app_cat_id"=>$row->app_cat_id,
	
 

	"comp_person_id"=>$row->comp_person_id,
	"product_items_details"=>$product_items_details,
	"offer_task_details"=>$tasks_details_array 
    ];
});

// Return JSON response
// return response()->json($offers);



$offer_data 				= json_decode($offers);
//$result_offer 					= DB::select(($sql_offer));	
$data							= $offers;





//print_r($order_ids);



	
$sql_offer_paging="SELECT 
    
        'company_full_name', CASE 
                                WHEN tbl_company_extn.company_extn_id NOT IN (5, 6) 
                                THEN CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name)
                                ELSE tbl_comp.comp_name
                            END,
		'days_since_offer', DATEDIFF(t1.time_ordered, t3.time_lead_added),
        'customers_name', t1.customers_name,
        'proforma_invoice_status', tpi.pi_status,				
        'admin_name', CONCAT(tbl_admin.admin_fname, ' ', tbl_admin.admin_lname)

FROM tbl_order AS t1
INNER JOIN tbl_order_product AS t2 ON t1.orders_id = t2.order_id
INNER JOIN tbl_lead AS t3 ON t1.lead_id = t3.id
LEFT JOIN tbl_comp ON tbl_comp.id = t1.customers_id
LEFT JOIN tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment
LEFT JOIN tbl_application ON tbl_application.application_id = t3.app_cat_id
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description = t3.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id = t1.order_by
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.ID = t1.edited_enq_id
LEFT JOIN tbl_country ON tbl_country.country_id = t1.shipping_country_name
LEFT JOIN tbl_zones ON tbl_zones.zone_id = t1.shipping_state
LEFT JOIN all_cities ON all_cities.city_id = t1.shipping_city
LEFT JOIN tbl_performa_invoice AS tpi ON tpi.O_Id = t1.orders_id
LEFT JOIN events AS ev ON ev.lead_type = t1.orders_id 	
WHERE 1=1
   $searchRecord
GROUP BY 
    t1.orders_id, t1.customers_name, t1.customers_email, t1.shipping_street_address, 
    t1.shipping_country_name, t1.shipping_zip_code, t1.shipping_state, t1.shipping_city, 
    t1.shipping_company, t1.billing_company, t1.hot_offer, t1.edited_enq_id, 
    t1.total_order_cost_new, t1.offer_type, t1.customers_id, t1.Price_type, 
    t1.offercode, t1.offer_currency, t1.ensure_sale_month, t1.follow_up_date, 
    t1.freight_amount, t1.customers_contact_no, t1.date_ordered, t1.time_ordered, 
    t1.orders_status, t1.total_order_cost, t1.offer_warranty, t1.delivery_day, 
    t1.offer_validity, t1.show_discount, t1.offer_subject, t1.order_in_favor_of, 
    t1.payment_terms, t1.offer_probability, t1.order_by, t1.lead_id, 
    t3.time_lead_added, t3.app_cat_id, t3.comp_person_id, t3.ref_source, t3.cust_segment
ORDER BY
    $order_by $order";
$result_offer_paging 		=  DB::select(($sql_offer_paging));					
$offer_num_rows				= count($result_offer_paging); 	  
$max_estimated_value_offer	= max_estimated_value_offer();

	return response()->json([ 
		'offer_data' => $offer_data,
		'num_rows_count' => $offer_num_rows,
		'tasks_details_array' => $tasks_details_array,
		'product_items_details' => $product_items_details,
		
		'max_estimated_value_offer'=>$max_estimated_value_offer		

	]);
		
}



 public function  offers_listing_export_to_excel(Request $request)
    {
       
//$AdminLoginID_SET = Auth::user()->id;
	$acc_manager        		= $request->acc_manager;
	$month_search  				= $request->month_search;
	$OrderNo  					= $request->order_no;
	$comp_name_search	  		= $request->comp_name_search;
	$state_search  				= $request->state_search;
	$app_cat_id_search  		= $request->product_category;
	$cust_segment_search  		= $request->cust_segment;
	$datevalid_from 			= $request->datevalid_from;
	$datevalid_to				= $request->datevalid_to;
	$follow_up_datevalid_to		= $request->follow_up_datevalid_to;
	$mobile_search				= $request->mobile_search;
	$hot_offer_search			= $request->hot_offer;	
	$orders_status				= $request->orders_status;
//	$payment_method				= $request->payment_method;
	$customers_name				= $request->customers_name;
	$customers_contact_no 		= $request->customers_contact_no;
	$pro_name 		  			= $request->pro_name;
	$sort_by	 				= $request->sort_by;
	$offer_probability			= $request->offer_probability;	
	$offer_type 				= $request->offer_type;	
 	$followup_offer_filter		= $request->followup_offer_filter;	
	$search_by					= $request->search_by;	
	$pro_name					= $request->pro_name;	
	$min_value					= $request->min_value;
	$max_value					= $request->max_value;		
	
	
if($followup_offer_filter=='' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" ";
	}
else if($followup_offer_filter=='1' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  >  CURDATE()";
	}

else if($followup_offer_filter=='2' )
	{
	//$orders_status='Pending';
	$followup_offer_filter_cond=" and  t1.follow_up_date  <  CURDATE()";
	}
else
{
		$followup_offer_filter_cond=" ";
}

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}
	
		
if($sort_by=='date_asc')
{
	$order_by="date_ordered";
	$order="asc";
}

else
{
		$order_by="date_ordered";
	$order="desc";
}
if($sort_by=='date_desc')
{
		$order_by="date_ordered";
		$order="desc";
}

if($sort_by=='amt_desc')
{
		$order_by="total_order_cost";
		$order="asc";
}

if($sort_by=='amt_asc')
{
		$order_by="total_order_cost";
		$order="desc";
}


if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}
else
{
	$order_no_search="";
}

 

if($state_search!='' && $state_search!='0')
	{
	//$orders_status='Pending';
	$state_search_search=" and t1.shipping_state='$state_search'";
	}

if($app_cat_id_search!='' && $app_cat_id_search!='0')
	{
	//$orders_status='Pending';
	$app_cat_id_search_search=" and t3.app_cat_id='$app_cat_id_search'";
	}

if($cust_segment_search!='' && $cust_segment_search!='0')
	{
	//$orders_status='Pending';
	$cust_segment_search_search=" and t3.cust_segment='$cust_segment_search'";
	}
else
{
	$cust_segment_search_search="";
}
 
 
if($month_search!='' && $month_search!='0')
	{
	//$orders_status='Pending';
	$month_search_search="and ensure_sale_month='$month_search'";
	}
	else
	{
	$month_search_search=" ";
	}


if($comp_name_search!='' && $comp_name_search!='0')
	{
	//$orders_status='Pending';
//	$comp_name_search_search="and  t1.customers_id = '$comp_name_search'";
	$CateParent_search="and  t1.shipping_company LIKE '%$comp_name_search%'";
	}
	else
	{
		$CateParent_search ="";
	}

if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  orders_id = '$OrderNo'";
	}

if($acc_manager!='' && $acc_manager!='0')
	{
	//$orders_status='Pending';
	$acc_manager_search="and order_by='$acc_manager'";
	}
	else
	{
		$acc_manager_search="";
	}

if($offer_probability!='0' && $offer_probability!='')
	{
	//$orders_status='Pending';
//echo 	$offer_probability_search_rec="and offer_probability='$offer_probability'";
$offer_probability_search_rec=" and offer_probability IN ($offer_probability)";	//view offer 
	}
else
{
$offer_probability_search_rec="  ";	//view offer 
}


if($offer_type!='' && $offer_type!='0') //default product
{
	$offer_type_search=" and t1.offer_type='$offer_type'";	

}
else
{
$offer_type_search=" and t1.offer_type='product'";	
}

	if($orders_status!='')
	{
	//$orders_status='Pending';
	$orders_status_search=" and orders_status='$orders_status'";
	}
	else
	{
	$orders_status_search=" ";		
	}

//c name
	if($customers_name!='')
	{
	//$orders_status='Pending';
	$customers_name_search=" and billing_company like '%$customers_name%'";
	}
	else
	{
			$customers_name_search=" ";
	}
//c NO
	if($customers_contact_no!='')
	{
	//$orders_status='Pending';
	$customers_contact_no_search=" and customers_contact_no='$customers_contact_no'";
	}
	else
	{
	$customers_contact_no_search=" ";		
	}
//Pro Name
	if($pro_name!='')
	{
	//$orders_status='Pending';
	$pro_name_search=" and t2.pro_name like '%$pro_name%'";
	}
	else
	{
	$pro_name_search=" ";		
	}
//$payment_method_search


if($datevalid_from!='' && $datevalid_to!='')
	{
		
$date_range_search=" AND (date( t1.date_ordered ) BETWEEN '$datevalid_from' AND '$datevalid_to')";
	}

else
{
	$date_range_search ="";
}
if($follow_up_datevalid_to!='')
	{
		
$follow_up_datevalid_to_search=" AND (date( t1.follow_up_date ) <= '$follow_up_datevalid_to' )";
	}
	else
	{
$follow_up_datevalid_to_search="";
	}

if($mobile_search!='')
	{
		
$mobile_search_search=" AND customers_contact_no = '$mobile_search' ";
	}
else
{
	$mobile_search_search="";
}

if($hot_offer_search!='')
	{
		
$hot_offer_search_search=" AND hot_offer = '$hot_offer_search' ";
	}
else
{
	$hot_offer_search_search="  ";
}


	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword= " AND t1.customers_name like '%".$search_by."%' OR t1.customers_email like '%".$search_by."%' OR t1.customers_contact_no like '%".$search_by."%' OR t1.shipping_company like '%".$search_by."%'   OR t2.pro_name like '%".$search_by."%'  ";
	}

else
{
$search_by_keyword="";
}


if ($min_value != '' && $max_value != '')
{
 
    $estimated_value_search = " and  t1.total_order_cost_new BETWEEN $min_value AND $max_value ";
}
else
{
	   $estimated_value_search = " ";
}


$searchRecord 	= " $orders_status_search $month_search_search $customers_name_search  $customers_contact_no_search $pro_name_search   $acc_manager_search $offer_probability_search_rec $order_no_search $pro_name_search $CateParent_search $date_range_search $follow_up_datevalid_to_search $mobile_search_search $offer_type_search $hot_offer_search_search $app_cat_id_search_search  $cust_segment_search_search $followup_offer_filter_cond $search_by_keyword $estimated_value_search "; 
	if(strlen(trim($order))<=0)
	{
	$order 	 	 = 'desc';
	}
	if(strlen(trim($order_by))<=0)
	{
	$order_by 	 = 'orders_id';
	}	
	
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
	
	$from = (($page * $max_results) - $max_results);
 
		if($datevalid_from!='')
		{
			$fromDate 	  = $s->getDateformate($datevalid_from,'mdy','ymd','-');
			$datevalid_from = "";
		}
		else
		{
			$fromDate = '';
		}
		if($datevalid_to!= '')
		{
			$toDate	  	  = $s->getDateformate($datevalid_to,'mdy','ymd','-');
			$datevalid_to = "";
		}
		else
		{
			$toDate	= "";
		}
 
 

$sql_offer_paging="
SELECT 
t3.time_lead_added,
CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name) as company_full_name,

'days_since_offer', DATEDIFF(t1.time_ordered, t3.time_lead_added),
t3.app_cat_id,
t3.ref_source,
t3.cust_segment,
t1.shipping_state,
t1.shipping_country_name,
t1.shipping_city,
t1.billing_state,
t1.hot_offer,
t1.edited_enq_id,
t1.total_order_cost_new,
t1.orders_id,
t1.customers_id,
t1.Price_type,
t1.offercode,
t1.offer_currency,
t1.ensure_sale_month,
t1.follow_up_date,
t1.customers_contact_no,
t1.date_ordered,
t1.time_ordered,
t1.orders_status,
t1.total_order_cost,
t1.order_by,
t1.lead_id,
t2.pro_id,
t2.pro_name,
'offer_probability', t1.offer_probability,
'application_name', tbl_application.application_name,
'cust_segment_name', tbl_cust_segment.cust_segment_name,
'enq_source_name', tbl_enq_source.enq_source_name,
tbl_stage_master.stage_name,
	tbl_country.country_name, 
    tbl_zones.zone_name, 
    all_cities.city_name


FROM tbl_order AS t1
INNER JOIN tbl_order_product AS t2 ON t1.orders_id = t2.order_id
INNER JOIN tbl_lead AS t3 ON t1.lead_id = t3.id
LEFT JOIN tbl_comp ON tbl_comp.id = t1.customers_id
LEFT JOIN tbl_company_extn ON tbl_company_extn.company_extn_id = tbl_comp.co_extn_id
LEFT JOIN tbl_cust_segment ON tbl_cust_segment.cust_segment_id = t3.cust_segment
LEFT JOIN tbl_application ON tbl_application.application_id = t3.app_cat_id
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description = t3.ref_source
LEFT JOIN tbl_admin ON tbl_admin.admin_id = t1.order_by
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.ID = t1.edited_enq_id
LEFT JOIN tbl_country ON tbl_country.country_id = t1.shipping_country_name
LEFT JOIN tbl_zones ON tbl_zones.zone_id = t1.shipping_state
LEFT JOIN all_cities ON all_cities.city_id = t1.shipping_city
LEFT JOIN tbl_performa_invoice AS tpi ON tpi.O_Id = t1.orders_id
LEFT JOIN events AS ev ON ev.lead_type = t1.orders_id 	
WHERE 1=1
   $searchRecord
GROUP BY 
    t1.orders_id, t1.customers_name, t1.customers_email, t1.shipping_street_address, 
    t1.shipping_country_name, t1.shipping_zip_code, t1.shipping_state, t1.shipping_city, 
    t1.shipping_company, t1.billing_company, t1.hot_offer, t1.edited_enq_id, 
    t1.total_order_cost_new, t1.offer_type, t1.customers_id, t1.Price_type, 
    t1.offercode, t1.offer_currency, t1.ensure_sale_month, t1.follow_up_date, 
    t1.freight_amount, t1.customers_contact_no, t1.date_ordered, t1.time_ordered, 
    t1.orders_status, t1.total_order_cost, t1.offer_warranty, t1.delivery_day, 
    t1.offer_validity, t1.show_discount, t1.offer_subject, t1.order_in_favor_of, 
    t1.payment_terms, t1.offer_probability, t1.order_by, t1.lead_id, 
    t3.time_lead_added, t3.app_cat_id, t3.comp_person_id, t3.ref_source, t3.cust_segment
ORDER BY
    $order_by $order";

//echo "<br><br>".$sql_lead = "Select * FROM tbl_lead where  tbl_lead.deleteflag='active' $searchRecord GROUP BY tbl_lead.id ORDER BY $order_by  $order  LIMIT $from, $max_results"; //exit;
$result_offer_paging 		=  DB::select(($sql_offer_paging));					
$offer_num_rows				= count($result_offer_paging); 	  
$max_estimated_value_offer	= max_estimated_value_offer();
 
//return response()->json(['profile' => json_decode($result_offer)]);

	return response()->json([ 
			'export_offer_data' => $result_offer_paging,
			'num_rows_count' => $offer_num_rows 
		]);
		
}



    public function hot_unhot_offer(Request $request)
    {
       // if($request->opr=='10'){

            $date 	  	= date('Y-m-d');        
            $hot_enquiry	= $request->hot_enquiry;
            $enq_id			= $request->ID;
           
            if($hot_enquiry!='') {            
                $ArrayData['hot_offer'] = $hot_enquiry;
                DB::table('tbl_order')
                    ->where('edited_enq_id', $enq_id)
                    ->update($ArrayData); 

                $ArrayData_enq['hot_enquiry'] = $hot_enquiry;
                $ArrayData_enq['mel_updated_on'] = date("Y-m-d H:i:s");
                DB::table('tbl_web_enq_edit')
                    ->where('ID', $enq_id)
                    ->update($ArrayData_enq);
            }
        
            if($hot_enquiry=='1' || $hot_enquiry=='0'){
                $success="true";
            }else {
                $success="false";
            }           
      //  }

        return response()->json([            
            'success' => $success, 
        ]);
    }

public function offer_status_master()
    {
        $rs_offer_status_master 	= DB::table('tbl_order_status_master')->where('deleteflag', '=', 'active')->orderby('order_status_name','asc')->select('order_status_id','order_status_name')->get();
        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'offer_status_master' => $rs_offer_status_master, 
        ]);
    }



public function create_offer(Request $request)
    {
 $offer_array			= $request->all();

/*echo "<pre>";
print_r($offer_array); */

 $todayDate					= date("Y-m-d H:i:s");	
 $date 	  					= date('Y-m-d');
 $deliveryTerm				= $offer_array["deliveryTerm"]; //exit;
 $paymentTerm				= $offer_array["paymentTerm"];
 $warranty					= $offer_array["warranty"];
 $offerValidity				= $offer_array["offerValidity"];
 $orderFavor				= $offer_array["orderFavor"];
 $offerSubject				= $offer_array["offerSubject"]; 
 $show_discount   			= $offer_array["show_discount"]; 

 //$custID					= $offer_array["customers_id"];     
$freight_amount				= $offer_array["freightCharges"];	
$followUpDate				= $offer_array["followUpDate"];	
$hotOffer					= $offer_array["hotOffer"];	
$freight_amount				= $offer_array["freightCharges"];	
$custID						= $offer_array["leadData"]["comp_name"];	
//$offer_type					= $offer_array["leadData"]["enq_type"];    
//if($offer_array["leadData"]["company_full_name"]=='')
//{
//	$company_full_name			= "N/A";
//}
//else
//{
$company_full_name			= $offer_array["leadData"]["company_full_name"];	
//}
$freight_amount				= $offer_array["freightCharges"];	
$comp_person_id				= $offer_array["leadData"]["comp_person_id"];    
$customer_comments			= $offer_array["leadData"]["customer_comments"];	
$acc_manager				= $offer_array["leadData"]["acc_manager"];    
$lead_id					= $offer_array["leadData"]["lead_id"];	
$enq_id						= $offer_array["leadData"]["enq_id"];    
$ID							= $offer_array["leadData"]["ID"];	
 //  exit; 

/* if($offer_type=='' && $offer_type=='product')
 {
	 $offer_type			= "product";
 }
 else
 {
	  $offer_type			= "service";
 }*/
 
$lead_details				= get_lead_details($lead_id);
$salutation					= salutation_name($lead_details->salutation);     
$lead_fname					= $lead_details->lead_fname;    
$lead_lname					= $lead_details->lead_lname;	
$lead_email					= $lead_details->lead_email;    
$lead_phone					= $lead_details->lead_phone;	
$cust_segment				= $lead_details->cust_segment;    
$ref_source					= $lead_details->ref_source;	
$app_cat_id					= $lead_details->app_cat_id;    
$enq_remark_edited			= $offer_array["leadData"]["enq_remark_edited"];	
$offer_type					= $offer_array["leadData"]["enq_type"];
$lead_contact_address1		= $lead_details->lead_contact_address1;
$lead_contact_state			= $lead_details->lead_contact_state;
$lead_contact_city			= $lead_details->lead_contact_city;
$lead_contact_zip_code		= $lead_details->lead_contact_zip_code;
$lead_contact_country		= $lead_details->lead_contact_country;
$lead_phone					= $lead_details->lead_phone;
$total_order_cost			= $offer_array["total_order_cost"];
$dataOrderArray["order_by"]	= $offer_array["leadData"]["acc_manager"];

$PaymentMode				= "17";


	
	$dataOrderArray["customers_id"] 			= $custID;
	$dataOrderArray["customers_name"]  			= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["customers_email"] 			= $lead_email;
	$dataOrderArray["customers_contact_no"] 	= $lead_phone;
	$dataOrderArray["payment_mode"] 			= $PaymentMode;


	$dataOrderArray["shipping_method_cost"]		= "";//$rowTempOrder->shippingCost; //ship cost added by rumit
	$dataOrderArray["tax_cost"]					= "0";//$rowTempOrder->tax;  //tax cost added by rumit
	$dataOrderArray["additional_disc"]			= "0";//$rowTempOrder->additional_disc;  //tax cost added by rumit
	$dataOrderArray["shipping_method_cost"]		= "0";//$rowTempOrder->shippingCost;
	$dataOrderArray["taxes_perc"]				= "0";//$rowTempOrder->taxes_perc; // tax amt for crm
	$dataOrderArray["discount_perc"]			= "0";//$rowTempOrder->discount_perc;// discount amt for crm
	$dataOrderArray["tax_per_amt"]				= "0";//$rowTempOrder->tax_per_amt; // tax amt for crm
	$dataOrderArray["discount_per_amt"]			= "0";//$rowTempOrder->discount_per_amt;// discount amt for crm
	$dataOrderArray["lead_id"]					= $lead_id;// discount amt for crm exit;
	$dataOrderArray["follow_up_date"]			= $followUpDate;// discount amt for crm exit;

	$dataOrderArray["offer_subject"]			= $offerSubject;
	$dataOrderArray["order_in_favor_of"]		= $orderFavor;
	$dataOrderArray["offer_warranty"]			= $warranty;
	$dataOrderArray["offer_calibration"]		= "1";// $cali $rowTempOrder->offer_calibration;//added on 002-mar-2020
	$dataOrderArray["offer_validity"]			= $offerValidity;//$rowTempOrder->offer_validity;
//	$dataOrderArray["tax_included"]				= $rowTempOrder->taxes_included;
	$dataOrderArray["show_discount"]			= $show_discount;
	$dataOrderArray["payment_terms"]			= $paymentTerm;//$rowTempOrder->payment_terms;
	$dataOrderArray["delivery_day"]				= "";//$rowTempOrder->delivery_day;
	

	$dataOrderArray["coupon_id"]				= "0";//$rowTempOrder->coupon_id;
	$dataOrderArray["offercode"]				= "0";//$rowTempOrder->coupon_type;
	$dataOrderArray["coupon_type"]				= "0";//$rowTempOrder->coupon_type;
	$dataOrderArray["coupon_value"]				= "0.00";//$rowTempOrder->coupon_value;
	$dataOrderArray["coupon_discount"]			= "0.00";//$rowTempOrder->coupon_discount;
	$dataOrderArray["offer_probability"]		= "3"; //default enq/order status after creating offer i.e. 50%
	$dataOrderArray["price_type"]				= $offer_array["productData"][0]["price_list"]; //default enq/order status after creating offer i.e. 50%



	$dataOrderArray["shipping_company"]			= $company_full_name;
	$dataOrderArray["shipping_name"]			= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["shipping_street_address"]	= $lead_contact_address1;
	$dataOrderArray["shipping_city"]			= $lead_contact_city;
	$dataOrderArray["shipping_zip_code"]		= $lead_contact_zip_code;
	$dataOrderArray["shipping_state"]			= $lead_contact_state;
	$dataOrderArray["shipping_country_name"]	= $lead_contact_country;
	$dataOrderArray["shipping_telephone_no"]	= $lead_phone;
	$dataOrderArray["shipping_fax_no"]			= "0";//$rowCustomer->fax_no;
	
/*	$sqlCustomerAddBilling	= "select * from tbl_customer_address where customers_id = '$custID' and address_id = '$rowTempOrder->billing_add_id' and address_type = 'billing'";
	$rsCustomerAddBilling	= mysqli_query($GLOBALS["___mysqli_ston"],$sqlCustomerAddBilling);
	$rowCustomerAddBilling	= mysqli_fetch_object($rsCustomerAddBilling);*/
	
	$dataOrderArray["billing_company"]			= $company_full_name;
	$dataOrderArray["billing_name"]				= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["billing_street_address"] 	= $lead_contact_address1;
	$dataOrderArray["billing_city"]				= $lead_contact_city;
	$dataOrderArray["billing_zip_code"]			= $lead_contact_zip_code;
	$dataOrderArray["billing_state"]			= $lead_contact_state;
	$dataOrderArray["billing_country_name"]		= $lead_contact_country;
	$dataOrderArray["billing_telephone_no"]		= $lead_phone;
	$dataOrderArray["billing_fax_no"]			= "0";//$rowCustomer->fax_no; 
 	$dataOrderArray["total_order_cost"]			= $total_order_cost;//$rowCustomer->fax_no; 
 	$dataOrderArray["total_order_cost_new"]		= $total_order_cost;//$rowCustomer->fax_no; 	
 	$dataOrderArray["freight_amount"]			= $freight_amount;//$rowCustomer->fax_no; 	
	$dataOrderArray["offer_type"]				= $offer_type;
	$dataOrderArray["trackingNo"]				= "0";
	$dataOrderArray["shipComment"]				= "0";
	$dataOrderArray["date_ordered"]				= $todayDate;
	
	if($PaymentMode==5 || $PaymentMode==9  || $PaymentMode==10 || $PaymentMode==11 )
	{
	$dataOrderArray["orders_status"]			= "Failed";
	}
	else
	{
	$dataOrderArray["orders_status"]			= "Pending";	
	}
	 //echo "<pre>";print_r($dataOrderArray); //exit;
	 $ENQ_ID									= $ID;//$this->get_ID_from_lead_id($rowTempOrder->lead_id);
	  $dataOrderArray["edited_enq_id"]			= $ENQ_ID;	 //exit;
//	$result										= $this->insertRecord('tbl_order',$dataOrderArray); //exit;
 
	
	
     "prev:=".$previous_order_id_check		= order_id_from_enq_edit_table($ID);//exit;
	  
	  
	  
	 // print_r($dataOrderArray); exit;
if($previous_order_id_check=='0')
{
	
         "orderid_:".$inserted_order_id 			= DB::table('tbl_order')->insertGetId($dataOrderArray);	// exit;
   	
 //   $inserted_lead_id 						= DB::table('tbl_lead')->insertGetId($fileArrayist);		
	//$fileArrayEnq["order_id"]  			 	= $inserted_order_id; 

//update order id in enquiry table	
	$fileArrayEnq["enq_stage"]					= 3;
	$fileArrayEnq["order_id"]					= $inserted_order_id;
	 DB::table('tbl_web_enq_edit')
            ->where('ID', $ID)
          ->update($fileArrayEnq);

	 
// $lead_array["contact_details"]["country_name"];
// $lead_array["contact_details"]["comp_person_id"];
$ctr										= count($offer_array["productData"]);
//exit;
$pro_category								= $offer_array["leadData"]["app_cat_id"];	
for($i=0; $i<$ctr; $i++)
{
$pro_id										= $offer_array["productData"][$i]["pro_id"];	
//$gst_percentage								= ApplicationTax($pro_category);
$pro_id										= $offer_array["productData"][$i]["pro_id"];	
$price_list									= $offer_array["productData"][$i]["price_list"];	
$proidentry									= $offer_array["productData"][$i]["pro_id_entry"];	
$pro_model									= $offer_array["productData"][$i]["modelNumber"];	
$proPrice									= $offer_array["productData"][$i]["unitPrice"];	
$proFinalPrice								= $offer_array["productData"][$i]["unitPrice"];	
$pro_name									= $offer_array["productData"][$i]["name"];	
$hsn_code									= $offer_array["productData"][$i]["hsn_code"];	
$pro_quantity								= $offer_array["productData"][$i]["quantity"];	
$GST_percentage								= $offer_array["productData"][$i]["taxPercentage"];	
$protaxcost									= $offer_array["productData"][$i]["tax"];	
$service_period_id							= $offer_array["productData"][$i]["service_period_id"];	
$discountValue							  	= $offer_array["productData"][$i]["discountValueInRupees"];	// in percentage
$proDiscount							  	= $offer_array["productData"][$i]["discountValue"];	 // in rupees

$pro_sort									= "0";
if($offer_array["productData"][$i]["service_period"]=='' && $offer_array["productData"][$i]["service_period"]=='0')
{
$service_period								= "1";//$offer_array["productData"][$i]["service_period"];		
$service_period_id							= "0";//$offer_array["productData"][$i]["service_period_id"];	
}
else
{
$service_period								= $offer_array["productData"][$i]["service_period"];	
$service_period_id							= $offer_array["productData"][$i]["service_period_id"];		
}

$additional_disc							= "0";	
	
	
			$dataOrderProducts["order_id"] 		  	  = $inserted_order_id;
			$dataOrderProducts["pro_id"] 		  	  = $pro_id;
			$dataOrderProducts["manufacturers_id"] 	  = "0";//$manufacturers_id;
			$dataOrderProducts["proidentry"]	  	  = $proidentry;
			$dataOrderProducts["additional_disc"]  	  = "0";//$additional_disc;
			$dataOrderProducts["group_id"] 		  	  = "0";//$group_id;
			$dataOrderProducts["qty_attDset_id"]      = "0";//$qty_attDset_id;
			$dataOrderProducts["customers_id"]	  	  = $custID;
			$dataOrderProducts["pro_model"] 	  	  = $pro_model;
			$dataOrderProducts["hsn_code"] 	  	  	  = $hsn_code;
			$dataOrderProducts["pro_name"] 		  	  = $pro_name;
			$dataOrderProducts["pro_price"] 	  	  = $proPrice;
			$dataOrderProducts["pro_final_price"]  	  = $proFinalPrice;// calculate final price - coupon amount 
			$dataOrderProducts["wrap_cost"] 		  = "0.00";//$wrap_cost;
			$dataOrderProducts["Pro_tax"] 		  	  = $protaxcost;
			$dataOrderProducts["GST_percentage"]   	  = $GST_percentage;
			$dataOrderProducts["pro_quantity"] 	      = $pro_quantity;

			$dataOrderProducts["coupon_id"] 	      = $discountValue;// in percentage 
			$dataOrderProducts["pro_coupon_amount"]   = 0.00;
			$dataOrderProducts["pro_text"]   		  = '0';
			$dataOrderProducts["pro_ret_remarks"]     = '0';
			$dataOrderProducts["pro_ret_qty"]   	  = '0';
			$dataOrderProducts["pro_ret_amt"]   	  = '0';
			$dataOrderProducts["barcode"]   		  = '0';
			
			
			//$proDiscount							  = "0";//$discount;
			$dataOrderProducts["pro_discount_amount"] = $proDiscount;
//			$dataOrderProducts["additional_disc"] 	  = $additional_disc;
			
			$dataOrderProducts["proAttribute_Cost"]   = "0";//$rowShopCartProduct->attributesetPrice;
			$dataOrderProducts["order_pro_status"]    = "Pending";	
			$dataOrderProducts["service_period"]      = $service_period;
			$dataOrderProducts["service_period_id"]   = $service_period_id;			 
$lead_products_table						= DB::table('tbl_order_product')->insert($dataOrderProducts);			
	
/* "productCategory". $offer_array["productData"][$i]["productCategory"];
 "product". $offer_array["productData"]["productDetails"][$i]["product"];
"quantity". $lead_array["productData"]["productDetails"][$i]["quantity"];*/


 			$dataOrderProductsdisc["orderid"] 		  	  = $inserted_order_id;
			$dataOrderProductsdisc["proid"] 		  	  = $pro_id;	
 			$dataOrderProductsdisc["discount_amount"] 	  = $discountValue;
			$dataOrderProductsdisc["discount_percent"] 	  = $proDiscount;	
			$dataOrderProductsdisc["show_discount"] 	  = "No";//$pro_id;	

//insert in prowise discount table
$lead_products_table						= DB::table('prowise_discount')->insert($dataOrderProductsdisc);			


}


           $msg = array("msg"=>"true","order_id"=>$inserted_order_id);
}
else
{
           $msg = array("msg"=>"false","order_id"=>$previous_order_id_check,"msg2"=>"Offer already created for this Enquiry/Lead.");
}
      //$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 

        return response()->json([            
        'message' => $msg, 
        ]);  


	}
	

//edit offer
public function edit_offer(Request $request)
    {
 $offer_array			= $request->all();

/*echo "edit offer:<pre> ";
print_r($offer_array); */


 $todayDate					= date("Y-m-d H:i:s");	
 $date 	  					= date('Y-m-d');
 $order_id					= $offer_array["leadData"]["order_id"];  
 $deliveryTerm				= $offer_array["deliveryTerm"];
 $paymentTerm				= $offer_array["paymentTerm"];
 $warranty					= $offer_array["warranty"];
 $offerValidity				= $offer_array["offerValidity"];
 $orderFavor				= $offer_array["orderFavor"];
 $offerSubject				= $offer_array["offerSubject"]; 
 $show_discount   			= $offer_array["show_discount"]; 

 //$custID					= $offer_array["customers_id"];     
$freight_amount				= $offer_array["freightCharges"];	
$followUpDate				= $offer_array["followUpDate"];	
$hotOffer					= $offer_array["hotOffer"];	
$custID						= $offer_array["leadData"]["comp_name"];	
$offer_type					= $offer_array["leadData"]["enq_type"];    
//if($offer_array["leadData"]["company_full_name"]=='')
//{
//	$company_full_name			= "N/A";
//}
//else
//{
$company_full_name			= $offer_array["leadData"]["company_full_name"];	
$comp_person_id				= $offer_array["leadData"]["comp_person_id"];    
$customer_comments			= $offer_array["leadData"]["customer_comments"];	
$acc_manager				= $offer_array["leadData"]["acc_manager"];    
$lead_id					= $offer_array["leadData"]["lead_id"];	
$enq_id						= $offer_array["leadData"]["enq_id"];    
$ID							= $offer_array["leadData"]["ID"];	
   

 if($offer_type=='' && $offer_type=='product')
 {
	 $offer_type			= "product";
 }
 else
 {
	  $offer_type			= "service";
 }
 
$lead_details				= get_lead_details($lead_id);
$salutation					= salutation_name($lead_details->salutation);    
$lead_fname					= $lead_details->lead_fname;    
$lead_lname					= $lead_details->lead_lname;	
$lead_email					= $lead_details->lead_email;    
$lead_phone					= $lead_details->lead_phone;	
$cust_segment				= $lead_details->cust_segment;    
$ref_source					= $lead_details->ref_source;	
$app_cat_id					= $lead_details->app_cat_id;    
$enq_remark_edited			= $offer_array["leadData"]["enq_remark_edited"];	
$offer_type					= $offer_array["leadData"]["enq_type"];
$lead_contact_address1		= $lead_details->lead_contact_address1;
$lead_contact_state			= $lead_details->lead_contact_state;
$lead_contact_city			= $lead_details->lead_contact_city;
$lead_contact_zip_code		= $lead_details->lead_contact_zip_code;
$lead_contact_country		= $lead_details->lead_contact_country;
$lead_phone					= $lead_details->lead_phone;
$total_order_cost			= $offer_array["total_order_cost"];
$dataOrderArray["order_by"]	= $offer_array["leadData"]["acc_manager"];
$PaymentMode				= "17";

/*print_r($lead_details);
exit;*/
	
	$dataOrderArray["customers_id"] 		= $custID;
	$dataOrderArray["customers_name"]  		= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["customers_email"] 		= $lead_email;
	$dataOrderArray["customers_contact_no"] = $lead_phone;
	$dataOrderArray["payment_mode"] 		= $PaymentMode;
	$dataOrderArray["hot_offer"] 			= $hotOffer;	


	$dataOrderArray["shipping_method_cost"]		= "";//$rowTempOrder->shippingCost; //ship cost added by rumit
	$dataOrderArray["tax_cost"]					= "0";//$rowTempOrder->tax;  //tax cost added by rumit
	$dataOrderArray["additional_disc"]			= "0";//$rowTempOrder->additional_disc;  //tax cost added by rumit
	$dataOrderArray["shipping_method_cost"]		= "0";//$rowTempOrder->shippingCost;
	$dataOrderArray["taxes_perc"]				= "0";//$rowTempOrder->taxes_perc; // tax amt for crm
	$dataOrderArray["discount_perc"]			= "0";//$rowTempOrder->discount_perc;// discount amt for crm
	$dataOrderArray["tax_per_amt"]				= "0";//$rowTempOrder->tax_per_amt; // tax amt for crm
	$dataOrderArray["discount_per_amt"]			= "0";//$rowTempOrder->discount_per_amt;// discount amt for crm
	$dataOrderArray["lead_id"]					= $lead_id;// discount amt for crm exit;
	$dataOrderArray["follow_up_date"]			= $followUpDate;// discount amt for crm exit;

	$dataOrderArray["offer_subject"]			= $offerSubject;
	$dataOrderArray["order_in_favor_of"]		= $orderFavor;
	$dataOrderArray["offer_warranty"]			= $warranty;
	$dataOrderArray["offer_calibration"]		= "1";// $cali $rowTempOrder->offer_calibration;//added on 002-mar-2020
	$dataOrderArray["offer_validity"]			= $offerValidity;//$rowTempOrder->offer_validity;
//	$dataOrderArray["tax_included"]				= $rowTempOrder->taxes_included;
	$dataOrderArray["show_discount"]			= $show_discount;
	$dataOrderArray["payment_terms"]			= $paymentTerm;//"17";//$rowTempOrder->payment_terms;
	$dataOrderArray["delivery_day"]				= $deliveryTerm;//$rowTempOrder->delivery_day;
	

	$dataOrderArray["coupon_id"]				= "0";//$rowTempOrder->coupon_id;
	$dataOrderArray["offercode"]				= "0";//$rowTempOrder->coupon_type;
	$dataOrderArray["coupon_type"]				= "0";//$rowTempOrder->coupon_type;
	$dataOrderArray["coupon_value"]				= "0.00";//$rowTempOrder->coupon_value;
	$dataOrderArray["coupon_discount"]			= "0.00";//$rowTempOrder->coupon_discount;
	$dataOrderArray["offer_probability"]		= "3"; //default enq/order status after creating offer i.e. 50%
	$dataOrderArray["price_type"]				= $offer_array["productData"][0]["price_list"]; //default enq/order status after creating offer i.e. 50%



	$dataOrderArray["shipping_company"]			= $company_full_name;
	$dataOrderArray["shipping_name"]			= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["shipping_street_address"]	= $lead_contact_address1;
	$dataOrderArray["shipping_city"]			= $lead_contact_city;
	$dataOrderArray["shipping_zip_code"]		= $lead_contact_zip_code;
	$dataOrderArray["shipping_state"]			= $lead_contact_state;
	$dataOrderArray["shipping_country_name"]	= $lead_contact_country;
	$dataOrderArray["shipping_telephone_no"]	= $lead_phone;
	$dataOrderArray["shipping_fax_no"]			= "0";//$rowCustomer->fax_no;
	
/*	$sqlCustomerAddBilling	= "select * from tbl_customer_address where customers_id = '$custID' and address_id = '$rowTempOrder->billing_add_id' and address_type = 'billing'";
	$rsCustomerAddBilling	= mysqli_query($GLOBALS["___mysqli_ston"],$sqlCustomerAddBilling);
	$rowCustomerAddBilling	= mysqli_fetch_object($rsCustomerAddBilling);*/
	
	$dataOrderArray["billing_company"]			= $company_full_name;
	$dataOrderArray["billing_name"]				= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["billing_street_address"] 	= $lead_contact_address1;
	$dataOrderArray["billing_city"]				= $lead_contact_city;
	$dataOrderArray["billing_zip_code"]			= $lead_contact_zip_code;
	$dataOrderArray["billing_state"]			= $lead_contact_state;
	$dataOrderArray["billing_country_name"]		= $lead_contact_country;
	$dataOrderArray["billing_telephone_no"]		= $lead_phone;
	$dataOrderArray["billing_fax_no"]			= "0";//$rowCustomer->fax_no; 
 	$dataOrderArray["total_order_cost"]			= $total_order_cost;//$rowCustomer->fax_no; 
	$dataOrderArray["total_order_cost_new"]		= $total_order_cost;//$rowCustomer->fax_no; 
	$dataOrderArray["freight_amount"]   	    = $freight_amount;

	$dataOrderArray["trackingNo"]				= "0";
	$dataOrderArray["shipComment"]				= "0";
	//$dataOrderArray["date_ordered"]				= $todayDate;
	
	if($PaymentMode==5 || $PaymentMode==9  || $PaymentMode==10 || $PaymentMode==11 )
	{
	$dataOrderArray["orders_status"]			= "Failed";
	}
	else
	{
	$dataOrderArray["orders_status"]			= "Pending";	
	}
	 //echo "<pre>";print_r($dataOrderArray); //exit;
	 $ENQ_ID									= $ID;//$this->get_ID_from_lead_id($rowTempOrder->lead_id);
	$dataOrderArray["edited_enq_id"]			= $ENQ_ID;	
//	$result										= $this->insertRecord('tbl_order',$dataOrderArray); //exit;
 //update order table
	 DB::table('tbl_order')
            ->where('orders_id', $order_id)
          ->update($dataOrderArray);
	
$ctr										= count($offer_array["productData"]);
$pro_category								= $offer_array["leadData"]["app_cat_id"];	
//delete from prowise discount table
 $result_tbl_pro_wise_discount_delete	= DB::table('prowise_discount')->where('orderid', $order_id)->delete();
for($i=0; $i<$ctr; $i++)
{
$pro_id										= $offer_array["productData"][$i]["pro_id"];	


//$gst_percentage								= ApplicationTax($pro_category);

$order_pros_id								= $offer_array["productData"][$i]["order_pros_id"];	
$pro_id										= $offer_array["productData"][$i]["pro_id"];	
$price_list									= $offer_array["productData"][$i]["price_list"];	
$proidentry									= $offer_array["productData"][$i]["pro_id_entry"];	
$pro_model									= $offer_array["productData"][$i]["modelNumber"];	
$proPrice									= $offer_array["productData"][$i]["unitPrice"];	
$proFinalPrice								= $offer_array["productData"][$i]["unitPrice"];	
$pro_name									= $offer_array["productData"][$i]["name"];	
$hsn_code									= $offer_array["productData"][$i]["hsn_code"];	
$pro_quantity								= $offer_array["productData"][$i]["quantity"];	
$GST_percentage								= $offer_array["productData"][$i]["taxPercentage"];	
$protaxcost									= $offer_array["productData"][$i]["tax"];	
$discountValue							  	= $offer_array["productData"][$i]["discountValueInRupees"];	// in percentage
$proDiscount							  	= $offer_array["productData"][$i]["discountValue"];	 // in rupees
$freight_amount								= $freight_amount;	
$pro_sort									= "0";
if($offer_array["productData"][$i]["service_period"]=='' && $offer_array["productData"][$i]["service_period"]=='0')
{
$service_period								= "1";//$offer_array["productData"][$i]["service_period"];		
$service_period_id							= "0";//$offer_array["productData"][$i]["service_period_id"];
}
else
{
$service_period								= $offer_array["productData"][$i]["service_period"];
$service_period_id							= $offer_array["productData"][$i]["service_period_id"];			
}

 

$additional_disc							= "0";	

	
	
			$dataOrderProducts["order_id"] 		  	  = $order_id;
			$dataOrderProducts["pro_id"] 		  	  = $pro_id;
			$dataOrderProducts["manufacturers_id"] 	  = "0";//$manufacturers_id;
			$dataOrderProducts["proidentry"]	  	  = $proidentry;
			$dataOrderProducts["additional_disc"]  	  = "0";//$additional_disc;
			$dataOrderProducts["group_id"] 		  	  = "0";//$group_id;
			$dataOrderProducts["qty_attDset_id"]      = "0";//$qty_attDset_id;
			$dataOrderProducts["customers_id"]	  	  = $custID;
			$dataOrderProducts["pro_model"] 	  	  = $pro_model;
			$dataOrderProducts["hsn_code"] 	  	  	  = $hsn_code;
			$dataOrderProducts["pro_name"] 		  	  = $pro_name;
			$dataOrderProducts["pro_price"] 	  	  = $proPrice;
			$dataOrderProducts["pro_final_price"]  	  = $proFinalPrice;// calculate final price - coupon amount 
			$dataOrderProducts["wrap_cost"] 		  = "0.00";//$wrap_cost;
			$dataOrderProducts["Pro_tax"] 		  	  = $protaxcost;
			$dataOrderProducts["GST_percentage"]   	  = $GST_percentage;
			$dataOrderProducts["pro_quantity"] 	      = $pro_quantity;
			$dataOrderProducts["coupon_id"] 	      = $discountValue;// in percentage 
			$dataOrderProducts["pro_coupon_amount"]   = 0.00;
			$dataOrderProducts["pro_text"]   		  = '0';
			$dataOrderProducts["pro_ret_remarks"]     = '0';
			$dataOrderProducts["pro_ret_qty"]   	  = '0';
			$dataOrderProducts["pro_ret_amt"]   	  = '0';
			$dataOrderProducts["barcode"]   		  = '0';
			$dataOrderProducts["freight_amount"]   	  = $freight_amount;
			
			
			//$proDiscount							  = "0";//$discount;
			$dataOrderProducts["pro_discount_amount"] = $proDiscount;
//			$dataOrderProducts["additional_disc"] 	  = $additional_disc;
			
			$dataOrderProducts["proAttribute_Cost"]   = "0";//$rowShopCartProduct->attributesetPrice;
			$dataOrderProducts["order_pro_status"]    = "Pending";	
			$dataOrderProducts["service_period"]      = $service_period;
			$dataOrderProducts["service_period_id"]   = $service_period_id;
 
 
 
 			$dataOrderProductsdisc["orderid"] 		  	  = $order_id;
			$dataOrderProductsdisc["proid"] 		  	  = $pro_id;	
 			$dataOrderProductsdisc["discount_amount"] 	  = $discountValue;
			$dataOrderProductsdisc["discount_percent"] 	  = $proDiscount;	
			$dataOrderProductsdisc["show_discount"] 	  = "No";//$pro_id;	

//insert in prowise discount table
$lead_products_table						= DB::table('prowise_discount')->insert($dataOrderProductsdisc);			
 
if($order_pros_id!='' && $order_pros_id!='0')
{
$order_products_table						= DB::table('tbl_order_product')->where('order_pros_id', $order_pros_id)->update($dataOrderProducts);

}
else
{
$order_products_table						= DB::table('tbl_order_product')->insert($dataOrderProducts);			



} 
 
//$order_products_table						= DB::table('tbl_order_product')->insert($dataOrderProducts);			
	
/* "productCategory". $offer_array["productData"][$i]["productCategory"];
 "product". $offer_array["productData"]["productDetails"][$i]["product"];
"quantity". $lead_array["productData"]["productDetails"][$i]["quantity"];*/
}
if($order_products_table)
{
           $msg = array("msg"=>"true","order_id"=>$order_id);
}
else
{
	       $msg = array("msg"=>"true","order_id"=>$order_id);
}
/*}
else
{
           $msg = array("msg"=>"false","order_id"=>0);
}*/
      //$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 

        return response()->json([            
        'message' => $msg, 
        ]);  


	}

   public function supply_order_delivery_terms()
    {
        $rs_delivery_terms 	= DB::table('tbl_supply_order_delivery_terms_master')->where('deleteflag', '=', 'active')->orderby('supply_order_delivery_terms_name','asc')->select('supply_order_delivery_terms_id','supply_order_delivery_terms_name','sort_order')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'supply_order_delivery_terms' => $rs_delivery_terms, 
        ]);
    }
	

   public function mode_master()
    {
        $rs_mode_master 	= DB::table('tbl_mode_master')->where('deleteflag', '=', 'active')->orderby('mode_name','asc')->select('mode_id','mode_name')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'mode_master' => $rs_mode_master, 
        ]);
    }
	
	
   public function supply_order_payment_terms()
    {
        $rs_payment_terms 	= DB::table('tbl_supply_order_payment_terms_master')->where('deleteflag', '=', 'active')->orderby('supply_order_payment_terms_name','asc')->select('supply_order_payment_terms_id','supply_order_payment_terms_name','sort_order')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'supply_order_payment_terms' => $rs_payment_terms, 
        ]);
    }	

   public function warranty_master()
    {
        $rs_warranty_master 	= DB::table('tbl_warranty_master')->where('deleteflag', '=', 'active')->orderby('warranty_name','asc')->select('warranty_id','warranty_name','warranty_description')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'warranty_master' => $rs_warranty_master, 
        ]);
    }			
		
	
   public function task_list(Request $request)
    {
		$role_id            = $request->role_id;
$rs_task_list = DB::table('tbl_tasktype_master')
    ->where('deleteflag', '=', 'active')
    ->where('task_by_role', 'LIKE', "%$role_id%")
    ->orderBy('tasktype_name', 'asc')
    ->select('tasktype_id', 'tasktype_name', 'tasktype_abbrv')
    ->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'task_list' => $rs_task_list, 
        ]);
    }		


  public function enquiry_history(Request $request)
    {
		   $order_id            = $request->order_id;
        $rs_enquiry_history 	= enquiry_history($order_id);

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'enquiry_history' => $rs_enquiry_history, 
        ]);
    }		
	


//edit customer details in offer

//edit lead
  public function edit_offer_customer_details(Request $request)
    {
 $lead_array			= $request->all();
// print_r($lead_array);// exit;

 $lead_id				=$lead_array["lead_id"];
 $orders_id				=$lead_array["orders_id"];
// $company_id			=$lead_array["company_id"];
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
	
	
	
	
///

$lead_details				= get_lead_details($lead_id);

$salutation					= salutation_name($lead_details->salutation);    
$lead_fname					= $lead_details->lead_fname;    
$lead_lname					= $lead_details->lead_lname;	
$lead_email					= $lead_details->lead_email;    
$lead_phone					= $lead_details->lead_phone;	
$cust_segment				= $lead_details->cust_segment;    
$ref_source					= $lead_details->ref_source;	
$app_cat_id					= $lead_details->app_cat_id;    
//$enq_remark_edited			= $offer_array["leadData"]["enq_remark_edited"];	
//$offer_type					= $offer_array["leadData"]["enq_type"];
$lead_contact_address1		= $lead_details->lead_contact_address1;
$lead_contact_state			= $lead_details->lead_contact_state;
$lead_contact_city			= $lead_details->lead_contact_city;
$lead_contact_zip_code		= $lead_details->lead_contact_zip_code;
$lead_contact_country		= $lead_details->lead_contact_country;
$lead_phone					= $lead_details->lead_phone;
$company_full_name			= company_name_return($comp_name); //exit;



	$dataOrderArray["customers_id"] 			= $comp_name;
	$dataOrderArray["customers_name"]  			= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["customers_email"] 			= $lead_email;
	$dataOrderArray["customers_contact_no"] 	= $lead_phone;
//	$dataOrderArray["payment_mode"] 			= $PaymentMode;
	$dataOrderArray["lead_id"]					= $lead_id;// discount amt for crm exit;
	$dataOrderArray["offer_probability"]		= "3"; //default enq/order status after creating offer i.e. 50%
//	$dataOrderArray["price_type"]				= $offer_array["productData"][0]["price_list"]; //default enq/order status after creating offer i.e. 50%



	$dataOrderArray["shipping_company"]			= $company_full_name;
	$dataOrderArray["shipping_name"]			= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["shipping_street_address"]	= $lead_contact_address1;
	$dataOrderArray["shipping_city"]			= $lead_contact_city;
	$dataOrderArray["shipping_zip_code"]		= $lead_contact_zip_code;
	$dataOrderArray["shipping_state"]			= $lead_contact_state;
	$dataOrderArray["shipping_country_name"]	= $lead_contact_country;
	$dataOrderArray["shipping_telephone_no"]	= $lead_phone;
	$dataOrderArray["shipping_fax_no"]			= "0";//$rowCustomer->fax_no;
	
/*	$sqlCustomerAddBilling	= "select * from tbl_customer_address where customers_id = '$custID' and address_id = '$rowTempOrder->billing_add_id' and address_type = 'billing'";
	$rsCustomerAddBilling	= mysqli_query($GLOBALS["___mysqli_ston"],$sqlCustomerAddBilling);
	$rowCustomerAddBilling	= mysqli_fetch_object($rsCustomerAddBilling);*/
	
	$dataOrderArray["billing_company"]			= $company_full_name;
	$dataOrderArray["billing_name"]				= $salutation." ".ucfirst($lead_fname)." ".ucfirst($lead_lname);
	$dataOrderArray["billing_street_address"] 	= $lead_contact_address1;
	$dataOrderArray["billing_city"]				= $lead_contact_city;
	$dataOrderArray["billing_zip_code"]			= $lead_contact_zip_code;
	$dataOrderArray["billing_state"]			= $lead_contact_state;
	$dataOrderArray["billing_country_name"]		= $lead_contact_country;
	$dataOrderArray["billing_telephone_no"]		= $lead_phone;
	$dataOrderArray["billing_fax_no"]			= "0";//$rowCustomer->fax_no; 
// 	$dataOrderArray["total_order_cost"]			= $total_order_cost;//$rowCustomer->fax_no; 


//	$dataOrderArray["trackingNo"]				= "0";
//	//$dataOrderArray["shipComment"]				= "0";
//	$dataOrderArray["date_ordered"]				= $todayDate;
/*	
	if($PaymentMode==5 || $PaymentMode==9  || $PaymentMode==10 || $PaymentMode==11 )
	{
	$dataOrderArray["orders_status"]			= "Failed";
	}
	else
	{
	$dataOrderArray["orders_status"]			= "Pending";	
	}*/
	 //echo "<pre>";print_r($dataOrderArray); //exit;
	// $ENQ_ID									= $ID;//$this->get_ID_from_lead_id($rowTempOrder->lead_id);
//	$dataOrderArray["edited_enq_id"]			= $ENQ_ID;	
	$result			= DB::table('tbl_order')
            ->where('orders_id', $orders_id)
          ->update($dataOrderArray);
	
		  
if($result)		  
{
 $msg = array("msg"=>"true","result"=>$result);		  
}
else
{
	$msg = array("msg"=>"false","result"=>$result);		  
}

		  
  return response()->json([            
        'message' => $msg, 
        ]);  
  }



public function add_task_by_offer(Request $request)
    {
 $task_array			= $request->all();
/* echo "<pre>";
   print_r($task_array); *///exit;
 $date 	  				= date('Y-m-d');
 

$start_event 		= $task_array["start_event"];
$end_event   		= $task_array["start_event"];
$orders_id			= $task_array["orders_id"];//offer id
$lead_type			= $task_array["orders_id"];//offer id
$customer			= $task_array["customers_id"];
$comp_person_id		= $task_array["comp_person_id"];
$title  			= $task_array["task_remarks"];
$evttxt_id 			= $task_array["tasktype_id"];//title LV/OSV/TFU
$evttxt  			= $task_array["tasktype_name"];//title LV/OSV/TFU
$color      		= "#3788d8";
$text_color 		= "#ffffff";//$task_array["value9"];
$opportunity_value	= $task_array["total_order_cost_new"];
$product_category 	= $task_array["app_cat_id"];
$edited_enq_id  	= $task_array["edited_enq_id"];
$account_manager 	= $task_array["account_manager"];
$task_added_by 		= $task_array["currentuserid"];
//$task_status 		= $task_array["task_status"];

  		$data_task_array["title"] 					= $title;
		$data_task_array["start_event"] 			= $start_event;
		$data_task_array["end_event"] 				= $end_event;
		$data_task_array["comp_person_id"] 			= $comp_person_id;
		$data_task_array["product_category"] 		= $product_category;
		$data_task_array["account_manager"] 		= $account_manager;
		$data_task_array["task_added_by"] 			= $task_added_by;		
		$data_task_array["customer"] 				= $customer;
		$data_task_array["evttxt_id"] 				= $evttxt_id;
		$data_task_array["evttxt"] 					= $evttxt;
		$data_task_array["lead_type"] 				= $lead_type;
		$data_task_array["opportunity_value"] 		= $opportunity_value;
		$data_task_array["color"] 					= $color;
		$data_task_array["text_color"] 				= $text_color;
		$data_task_array["status"] 					= "Pending";	

//print_r($data_task_array); //exit;
    $inserted_task_id 						= DB::table('events')->insertGetId($data_task_array);		
//	$fileArrayEnq["task_id"]  			 	= $inserted_task_id; 
	
	
if($inserted_task_id)
	{
           $msg = array("msg"=>"true","task_id"=>$inserted_task_id);
}
else
{
           $msg = array("msg"=>"false","task_id"=>0);
}
      //$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 

        return response()->json([            
        'message' => $msg, 
        ]); 	
}


public function delete_offer_requirements(Request $request)
    {
$order_array				= $request->all();
$date 	  				= date('Y-m-d');
$order_pros_id			= $order_array["order_pros_id"];		

$result_tbl_order_products_delete	= DB::table('tbl_order_product')->where('order_pros_id', $order_pros_id)->delete();

if($result_tbl_order_products_delete)		  
{
 $msg = array("msg"=>"true","result"=>$result_tbl_order_products_delete);		  
}
else
{
	$msg = array("msg"=>"false","result"=>$result_tbl_order_products_delete);		  
}

 return response()->json([            
         'order_products_listing' => $msg, 
        ]); 

}	

public function offer_tasks_list(Request $request)
    {
		$order_array		= $request->all();
		$order_id			 = $order_array["order_id"];
       // $rs_offer_tasks_list 	= DB::table('events')->where('lead_type', '=', $order_id)->orderby('start_event','asc')->select('start_event','title','comp_person_id','product_category','evttxt_id','evttxt','opportunity_value','status','task_added_by','account_manager')->get();

$sql_task_details = "SELECT events.id, events.start_event, events.title as task_description, events.comp_person_id, events.product_category, events.evttxt_id, events.evttxt as title, events.opportunity_value,events.status, events.task_added_by, events.account_manager, CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name from events
 INNER JOIN tbl_admin on tbl_admin.admin_id= events.account_manager 
 where events.lead_type = '$order_id'";// exit;
$rs_offer_tasks_list =  DB::select(($sql_task_details));
       // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'offer_tasks_list' => $rs_offer_tasks_list, 
        ]);
    }
	
	
	
public function complete_task(Request $request)
    {
		$order_array		 = $request->all();
		
		
		
		//print_r($order_array);
		$order_id			 = $order_array["order_id"];
		$task_id			 = $order_array["task_id"];
		$title		 		 = $order_array["task_remarks"];
		$task_status		 = $order_array["task_status"];
	//	$task_status		 = $order_array["currentuserid"];
		$account_manager	 = $order_array["currentuserid"];
		$color		 		 = "#1eb353";
		$text_color		 	 = "#ffffff";		


		$data_task_array_edited["title"] 					= $title;
/*		$data_task_array_edited["start_event"] 				= $start_event;
		$data_task_array_edited["end_event"] 				= $end_event;*/
		//$data_task_array_edited["product_category"] 		= $product_category;
		$data_task_array_edited["account_manager"] 			= $account_manager;
/*		$data_task_array_edited["task_added_by"] 			= $task_added_by;		
		$data_task_array_edited["customer"] 				= $customer;
		$data_task_array_edited["evttxt"] 					= $evttxt;
		$data_task_array_edited["lead_type"] 				= $lead_type;
		$data_task_array_edited["opportunity_value"] 		= $opportunity_value;*/
		$data_task_array_edited["color"] 					= $color;
		$data_task_array_edited["text_color"] 				= $text_color;
		$data_task_array_edited["status"] 					= $task_status;

//print_r($data_task_array_edited);  
		
	//	$result_data_array_invoice_products			=  $s->editRecord('events', $data_task_array_edited, "id", $task_id);



	      $sql_task_details 	 = "SELECT * from events where  id = '$task_id'";// exit;
$rs_offer_tasks_list =  DB::select(($sql_task_details));


//print_r($rs_offer_tasks_list);
$fileArray["start_event"]		= $start_event			= $rs_offer_tasks_list["0"]->start_event; 
$fileArray["end_event"]			= $end_event			= $rs_offer_tasks_list["0"]->start_event; 
$fileArray["product_category"]	= $product_category		= $rs_offer_tasks_list["0"]->product_category; 
$fileArray["account_manager"]	= $account_manager		= $rs_offer_tasks_list["0"]->account_manager; 
//$fileArray["task_added_by"]		= $task_added_by		= $rs_offer_tasks_list["0"]->task_added_by; 
$fileArray["customer"]			= $customer				= $rs_offer_tasks_list["0"]->customer; 
//$fileArray["comp_person_id"]	= $comp_person_id		= $rs_offer_tasks_list["0"]->comp_person_id; 

$fileArray["evttxt"]			= $evttxt				= $rs_offer_tasks_list["0"]->evttxt; 
$fileArray["lead_type"]			= $lead_type			= $rs_offer_tasks_list["0"]->lead_type; 
$fileArray["opportunity_value"]	= $opportunity_value	= $rs_offer_tasks_list["0"]->opportunity_value; 
$fileArray["status"]		= $task_status			= $task_status; 


//print_r($fileArray);


    $inserted_task_completd_id 		= DB::table('events_history')->insertGetId($fileArray);

     $result_task_completed			=    DB::table('events')
            ->where('id', $task_id)
            ->update($data_task_array_edited);

// Logic for your dashboard, e.g., returning dashboard data.


 if($result_task_completed){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

        return response()->json([            
        'message' => $msg, 
		 'result_task_completed' => $result_task_completed,
		 'inserted_task_completd_id' => $inserted_task_completd_id,
		 
        ]);

    }	
	
//view offer using orders_id
public function view_offer(Request $request)
    {
		$order_array						= $request->all();
		$order_id							= $order_array["order_no"];
        $rs_offer_tasks_list 				= DB::table('tbl_order')->where('orders_id', '=', $order_id)->orderby('orders_id','asc')->select('orders_id','customers_id'
		,'payment_terms'
		,'delivery_day'
		,'offer_validity'
		,'offer_warranty'
		,'order_in_favor_of'
		,'lead_id'
		,'customers_id'
		,'customers_name'
		,'customers_email'
		,'customers_contact_no'
		,'shipping_company'
		,'shipping_name'
		,'shipping_street_address'
		,'shipping_city'
		,'shipping_zip_code'
		,'lead_id'
		,'offer_subject'
		,'shipping_state'
		,'shipping_country_name'
		,'date_ordered'
		,'time_ordered'
		,'orders_status'
		,'total_order_cost'
		,'total_order_cost_new'
		,'offer_calibration'
		,'show_discount'
		,'order_by'
		,'offer_probability'
		,'follow_up_date'		
		,'Price_type as price_type'
		,'Price_value'
		,'edited_enq_id'
		,'hot_offer'
		,'offer_currency'
		,'freight_amount'
		,'order_by'	
		,'offer_type')->get();
		
		// select('start_event','title','comp_person_id','product_category','evttxt_id','evttxt','opportunity_value','status','task_added_by','account_manager')->get();
		
		
		$offer_type							= $rs_offer_tasks_list["0"]->offer_type;
		$customer_id						= $rs_offer_tasks_list["0"]->customers_id;
		$payment_terms						= $rs_offer_tasks_list["0"]->payment_terms;
		$delivery_day						= $rs_offer_tasks_list["0"]->delivery_day;
	 	$offer_validity						= $rs_offer_tasks_list["0"]->offer_validity;
		$offer_warranty						= $rs_offer_tasks_list["0"]->offer_warranty;
		$order_in_favor_of					= $rs_offer_tasks_list["0"]->order_in_favor_of;
		$lead_id							= $rs_offer_tasks_list["0"]->lead_id;
		$order_by							= $rs_offer_tasks_list["0"]->order_by;
		$company_name						= company_name_return($customer_id);


		$offer_product_details  			= product_name_generated_with_quantity_json_tbl_order_product($order_id);
		$grand_total_offer_details 			= grand_total_offer($order_id);



	$sql_admin					= "SELECT admin_fname,admin_lname,admin_designation,admin_telephone,admin_email from tbl_admin where admin_id= '$order_by'";

	$row_admin 					= DB::select(($sql_admin));	
//    $comp_person_designation	= isset($rs_comp[0]->designation_id) ? $rs_comp[0]->designation_id : '0';	
	
//	$row_admin  				= mysqli_fetch_object($rs_admin);
	$emp_name					= $row_admin[0]->admin_fname." ".$row_admin[0]->admin_lname;
	$emp_designation			= $row_admin[0]->admin_designation;
	$emp_telephone				= $row_admin[0]->admin_telephone;
	$emp_email					= $row_admin[0]->admin_email;




	$sql_lead_id			= "SELECT salutation,comp_person_id from tbl_lead where id= '$lead_id'";
	$rs_lead_id 			= DB::select(($sql_lead_id)); 
   // $designation_name	  = isset($rowdesignation_name->designation_name) ? $rowdesignation_name->designation_name : '';
$comp_person_id	  = isset($rs_lead_id->comp_person_id) ? $rs_lead_id->comp_person_id : '0';

if($comp_person_id=='0' || $comp_person_id=='')
{
 	$sql_comp				= "SELECT designation_id from tbl_comp where id= '$customer_id'";
	//$rs_comp				= mysqli_query($GLOBALS["___mysqli_ston"], $sql_comp);
	$rs_comp = DB::select(($sql_comp)); 
//print_r($rs_comp);
	//$row_comp  			= mysqli_fetch_object($rs_comp);
  $comp_person_designation	= isset($rs_comp[0]->designation_id) ? $rs_comp[0]->designation_id : '0';
 	//$comp_person_designation	= $row_comp->designation_id;
 
	//$comp_shipping_street_address=$row_comp->address.", ".$row_comp->city.", ".$row_comp->state.", ".$row_comp->zip;
//	$shipping_telephone_no	= $row_comp->telephone;

}

else
{
 	 $sql_comp					= "SELECT designation_id from tbl_comp_person where id= '$comp_person_id'";
	 $rs_comp 					= DB::select(($sql_comp));	
     $comp_person_designation	= isset($rs_comp[0]->designation_id) ? $rs_comp[0]->designation_id : '0';
}

//echo "designa:".	$comp_person_designation;

if($comp_person_designation!='Others' && $comp_person_designation!='93')
		{
$designation				= designation_comp_name($comp_person_designation);
                 }
				 else
				 {
					 $designation ="Others";
				 }
	
		$supply_delivery_terms_name			= supply_delivery_terms_name($delivery_day);
		$supply_payment_terms_name			= supply_payment_terms_name($payment_terms);				
		$offer_validity_name				= offer_validity_name($offer_validity);				
		$offer_warranty_name				= warranty_name($offer_warranty);


		$country_name			= CountryName($delivery_day);				
		$state_name				= StateName($offer_validity);				
		$city_name				= CityName($offer_warranty);
		$address 				= $rs_offer_tasks_list["0"]->shipping_street_address.''.$rs_offer_tasks_list["0"]->shipping_zip_code.', '.$city_name.', '.$state_name.', '.$country_name;

 		
		
        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            

		  'grand_total' => $grand_total_offer_details, 		  
		  'company_full_name' => $company_name, 		  
		  'address' => $address, 		  
		  'delivery_terms' => $supply_delivery_terms_name, 
		  'payment_terms' => $supply_payment_terms_name, 		  
		  'offer_validity' => $offer_validity_name,
		  'offer_warranty' => $offer_warranty_name, 
		  'order_in_favor_of' => $order_in_favor_of, 
		   'designation' => $designation, 
		  'acc_manager_name' => $emp_name, 
          'acc_manager_designation' => designation_name($emp_designation), 		  
          'acc_manager_phone' => $emp_telephone, 		  		  
		  'acc_manager_email' => $emp_email, 		  		  		  
		  'offer_details' => $rs_offer_tasks_list, 
          'offer_product_details' => $offer_product_details,  
		  
		  
		  		  

        ]);
    }	
//mark as dead offer details page
   public function  kill_offer(Request $request)
    {
    
        $date 	  									= date('Y-m-d');
        $order_status								= $request->order_status;
		$orders_id									= $request->orders_id;
		$currentuserid								= $request->currentuserid;
        $enq_remark_edited							= $request->remark;
        $EID										= $request->enq_id;
		$dead_duck									= $request->dead_duck;
      
		$offer_probability							= "3";
		$dataStatusArray["offer_probability"]  	 = $offer_probability;
		$fileArrayEnq["enq_stage"]  			 = $offer_probability;
		$fileArrayEnq["dead_duck"]  			 = $dead_duck;
      
        $ArrayData['enq_remark'] = $enq_remark_edited;
		$ArrayData['dead_duck'] = $dead_duck;
        $snooze_days							= "0";
        $snooze_date							= date('Y-m-d h:m:s',strtotime('+'.$snooze_days.' days'));
        $ArrayDataedited['snooze_days'] 		= 0;
        $ArrayDataedited['snooze_date'] 		= date('Y-m-d h:m:s');
        $ArrayDataedited['dead_duck'] 			= $dead_duck;
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

        DB::table('tbl_web_enq_edit')
            ->where('enq_id', $EID)
            ->update($fileArrayEnq);

        DB::table('tbl_web_enq')
            ->where('ID', $EID)
            ->update($ArrayData);
        



		$fileArray["order_status"]  			= $order_status;
		$dataStatusArray["orders_status"]  		= $order_status;
		$fileArray["lost_reason"]  				= $enq_remark_edited;
		$fileArray["order_id"]  				= $orders_id;	
		//$fileArray['dead_duck'] 				= $dead_duck;
		$fileArray["comment"] 					= addslashes($enq_remark_edited);	
		$fileArray["comment_by"] 				= $currentuserid;	
		$fileArray["customer_notification"] 	= "no";//$_REQUEST["customer_notification"];	
		$fileArray["trackingNo"] 				= "N/A";//$_SESSION["trackingNo"];	
		
		
		
		
		  DB::table('tbl_web_enq_edit')
            ->where('enq_id', $EID)
            ->update($fileArrayEnq);

       $result_AWB 								= DB::table('tbl_order')
            ->where('orders_id', $orders_id)
            ->update($dataStatusArray);
		
			    $inserted_comment_id 						= DB::table('tbl_order_comment')->insertGetId($fileArray);	
	//	$result 								= $s->insertRecord('tbl_order_comment',$fileArray);
		//$result_AWB 							= $s->editRecord('tbl_order', $dataStatusArray, 'orders_id' , $pcode); 


		
        //$currentuserid = Auth::user()->id; 
        $fileArray_tbl_enq_remarks["enq_id"]					= $EID;
        $fileArray_tbl_enq_remarks["remarks"]					= $enq_remark_edited;
        $fileArray_tbl_enq_remarks["dead_duck"]					= $dead_duck;
        $fileArray_tbl_enq_remarks["added_by"]					= $request->acc_manager;
        $fileArray_tbl_enq_remarks["remarks_added_date_time"]	= date("Y-m-d H:i:s");
        $fileArray_tbl_enq_remarks["snooze_days"]				= $snooze_days;
        $fileArray_tbl_enq_remarks["snooze_date"]				= $snooze_date;

       $insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 
       if($insId){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

        return response()->json([            
        'message' => $msg, 
		 'inserted_comment_id' => $inserted_comment_id, 
        ]);
    }	
	
	
	
	
//move to opportunity

 public function  move_to_opportunity(Request $request)
    {
		
    
        $date 	  									= date('Y-m-d');
        $order_status								= $request->order_status;
		$orders_id									= $request->orders_id;
		$offer_probability							= $request->offer_probability;
		$currentuserid								= $request->currentuserid;
  
		//$offer_probability							= "3";

$ArrayData['offer_probability'] 	= $offer_probability;
//update offer probablity in offer
$result_order = DB::table('tbl_order')
              ->where('orders_id', $orders_id)
              ->update($ArrayData);

//update offer probablity in enquiry			
$ArrayData_enq['enq_stage'] 		= $offer_probability;
 $ArrayData_enq['mel_updated_on']	= date("Y-m-d H:i:s");			

$result_enq =  DB::table('tbl_web_enq_edit')
            ->where('order_id', $orders_id)
            ->update($ArrayData_enq);
   
      // $insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 
       if($result_enq){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

        return response()->json([            
        'message' => $msg, 
		 'result' => $result_enq, 
        ]);
    }		
	


//create proforma invoice

public function create_proforma_invoice(Request $request)
{
$offer_array			= $request->all();

	/*echo "<pre>";
		print_r($offer_array); exit;*/
		
$pcode					= $order_id				= $offer_array["customerData"]["orders_id"];		
/*$offer_array["companyData"];		 
$offer_array["bankData"];
$offer_array["customerData"];
$offer_array["customerData"]["product_items_details"];
$offer_array["otherDetails"];
$offer_array["requirementData"];
$offer_array["finalFreightData"];*/
$Special_Ins = "";
//co branch details
$branch_sel						= $offer_array["companyData"]["id"];	
$company_location_id			= $offer_array["companyData"]["location"];	
$company_branch_address			= $offer_array["companyData"]["address"];	
$company_branch_state			= $offer_array["companyData"]["state"];	
$company_branch_cin_no			= $offer_array["companyData"]["cin_no"];	
$company_branch_email_id		= $offer_array["companyData"]["email_id"];	
$company_branch_pan_no			= $offer_array["companyData"]["pan_no"];	
$company_branch_gst_no			= $offer_array["companyData"]["gst_no"];	
$company_branch_pincode			= $offer_array["companyData"]["pincode"];	
$company_branch_phone_number	= $offer_array["companyData"]["phone_number"];	


//co bank details
$bank_sel						= $offer_array["bankData"]["bank_id"];	
$account_holder_name			= $offer_array["bankData"]["account_holder_name"];	
$bank_name						= $offer_array["bankData"]["bank_name"];	
$bank_acc_no					= $offer_array["bankData"]["bank_acc_no"];	
$bank_address					= $offer_array["bankData"]["bank_address"];	
$ifsc_code						= $offer_array["bankData"]["ifsc_code"];	




//co customer details
$lead_id							= $offer_array["customerData"]["lead_id"];	
//$Prepared_by						= $offer_array["customerData"]["order_by"];	
$Prepared_by						= $offer_array["createdBy"];//$offer_array["customerData"]["order_by"];	
$orders_id							= $offer_array["customerData"]["orders_id"];	
$app_cat_id							= $offer_array["customerData"]["app_cat_id"];	
$offer_type							= $offer_array["customerData"]["offer_type"];	
$ref_source							= $offer_array["customerData"]["ref_source"];	
$city_name							= $offer_array["customerData"]["city_name"];	
$state_name							= $offer_array["customerData"]["state_name"];	
$country_name						= $offer_array["customerData"]["country_name"];	
$customers_id						= $offer_array["customerData"]["customers_id"];	
$edited_enq_id						= $offer_array["customerData"]["edited_enq_id"];	
$show_discount						= $offer_array["customerData"]["show_discount"];	
$comp_person_id						= $offer_array["customerData"]["comp_person_id"];	
$customers_name						= $offer_array["customerData"]["customers_name"];	
$edited_enq_id						= $offer_array["customerData"]["edited_enq_id"];	
$show_discount						= $offer_array["customerData"]["show_discount"];	
$comp_person_id						= $offer_array["customerData"]["comp_person_id"];	
$shipping_address					= $offer_array["customerData"]["shipping_address"];	
$freight_amount						= $offer_array["customerData"]["freight_amount"];	


$billing_company					= $offer_array["customerData"]["billing_company"];	
$shipping_company					= $offer_array["customerData"]["shipping_company"];	
$shipping_country					= $offer_array["customerData"]["shipping_country"];	
$shipping_state						= $offer_array["customerData"]["shipping_state"];	
$shipping_zip_code					= $offer_array["customerData"]["shipping_zip_code"];	

$shipping_city						= $offer_array["customerData"]["shipping_city"];	 
$offer_probability					= $offer_array["customerData"]["offer_probability"];	 
$customers_contact_no				= $offer_array["customerData"]["customers_contact_no"];	 
$customers_email					= $offer_array["customerData"]["customers_email"];	 
$gstNumber							= $offer_array["gstNumber"];
$piDate								= $offer_array["otherDetails"]["piDate"];
$finance_email						= "finance@stanlay.com";//$offer_array["otherDetails"]["email"];
$PanNumber							= "00";//$offer_array["otherDetails"]["PanNumber"];
$buyerPoNumber						= $offer_array["otherDetails"]["buyerPoNumber"];
$deliveryNote						= $offer_array["otherDetails"]["deliveryNote"];
$po_path							= $offer_array["otherDetails"]["poFile"];
$subTotal							= $offer_array["finalFreightData"]["subTotal"];
$freightValue						= $offer_array["finalFreightData"]["freightValue"];
$gstValue							= $offer_array["finalFreightData"]["gstValue"];
$advancedRecieved					= $offer_array["finalFreightData"]["advancedRecieved"];
$grandTotal							= $offer_array["finalFreightData"]["grandTotal"];
$performa_notes						= "";
$Payment_Terms						= "";

		
		$dataArray["PO_NO"] 				= $buyerPoNumber;
		$dataArray["performa_notes"] 		= $performa_notes;
		$dataArray["Payment_Terms"]			= $Payment_Terms;		
		$dataArray["Special_Ins"] 			= $Special_Ins;
		$dataArray["branch_sel"] 			= $branch_sel;
		$dataArray["bank_sel"] 				= $bank_sel;
		$dataArray["PO_From"] 				= $Prepared_by;
		$dataArray["pi_generated_date"] 	= $piDate;
		$dataArray["Cus_Com_Name"] 			= $billing_company;
		$dataArray["Buyer_Name"] 			= $customers_name;
		$dataArray["O_Id"] 					= $orders_id;
		$dataArray["pi_status"] 			= "pending";
		$dataArray["save_send"] 			= "no";	
		
		$buyer_updated= $shipping_address." ".$shipping_city." ".$shipping_state." ".$shipping_zip_code;
		
		$dataArray["Buyer"] 				= $buyer_updated;//$_REQUEST["Buyer"];
		$dataArray["Buyer_Tel"] 			= $customers_contact_no;
		$dataArray["Buyer_Fax"] 			= "0";
		$dataArray["Buyer_Mobile"] 			= $customers_contact_no;
		$dataArray["Buyer_Email"] 			= $customers_email;
		$dataArray["buyer_gst"] 			= $gstNumber;
		$dataArray["Prepared_by"] 			= $Prepared_by;
		$dataArray["advance_received"] 		= $advancedRecieved;
		$dataArray["Special_Ins"] 			= $deliveryNote;
		$dataArray["PO_path"] 				= $po_path;		
		
		//echo $_FILES["PO_path"]["name"] ;
	/*	if($_FILES["PO_path"]["name"] != "")
		{
			$filePath = $s->fileUpload("uploads/do/", "PO_path", "PO_");
			if($filePath != -1)
			{
				 $dataArray["PO_path"] = $filePath;
				// exit;
			}
			else 
			{
					$msg = 'Pl Check Invoice Name! Invoice Not Uploaded';
			}
		}	*/	
//		echo "orders_id".$orders_id;
 "prev id:".$previous_proforma_id_check					= performa_invoice_id($orders_id);//exit;	
 
//notification
$send_cord="send";
 	if($send_cord=='send') {
	$finance_name="53";//finance@stanlay.com";
    $acc_manager_phone 			= account_manager_phone($Prepared_by);
	$acc_manager_name_sms		= admin_name($Prepared_by);
  	$acc_manager_name_finance	= admin_name($finance_name);
 	$acc_manager_phone_finance 	= "917053062400";//$s->account_manager_phone($finance_name); 

	$finance_name_sanjeev="123";//finance@stanlay.com";
//	$acc_manager_phone_sanjeev 			= $s->account_manager_phone($PO_From);
//	$acc_manager_name_sms_sanjeev		= $s->admin_name($Prepared_by);
 	$acc_manager_name_finance_sanjeev	= admin_name($finance_name_sanjeev);
 	$acc_manager_phone_finance_sanjeev 	= "917053062400";//"91".$s->account_manager_phone($finance_name_sanjeev); 

	$finance_name_sha="241";//finance@stanlay.com";
//	$acc_manager_phone_sanjeev 			= $s->account_manager_phone($PO_From);
//	$acc_manager_name_sms_sanjeev		= $s->admin_name($Prepared_by);
 	$acc_manager_name_finance_sha	= admin_name($finance_name_sha);
 	$acc_manager_phone_finance_sha 	= "917053062400";//"91".$s->account_manager_phone($finance_name_sha); 

		
	/*************whatsapp send to AC MANAGER***********/
//			echo "Whatsaaaaap";		  
//echo 			$tl_name;
//echo 	$tl_phone;
//$acc_manager_phone=$acc_manager_phone;
//$acc_manager_phones=$tl_phone;//"919811169723";
//$acc_manager_phones="919811169723";
//$acc_manager_phones="918218750790";
$acc_manager_phone_finance= "917053062400";//"919319396589";
//$acc_manager_names = $tl_name;
$message_ac_managers_fin='Dear  *'.$acc_manager_name_finance.'*, '. "\r\n".
'*PI of Offer #* :*'.$pcode.'*, '." has been received for Approval.". "\r\n".
'*Customer Name :* '.$billing_company.' '." \r\n".
'*A/C Manager. :* '.admin_name($Prepared_by).' '." \r\n".
'Thanking You,'."\r\n".
'ACL Stanlay Team'."\r\n".
'Helpline: 011-41406926'."\r\n".
'Web : www.stanlay.in'."\r\n".
'-----------------------------------------------------------'."\r\n".
'ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
//echo $wp=whatsapp_msg($acc_manager_phone_finance,$message_ac_managers_fin);
//print_r($wp);
 
 
/*Sanjeev whatsapp starts */ 
// $tl_name_sanjeev="Sanjeev";
// $acc_manager_names_sanjeev = $tl_name_sanjeev;
// $acc_manager_phone_finance_sanjeev="";
$message_ac_managers_fin_sanjeev='Dear  *'.$acc_manager_name_finance_sanjeev.'*, '. "\r\n".
'*PI of Offer #* :*'.$pcode.'*, '." has been received for Approval.". "\r\n".
'*Customer Name :* '.$billing_company.' '." \r\n".
'*A/C Manager. :* '.admin_name($Prepared_by).' '." \r\n".
'Thanking You,'."\r\n".
'ACL Stanlay Team'."\r\n".
'Helpline: 011-41406926'."\r\n".
'Web : www.stanlay.in'."\r\n".
'-----------------------------------------------------------'."\r\n".
'ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
 //whatsapp_msg($acc_manager_phone_finance_sanjeev,$message_ac_managers_fin_sanjeev);

/*Sanjeev whatsapp ends */

/*Shashank whatsapp starts */

$message_ac_managers_fin_sha='Dear  *'.$acc_manager_name_finance_sha.'*, '. "\r\n".
'*PI of Offer #* :*'.$pcode.'*, '." has been received for Approval.". "\r\n".
'*Customer Name :* '.$billing_company.' '." \r\n".
'*A/C Manager. :* '.$Prepared_by.' '." \r\n".
'Thanking You,'."\r\n".
'ACL Stanlay Team'."\r\n".
'Helpline: 011-41406926'."\r\n".
'Web : www.stanlay.in'."\r\n".
'-----------------------------------------------------------'."\r\n".
'ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
// whatsapp_msg($acc_manager_phone_finance_sha,$message_ac_managers_fin_sha);
/*Shashank whatsapp ends */
//function to send whatsapp 06-Jul-2022	


// exit;
	//$defaultSMS_phone 		= "7701821170";//sandeep:----jyoti9871190715
			
/*$sms_msg="Dear $acc_manager_name_finance
PI of Offer No. $pcode has been received for approval.";
 

		$dataTempOrder_send["save_send"]= "yes";
			$result 	= $s->editRecord('tbl_performa_invoice', $dataTempOrder_send,'O_Id' ,$pcode);    
			$to 		= "finance@stanlay.com".","."purchaseaccounts@stanlay.com".","."accounts@stanlay.com" ; 
			$to1 		= $s->admin_email($_REQUEST['Prepared_by']); 
 			$from 		= $s->admin_email($_REQUEST['Prepared_by']);
			$subject 	= 'PI of offer No. #'.$pcode.' has been received for approval!';
			$filename 	= 'pi_email_preview.php';
			$s->sendmail($to,$from,$subject,$filename);	
			$dataTempOrder_send["save_send"]= "yes";
			$result 	= $s->editRecord('tbl_performa_invoice', $dataTempOrder_send,'O_Id' ,$pcode); 
			  
			
 */
				
} 
//notofication ends
 
 
 
if($previous_proforma_id_check=='0')
{
      "performa invoice id::".$inserted_proforma_id 						= DB::table('tbl_performa_invoice')->insertGetId($dataArray);	 //exit;
  $result_tbl_pro_wise_discount_delete	= DB::table('prowise_discount')->where('orderid', $order_id)->delete();
   $msg = array("msg"=>"true","proforma_id"=>$inserted_proforma_id);


}
else
{
           $msg = array("msg"=>"false","proforma_id"=>$previous_proforma_id_check,"msg2"=>"Proforma Invoice already created for this offer #.");


}
//echo "CTR".count($offer_array["requirementData"]);
$ctr								= count($offer_array["requirementData"]);
 $result_tbl_pro_wise_discount_delete	= DB::table('prowise_discount')->where('orderid', $order_id)->delete();
for($i=0; $i<$ctr; $i++)
{

$order_pros_id						= $offer_array["requirementData"][$i]["order_pros_id"];	
$pro_id								= $offer_array["requirementData"][$i]["pro_id"];	
$price_list							= "pvt";//$offer_array["requirementData"][$i]["price_list"];	
$proidentry							= $offer_array["requirementData"][$i]["proidentry"];	
$pro_model							= $offer_array["requirementData"][$i]["pro_model"];	
$proPrice							= $offer_array["requirementData"][$i]["pro_price"];	
$proFinalPrice						= $offer_array["requirementData"][$i]["pro_price"];	
$pro_name							= $offer_array["requirementData"][$i]["pro_name"];	
$hsn_code							= $offer_array["requirementData"][$i]["hsn_code"];	
$pro_quantity						= $offer_array["requirementData"][$i]["pro_quantity"];	
$GST_percentage						= $offer_array["requirementData"][$i]["GST_percentage"];	
$protaxcost							= $offer_array["requirementData"][$i]["Pro_tax"];	
//$discountValue						= $offer_array["requirementData"][$i]["discount_percentage"];	// in percentage
//$proDiscount						= $offer_array["requirementData"][$i]["pro_discount_amount"];	 // in rupees

$discountValue						= $offer_array["requirementData"][$i]["pro_discount_amount"];	// in percentage
$proDiscount						= $offer_array["requirementData"][$i]["discount_percentage"];	 // in rupees

$pi_pro_rate						= $offer_array["requirementData"][$i]["rate"];	 // in rupees
$pi_pro_rate_subtotal				= $offer_array["requirementData"][$i]["subtotal"];	 // in rupees


//$freight_amount								= $freight_amount;	
$pro_sort									= "0";
if($offer_array["requirementData"][$i]["service_period"]=='' && $offer_array["requirementData"][$i]["service_period"]=='0')
{
$service_period								= "1";//$offer_array["requirementData"][$i]["service_period"];		
}
else
{
$service_period								= $offer_array["requirementData"][$i]["service_period"];		
}

$additional_disc							= "0";	

	
	
			$dataOrderProducts1["order_pros_id"] 	  = $order_pros_id;
			$dataOrderProducts["order_id"] 		  	  = $orders_id;
			$dataOrderProducts["pro_id"] 		  	  = $pro_id;
			$dataOrderProducts["manufacturers_id"] 	  = "0";//$manufacturers_id;
			$dataOrderProducts["proidentry"]	  	  = $proidentry;
			$dataOrderProducts["additional_disc"]  	  = "0";//$additional_disc;
			$dataOrderProducts["group_id"] 		  	  = "0";//$group_id;
			$dataOrderProducts["qty_attDset_id"]      = "0";//$qty_attDset_id;
			$dataOrderProducts["customers_id"]	  	  = $customers_id;
			$dataOrderProducts["pro_model"] 	  	  = $pro_model;
			$dataOrderProducts["hsn_code"] 	  	  	  = $hsn_code;
			$dataOrderProducts["pro_name"] 		  	  = $pro_name;
			$dataOrderProducts["pro_price"] 	  	  = $proPrice;
			$dataOrderProducts["pro_final_price"]  	  = $proFinalPrice;// calculate final price - coupon amount 
			$dataOrderProducts["wrap_cost"] 		  = "0.00";//$wrap_cost;
			$dataOrderProducts["Pro_tax"] 		  	  = $protaxcost;
			$dataOrderProducts["GST_percentage"]   	  = $GST_percentage;
			$dataOrderProducts["pro_quantity"] 	      = $pro_quantity;
			$dataOrderProducts["coupon_id"] 	      = $discountValue;// in percentage 
			$dataOrderProducts["pro_coupon_amount"]   = 0.00;
			$dataOrderProducts["pro_text"]   		  = '0';
			$dataOrderProducts["pro_ret_remarks"]     = '0';
			$dataOrderProducts["pro_ret_qty"]   	  = '0';
			$dataOrderProducts["pro_ret_amt"]   	  = '0';
			$dataOrderProducts["barcode"]   		  = '0';
			$dataOrderProducts["freight_amount"]   	  = $freight_amount;
			
			
			//$proDiscount							  = "0";//$discount;
			$dataOrderProducts["pro_discount_amount"] = $proDiscount;
//			$dataOrderProducts["additional_disc"] 	  = $additional_disc;
			
			$dataOrderProducts["proAttribute_Cost"]   = "0";//$rowShopCartProduct->attributesetPrice;
			$dataOrderProducts["order_pro_status"]    = "Pending";	
			$dataOrderProducts["service_period"]      = $service_period;
 

			$order_products_table					  = DB::table('tbl_order_product')->where('order_pros_id', $order_pros_id)->update($dataOrderProducts);// exit;



 			$dataOrderProductsdisc["orderid"] 		  	  = $orders_id;
			$dataOrderProductsdisc["proid"] 		  	  = $pro_id;	
 			$dataOrderProductsdisc["discount_amount"] 	  = $discountValue;
			$dataOrderProductsdisc["discount_percent"] 	  = $proDiscount;	
			$dataOrderProductsdisc["show_discount"] 	  = "No";//$pro_id;	

//insert in prowise discount table
$lead_products_table						= DB::table('prowise_discount')->insert($dataOrderProductsdisc);		
}			
$total_order_cost							= $offer_array["finalFreightData"]["grandTotal"];;
//$dataArrayOrder["freight_amount"]   	  	= $freightValue;
////$dataArrayOrder["Special_Ins"] 				= $freightValue;
////$gstValue									= $offer_array["finalFreightData"]["gstValue"];;
//$dataArrayOrder["total_order_cost"] 		= $total_order_cost;
//$dataArrayOrder["total_order_cost_new"] 	= $total_order_cost;
//
//$order_products_table						= DB::table('tbl_order')->where('orders_id', $orders_id)->update($dataArrayOrder);


$dataArrayOrdertotal["freight_amount"]   	  	= $freightValue;
//$dataArrayOrder["Special_Ins"] 			= $freightValue;
//$gstValue									= $offer_array["finalFreightData"]["gstValue"];;
$dataArrayOrdertotal["total_order_cost"] 		= $total_order_cost;
$dataArrayOrdertotal["offer_probability"] 		= "4";
$dataArrayOrdertotal["total_order_cost_new"] 	= $total_order_cost;
$order_products_table						= DB::table('tbl_order')->where('orders_id', $orders_id)->update($dataArrayOrdertotal);


$ArrayData_enq['enq_stage'] = "4";
	
	     $result_order_status= DB::table('tbl_web_enq_edit')
            ->where('order_id', $orders_id)
            ->update($ArrayData_enq);

//insert data in performa_invoice  table	
//exit;

      //$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 

        return response()->json([            
        'message' => $msg, 
        ]); 
}

//edit proforma invoice





public function edit_proforma_invoice(Request $request)
{
$offer_array			= $request->all();
/*echo "<pre>";
print_r($offer_array);*/ 
$pcode					= $order_id				= $offer_array["customerData"]["orders_id"];	
//exit;	
/*$offer_array["companyData"];		 
$offer_array["bankData"];
$offer_array["customerData"];
$offer_array["customerData"]["product_items_details"];
$offer_array["otherDetails"];
$offer_array["requirementData"];
$offer_array["finalFreightData"];*/
$Special_Ins 					= "";
$save_send						= "no";
//co branch details
$branch_sel						= $offer_array["companyData"]["id"];	
$company_location_id			= $offer_array["companyData"]["location"];	
$company_branch_address			= $offer_array["companyData"]["address"];	
$company_branch_state			= $offer_array["companyData"]["state"];	
$company_branch_cin_no			= $offer_array["companyData"]["cin_no"];	
$company_branch_email_id		= $offer_array["companyData"]["email_id"];	
$company_branch_pan_no			= $offer_array["companyData"]["pan_no"];	
$company_branch_gst_no			= $offer_array["companyData"]["gst_no"];	
$company_branch_pincode			= $offer_array["companyData"]["pincode"];	
$company_branch_phone_number	= $offer_array["companyData"]["phone_number"];	


//co bank details
$bank_sel						= $offer_array["bankData"]["bank_id"];	
$account_holder_name			= $offer_array["bankData"]["account_holder_name"];	
$bank_name						= $offer_array["bankData"]["bank_name"];	
$bank_acc_no					= $offer_array["bankData"]["bank_acc_no"];	
$bank_address					= $offer_array["bankData"]["bank_address"];	
$ifsc_code						= $offer_array["bankData"]["ifsc_code"];	




//co customer details
///$lead_id						= $offer_array["customerData"]["lead_id"];	
//$Prepared_by					= $offer_array["customerData"]["order_by"];	
$Prepared_by					= $offer_array["createdBy"];//$offer_array["customerData"]["order_by"];	
$orders_id						= $offer_array["customerData"]["orders_id"];	
///$app_cat_id							= $offer_array["customerData"]["app_cat_id"];	
///$offer_type							= $offer_array["customerData"]["offer_type"];	
///$ref_source							= $offer_array["customerData"]["ref_source"];	
///$city_name							= $offer_array["customerData"]["city_name"];	
///$state_name							= $offer_array["customerData"]["state_name"];	
///$country_name						= $offer_array["customerData"]["country_name"];	
///$customers_id						= $offer_array["customerData"]["customers_id"];	
///$edited_enq_id						= $offer_array["customerData"]["edited_enq_id"];	
///$show_discount						= $offer_array["customerData"]["show_discount"];	
///$comp_person_id						= $offer_array["customerData"]["comp_person_id"];	
$customers_name							= $offer_array["customerData"]["contactPerson"];	
///$edited_enq_id						= $offer_array["customerData"]["edited_enq_id"];	
///$show_discount						= $offer_array["customerData"]["show_discount"];	
///$comp_person_id						= $offer_array["customerData"]["comp_person_id"];	
$shipping_address						= $offer_array["customerData"]["companyAddress"];	
$freight_amount							= $offer_array["finalFreightData"]["freightValue"];//$freightValue;//$offer_array["customerData"]["freight_amount"];	


$billing_company						= $offer_array["customerData"]["companyName"];	
///$shipping_company					= $offer_array["customerData"]["shipping_company"];	
///$shipping_country					= $offer_array["customerData"]["shipping_country"];	
///$shipping_state						= $offer_array["customerData"]["shipping_state"];	
///$shipping_zip_code					= $offer_array["customerData"]["shipping_zip_code"];	

///$shipping_city						= $offer_array["customerData"]["shipping_city"];	 
///$offer_probability					= $offer_array["customerData"]["offer_probability"];	 
$customers_contact_no				= $offer_array["customerData"]["mobile"];	 
$customers_email					= $offer_array["customerData"]["email"];	 
$gstNumber							= $offer_array["gstNumber"];
$piDate								= $offer_array["otherDetails"]["piDate"];
///$finance_email						= "finance@stanlay.com";//$offer_array["otherDetails"]["email"];
///$PanNumber							= "00";//$offer_array["otherDetails"]["PanNumber"];

$buyerPoNumber						= $offer_array["otherDetails"]["buyerPoNumber"];
$deliveryNote						= $offer_array["otherDetails"]["deliveryNote"];
$po_path							= $offer_array["otherDetails"]["poFile"];
$subTotal							= $offer_array["finalFreightData"]["subTotal"];
$freightValue						= $offer_array["finalFreightData"]["freightValue"];
$gstValue							= $offer_array["finalFreightData"]["gstValue"];
$advancedRecieved					= $offer_array["finalFreightData"]["advancedRecieved"];
$grandTotal							= $offer_array["finalFreightData"]["grandTotal"];
$performa_notes						= "";
$Payment_Terms						= "";
//echo "CTR".count($offer_array["requirementData"]);
$ctr								= count($offer_array["requirementData"]);


$result_tbl_pro_wise_discount_delete	= DB::table('prowise_discount')->where('orderid', $order_id)->delete();
for($i=0; $i<$ctr; $i++)
{
$order_pros_id						= $offer_array["requirementData"][$i]["order_pros_id"];	
$pro_id								= $offer_array["requirementData"][$i]["pro_id"];	
/*if($offer_array["requirementData"][$i]["price_list"]=='')
{
$price_list							= "pvt";//$offer_array["requirementData"][$i]["price_list"];	
}
else
{
$price_list							= $offer_array["requirementData"][$i]["price_list"];		
}*/

$price_list							= "pvt";//$offer_array["requirementData"][$i]["price_list"];	
$proidentry							= $offer_array["requirementData"][$i]["proidentry"];	
$pro_model							= $offer_array["requirementData"][$i]["pro_model"];	
$proPrice							= $offer_array["requirementData"][$i]["pro_price"];	
$proFinalPrice						= $offer_array["requirementData"][$i]["pro_price"];	
$pro_name							= $offer_array["requirementData"][$i]["pro_name"];	
$hsn_code							= $offer_array["requirementData"][$i]["hsn_code"];	
$pro_quantity						= $offer_array["requirementData"][$i]["pro_quantity"];	
$GST_percentage						= $offer_array["requirementData"][$i]["GST_percentage"];	
$protaxcost							= $offer_array["requirementData"][$i]["Pro_tax"];	
$discountValue						= $offer_array["requirementData"][$i]["discount_percentage"];	// in percentage
$proDiscount						= $offer_array["requirementData"][$i]["pro_discount_amount"];	 // in rupees



$pi_pro_rate						= $offer_array["requirementData"][$i]["rate"];	 // in rupees
$pi_pro_rate_subtotal				= $offer_array["requirementData"][$i]["subtotal"];	 // in rupees


//$freight_amount								= $freight_amount;	
$pro_sort							= "0";
if($offer_array["requirementData"][$i]["service_period"]=='' && $offer_array["requirementData"][$i]["service_period"]=='0')
{
$service_period						= "1";//$offer_array["requirementData"][$i]["service_period"];		
}
else
{
$service_period						= $offer_array["requirementData"][$i]["service_period"];		
}

$additional_disc							= "0";	

	
	
			$dataOrderProducts1["order_pros_id"] 	  = $order_pros_id;
			$dataOrderProducts["order_id"] 		  	  = $orders_id;
			$dataOrderProducts["pro_id"] 		  	  = $pro_id;
			$dataOrderProducts["manufacturers_id"] 	  = "0";//$manufacturers_id;
			$dataOrderProducts["proidentry"]	  	  = $proidentry;
			$dataOrderProducts["additional_disc"]  	  = "0";//$additional_disc;
			$dataOrderProducts["group_id"] 		  	  = "0";//$group_id;
			$dataOrderProducts["qty_attDset_id"]      = "0";//$qty_attDset_id;
			$dataOrderProducts["customers_id"]	  	  = "0";//$customers_id;
			$dataOrderProducts["pro_model"] 	  	  = $pro_model;
			$dataOrderProducts["hsn_code"] 	  	  	  = $hsn_code;
			$dataOrderProducts["pro_name"] 		  	  = $pro_name;
			$dataOrderProducts["pro_price"] 	  	  = $proPrice;
			$dataOrderProducts["pro_final_price"]  	  = $proFinalPrice;// calculate final price - coupon amount 
			$dataOrderProducts["wrap_cost"] 		  = "0.00";//$wrap_cost;
			$dataOrderProducts["Pro_tax"] 		  	  = $protaxcost;
			$dataOrderProducts["GST_percentage"]   	  = $GST_percentage;
			$dataOrderProducts["pro_quantity"] 	      = $pro_quantity;
			$dataOrderProducts["coupon_id"] 	      = $discountValue;// in percentage 
			$dataOrderProducts["pro_coupon_amount"]   = 0.00;
			$dataOrderProducts["pro_text"]   		  = '0';
			$dataOrderProducts["pro_ret_remarks"]     = '0';
			$dataOrderProducts["pro_ret_qty"]   	  = '0';
			$dataOrderProducts["pro_ret_amt"]   	  = '0';
			$dataOrderProducts["barcode"]   		  = '0';
			$dataOrderProducts["freight_amount"]   	  = $freight_amount;
			//$proDiscount							  = "0";//$discount;
			$dataOrderProducts["pro_discount_amount"] = $proDiscount;
//			$dataOrderProducts["additional_disc"] 	  = $additional_disc;
			
			$dataOrderProducts["proAttribute_Cost"]   = "0";//$rowShopCartProduct->attributesetPrice;
			$dataOrderProducts["order_pro_status"]    = "Pending";	
			$dataOrderProducts["service_period"]      = $service_period;
 
//echo "<br>order_pros_id". $order_pros_id;
/*if($order_pros_id!='' && $order_pros_id!='0')
{*/
$order_products_table						= DB::table('tbl_order_product')->where('order_pros_id', $order_pros_id)->update($dataOrderProducts);// exit;



 			$dataOrderProductsdisc["orderid"] 		  	  = $orders_id;
			$dataOrderProductsdisc["proid"] 		  	  = $pro_id;	
 			$dataOrderProductsdisc["discount_amount"] 	  = $proDiscount;
			$dataOrderProductsdisc["discount_percent"] 	  = $discountValue;	
			$dataOrderProductsdisc["show_discount"] 	  = "No";//$pro_id;	

//insert in prowise discount table
$lead_products_table						= DB::table('prowise_discount')->insert($dataOrderProductsdisc);	
//print_r($dataOrderProducts1);
//print_r($dataOrderProducts); //exit;
/*}
else
{
$order_products_table						= DB::table('tbl_order_product')->insert($dataOrderProducts);			
}*/ 
 
}			
 
		$dataArray["PO_NO"] 				= $buyerPoNumber;
		$dataArray["performa_notes"] 		= $performa_notes;
		$dataArray["Payment_Terms"]			= $Payment_Terms;		
		$dataArray["Special_Ins"] 			= $Special_Ins;
		$dataArray["branch_sel"] 			= $branch_sel;
		$dataArray["bank_sel"] 				= $bank_sel;
		$dataArray["PO_From"] 				= $Prepared_by;
		$dataArray["pi_generated_date"] 	= $piDate;
		$dataArray["Cus_Com_Name"] 			= $billing_company;
		$dataArray["Buyer_Name"] 			= $customers_name;
		$dataArray["O_Id"] 					= $orders_id;
		$dataArray["pi_status"] 			= "pending";
		$dataArray["save_send"] 			= $save_send;	
		
		$buyer_updated 						= $shipping_address;//$shipping_address." ".$shipping_city." ".$shipping_state." ".$shipping_zip_code;
		
		$dataArray["Buyer"] 				= $buyer_updated;//$_REQUEST["Buyer"];
		$dataArray["Buyer_Tel"] 			= $customers_contact_no;
		$dataArray["Buyer_Fax"] 			= "0";
		$dataArray["Buyer_Mobile"] 			= $customers_contact_no;
		$dataArray["Buyer_Email"] 			= $customers_email;
		$dataArray["buyer_gst"] 			= $gstNumber;
		$dataArray["Prepared_by"] 			= $Prepared_by;
		$dataArray["advance_received"] 		= $advancedRecieved;
		$dataArray["Special_Ins"] 			= $deliveryNote;
		$dataArray["PO_path"] 				= $po_path;
	
		
//echo $_FILES["PO_path"]["name"] ;
	/*	if($_FILES["PO_path"]["name"] != "")
		{
			$filePath = $s->fileUpload("uploads/do/", "PO_path", "PO_");
			if($filePath != -1)
			{
				 $dataArray["PO_path"] = $filePath;
				// exit;
			}
			else 
			{
					$msg = 'Pl Check Invoice Name! Invoice Not Uploaded';
			}
		}	*/	
//"prev :-". $previous_proforma_id_check	= performa_invoice_id($orders_id);//exit;		
$edit_proforma_id							= DB::table('tbl_performa_invoice')->where('O_Id', $orders_id)->update($dataArray);// exit;

//notification
$send_cord="send";
 	if($send_cord=='send') {
	$finance_name="53";//finance@stanlay.com";
    $acc_manager_phone 			= account_manager_phone($Prepared_by);
	$acc_manager_name_sms		= admin_name($Prepared_by);
  	$acc_manager_name_finance	= admin_name($finance_name);
 	$acc_manager_phone_finance 	= "917053062400";//$s->account_manager_phone($finance_name); 

	$finance_name_sanjeev="123";//finance@stanlay.com";
//	$acc_manager_phone_sanjeev 			= $s->account_manager_phone($PO_From);
//	$acc_manager_name_sms_sanjeev		= $s->admin_name($Prepared_by);
 	$acc_manager_name_finance_sanjeev	= admin_name($finance_name_sanjeev);
 	$acc_manager_phone_finance_sanjeev 	= "917053062400";//"91".$s->account_manager_phone($finance_name_sanjeev); 

	$finance_name_sha="241";//finance@stanlay.com";
//	$acc_manager_phone_sanjeev 			= $s->account_manager_phone($PO_From);
//	$acc_manager_name_sms_sanjeev		= $s->admin_name($Prepared_by);
 	$acc_manager_name_finance_sha	= admin_name($finance_name_sha);
 	$acc_manager_phone_finance_sha 	= "917053062400";//"91".$s->account_manager_phone($finance_name_sha); 

		
	/*************whatsapp send to AC MANAGER***********/
//			echo "Whatsaaaaap";		  
//echo 			$tl_name;
//echo 	$tl_phone;
//$acc_manager_phone=$acc_manager_phone;
//$acc_manager_phones=$tl_phone;//"919811169723";
//$acc_manager_phones="919811169723";
//$acc_manager_phones="918218750790";
$acc_manager_phone_finance= "917053062400";//"919319396589";
//$acc_manager_names = $tl_name;
$message_ac_managers_fin='Dear  *'.$acc_manager_name_finance.'*, '. "\r\n".
'*PI of Offer #* :*'.$pcode.'*, '." has been received for Approval.". "\r\n".
'*Customer Name :* '.$billing_company.' '." \r\n".
'*A/C Manager. :* '.admin_name($Prepared_by).' '." \r\n".
'Thanking You,'."\r\n".
'ACL Stanlay Team'."\r\n".
'Helpline: 011-41406926'."\r\n".
'Web : www.stanlay.in'."\r\n".
'-----------------------------------------------------------'."\r\n".
'ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
//echo $wp=whatsapp_msg($acc_manager_phone_finance,$message_ac_managers_fin);
//print_r($wp);
 
 
/*Sanjeev whatsapp starts */ 
// $tl_name_sanjeev="Sanjeev";
// $acc_manager_names_sanjeev = $tl_name_sanjeev;
// $acc_manager_phone_finance_sanjeev="";
$message_ac_managers_fin_sanjeev='Dear  *'.$acc_manager_name_finance_sanjeev.'*, '. "\r\n".
'*PI of Offer #* :*'.$pcode.'*, '." has been received for Approval.". "\r\n".
'*Customer Name :* '.$billing_company.' '." \r\n".
'*A/C Manager. :* '.admin_name($Prepared_by).' '." \r\n".
'Thanking You,'."\r\n".
'ACL Stanlay Team'."\r\n".
'Helpline: 011-41406926'."\r\n".
'Web : www.stanlay.in'."\r\n".
'-----------------------------------------------------------'."\r\n".
'ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
// whatsapp_msg($acc_manager_phone_finance_sanjeev,$message_ac_managers_fin_sanjeev);

/*Sanjeev whatsapp ends */

/*Shashank whatsapp starts */

$message_ac_managers_fin_sha='Dear  *'.$acc_manager_name_finance_sha.'*, '. "\r\n".
'*PI of Offer #* :*'.$pcode.'*, '." has been received for Approval.". "\r\n".
'*Customer Name :* '.$billing_company.' '." \r\n".
'*A/C Manager. :* '.$Prepared_by.' '." \r\n".
'Thanking You,'."\r\n".
'ACL Stanlay Team'."\r\n".
'Helpline: 011-41406926'."\r\n".
'Web : www.stanlay.in'."\r\n".
'-----------------------------------------------------------'."\r\n".
'ACL Stanlay is India'."'".'s largest engineering test & measurement equipment supplier'."";
// whatsapp_msg($acc_manager_phone_finance_sha,$message_ac_managers_fin_sha);
/*Shashank whatsapp ends */
//function to send whatsapp 06-Jul-2022	


// exit;
	//$defaultSMS_phone 		= "7701821170";//sandeep:----jyoti9871190715
/*			
$sms_msg="Dear $acc_manager_name_finance
PI of Offer No. $pcode has been received for approval.";
 

		$dataTempOrder_send["save_send"]= "yes";
			$result 	= $s->editRecord('tbl_performa_invoice', $dataTempOrder_send,'O_Id' ,$pcode);    
			$to 		= "finance@stanlay.com".","."purchaseaccounts@stanlay.com".","."accounts@stanlay.com" ; 
			$to1 		= $s->admin_email($_REQUEST['Prepared_by']); 
 			$from 		= $s->admin_email($_REQUEST['Prepared_by']);
			$subject 	= 'PI of offer No. #'.$pcode.' has been received for approval!';
			$filename 	= 'pi_email_preview.php';
			$s->sendmail($to,$from,$subject,$filename);	
			$dataTempOrder_send["save_send"]= "yes";
			$result 	= $s->editRecord('tbl_performa_invoice', $dataTempOrder_send,'O_Id' ,$pcode); 
			 */ 
			
 
				
} 
//notofication ends

//insert data in lead table	
//exit;
if($edit_proforma_id || $order_products_table)
{
//"performa invoice id::".$inserted_proforma_id 						= DB::table('tbl_performa_invoice')->insertGetId($dataArray);	 //exit;
   $msg = array("msg"=>"true","result"=>$edit_proforma_id);
}
else
{
   $msg = array("msg"=>"true","result"=>$edit_proforma_id);
}
$total_order_cost							= $offer_array["finalFreightData"]["grandTotal"];;
$dataArrayOrdertotal["freight_amount"]   	  	= $freightValue;
//$dataArrayOrder["Special_Ins"] 			= $freightValue;
//$gstValue									= $offer_array["finalFreightData"]["gstValue"];
$dataArrayOrdertotal["offer_probability"] 		= "4";
$dataArrayOrdertotal["total_order_cost"] 		= $total_order_cost;
$dataArrayOrdertotal["total_order_cost_new"] 	= $total_order_cost;
$order_products_table						= DB::table('tbl_order')->where('orders_id', $orders_id)->update($dataArrayOrdertotal);


$ArrayData_enq['enq_stage'] = "4";
	
	     $result_order_status= DB::table('tbl_web_enq_edit')
            ->where('order_id', $orders_id)
            ->update($ArrayData_enq);
    
	  //$insId = DB::table('tbl_enq_remarks')->insert($fileArray_tbl_enq_remarks); 
       return response()->json([            
        'message' => $msg, 
        ]);  
		
					
}


#############################################Leads#############################################
//Proforma Invoice listing	
 public function proforma_invoice_listing(Request $request)
    {
       
//$AdminLoginID_SET = Auth::user()->id;
	$acc_manager        		= $request->acc_manager;
	$OrderNo  					= $request->order_no;
	$comp_name_search	  		= $request->comp_name_search;
	$datevalid_from 			= $request->datevalid_from;
	$datevalid_to				= $request->datevalid_to;
	$mobile_search				= $request->mobile_search;
//	$payment_method				= $request->payment_method;
	$customers_name				= $request->customers_name;
	$customers_contact_no 		= $request->customers_contact_no;
	$pro_name 		  			= $request->pro_name;
	$sort_by	 				= $request->sort_by;
	$offer_type 				= $request->offer_type;	
	$search_by					= $request->search_by;	
	$pro_name					= $request->pro_name;	
	$min_value					= $request->min_value;
	$max_value					= $request->max_value;
	$save_send					= $request->save_send;			
	
	
		
if($sort_by=='date_asc')
{
	$order_by="pi_generated_date";
	$order="asc";
}

else
{
		$order_by="pi_generated_date";
	$order="desc";
}
if($sort_by=='date_desc')
{
		$order_by="pi_generated_date";
		$order="desc";
}

/*if($sort_by=='amt_desc')
{
		$order_by="total_order_cost";
		$order="asc";
}

if($sort_by=='amt_asc')
{
		$order_by="total_order_cost";
		$order="desc";
}*/


if($OrderNo!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  O_Id = '$OrderNo'";
	}
else
{
	$order_no_search="";
}



if($save_send!='' )
	{
	//$orders_status='Pending';
	$save_send_search=" and  save_send = '$save_send'";
	}
else
{
	$save_send_search="";
}
 
if($comp_name_search!='' && $comp_name_search!='0')
	{
	//$orders_status='Pending';
//	$comp_name_search_search="and  t1.customers_id = '$comp_name_search'";
	$CateParent_search="and  Cus_Com_Name LIKE '%$comp_name_search%'";
	}
	else
	{
		$CateParent_search ="";
	}

if($acc_manager!='' && $acc_manager!='0')
	{
	//$orders_status='Pending';
	$acc_manager_search="and Prepared_by='$acc_manager'";
	}
	else
	{
		$acc_manager_search="";
	}


//c name
	if($customers_name!='')
	{
	//$orders_status='Pending';
	$customers_name_search=" and Buyer_Name like '%$customers_name%'";
	}
	else
	{
			$customers_name_search=" ";
	}
//c NO
	if($customers_contact_no!='')
	{
	//$orders_status='Pending';
	$customers_contact_no_search=" and Buyer_Mobile='$customers_contact_no'";
	}
	else
	{
	$customers_contact_no_search=" ";		
	}



if($datevalid_from!='' && $datevalid_to!='')
	{
		
$date_range_search=" AND (date( pi_generated_date ) BETWEEN '$datevalid_from' AND '$datevalid_to')";
	}

else
{
	$date_range_search ="";
}
if($mobile_search!='')
	{
		
$mobile_search_search=" AND Buyer_Mobile = '$mobile_search' ";
	}
else
{
	$mobile_search_search="";
}



	if($search_by!='' && $search_by!='0')
	{
		
$search_by_keyword= " AND Buyer_Name like '%".$search_by."%' OR Buyer_Email like '%".$search_by."%' OR Buyer_Mobile like '%".$search_by."%' OR Cus_Com_Name like '%".$search_by."%' ";
	}

else
{
$search_by_keyword="";
}


/*if ($min_value != '' && $max_value != '')
{
 
    $estimated_value_search = " and  total_order_cost_new BETWEEN $min_value AND $max_value ";
}
else
{
	   $estimated_value_search = " ";
}*/


$searchRecord 	= " $customers_name_search  $customers_contact_no_search $acc_manager_search  $order_no_search $save_send_search $date_range_search $mobile_search_search $search_by_keyword "; 
	if(strlen(trim($order))<=0)
	{
	$order 	 	 = 'desc';
	}
	if(strlen(trim($order_by))<=0)
	{
	$order_by 	 = 'orders_id';
	}	
	
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
	
	$from = (($page * $max_results) - $max_results);
 
		if($datevalid_from!='')
		{
			$fromDate 	  = $s->getDateformate($datevalid_from,'mdy','ymd','-');
			$datevalid_from = "";
		}
		else
		{
			$fromDate = '';
		}
		if($datevalid_to!= '')
		{
			$toDate	  	  = $s->getDateformate($datevalid_to,'mdy','ymd','-');
			$datevalid_to = "";
		}
		else
		{
			$toDate	= "";
		}
 

 $sql_offer= "SELECT 
 tbl_performa_invoice.pi_id as performa_invoice_no,
 tbl_performa_invoice.O_Id as order_no,
 tbl_performa_invoice.PO_NO as buyerPoNumber, 
 tbl_performa_invoice.buyer_gst as gstNumber,
 tbl_performa_invoice.PO_Due_Date as po_due_date,
 tbl_performa_invoice.Payment_Terms,
 tbl_performa_invoice.Special_Ins as delivery_notes,
 tbl_performa_invoice.PO_path as po_upload_path,
 tbl_performa_invoice.PO_Date as po_date,
 tbl_performa_invoice.pi_generated_date,
 tbl_performa_invoice.Cus_Com_Name as customer_company_name,
 tbl_performa_invoice.Buyer_Name as customer_name,
 tbl_performa_invoice.Buyer as customer_address,
 tbl_performa_invoice.Buyer_Mobile as customer_mobile,
 tbl_performa_invoice.Buyer_Email as customer_email,
  tbl_performa_invoice.pi_status,
 tbl_performa_invoice.Prepared_by as createdBy, 
 tbl_performa_invoice.branch_sel as company_branch_id,
 tbl_performa_invoice.bank_sel as bank_id,
 tbl_pi_comment.comment,
    tbl_pi_comment.pi_status AS pi_comment_status,
    tbl_pi_comment.comment_date,
    tbl_pi_comment.comment_by
FROM tbl_performa_invoice  
LEFT JOIN tbl_pi_comment   
    ON tbl_performa_invoice.O_Id = tbl_pi_comment.order_id 
    AND tbl_pi_comment.comment_date = (
        SELECT MAX(comment_date) 
        FROM tbl_pi_comment 
        WHERE order_id = tbl_performa_invoice.O_Id
    )	
WHERE 1=1
   $searchRecord
GROUP BY 
    tbl_performa_invoice.pi_id
ORDER BY 
    $order_by $order
LIMIT $from, $max_results";
$result_offer 	=  DB::select(($sql_offer));					

//$orders_id		=  $result_offer[0]->orders_id;

$sql_offer_paging="SELECT 
 tbl_performa_invoice.pi_id as performa_invoice_no,
 tbl_performa_invoice.O_Id as order_no,
 tbl_performa_invoice.PO_NO as buyerPoNumber, 
 tbl_performa_invoice.buyer_gst as gstNumber,
 tbl_performa_invoice.PO_Due_Date as po_due_date,
 tbl_performa_invoice.Payment_Terms,
 tbl_performa_invoice.Special_Ins as delivery_notes,
 tbl_performa_invoice.PO_path as po_upload_path,
 tbl_performa_invoice.PO_Date as po_date,
 tbl_performa_invoice.pi_generated_date,
 tbl_performa_invoice.Cus_Com_Name as customer_company_name,
 tbl_performa_invoice.Buyer_Name as customer_name,
 tbl_performa_invoice.Buyer as customer_address,
 tbl_performa_invoice.Buyer_Mobile as customer_mobile,
 tbl_performa_invoice.Buyer_Email as customer_email,
  tbl_performa_invoice.pi_status,
 tbl_performa_invoice.Prepared_by as createdBy, 
 tbl_performa_invoice.branch_sel as company_branch_id,
 tbl_performa_invoice.bank_sel as bank_id,
 tbl_pi_comment.comment,
    tbl_pi_comment.pi_status AS pi_comment_status,
    tbl_pi_comment.comment_date,
    tbl_pi_comment.comment_by
FROM tbl_performa_invoice  
LEFT JOIN tbl_pi_comment   
    ON tbl_performa_invoice.O_Id = tbl_pi_comment.order_id 
    AND tbl_pi_comment.comment_date = (
        SELECT MAX(comment_date) 
        FROM tbl_pi_comment 
        WHERE order_id = tbl_performa_invoice.O_Id
    )	
WHERE 1=1
   $searchRecord
GROUP BY 
    tbl_performa_invoice.pi_id
ORDER BY 
    $order_by $order";

//echo "<br><br>".$sql_lead = "Select * FROM tbl_lead where  tbl_lead.deleteflag='active' $searchRecord GROUP BY tbl_lead.id ORDER BY $order_by  $order  LIMIT $from, $max_results"; //exit;
$result_offer_paging 		=  DB::select(($sql_offer_paging));					
$offer_num_rows				= count($result_offer_paging); 	  
//$max_estimated_value_offer	= max_estimated_value_offer();
 
//return response()->json(['profile' => json_decode($result_offer)]);

	return response()->json([ 
			'pi_data' => $result_offer,
			'num_rows_count' => $offer_num_rows,
			//'max_estimated_value_offer'=>$max_estimated_value_offer
		]);
		
}

//create delivery order

public function create_delivery_order(Request $request)
{
$do_array					= $request->all();
/*echo "<pre>";
print_r($do_array);*/
$orders_id					= $do_array["orders_id"]; 	
$PO_From					= $do_array["created_by"]; 	
$D_Order_Date				= $do_array["date_of_delivery_order"]; 	

//buyer details
$Cus_Com_Name				= $do_array["delivery_order_data"]["customerCompany"];
$Buyer_Name					= $do_array["delivery_order_data"]["buyerName"];
$buyer_address				= $do_array["delivery_order_data"]["address"];
$buyer_pincode				= $do_array["delivery_order_data"]["pincode"];
$buyer_city					= $do_array["delivery_order_data"]["city"];
$buyer_state				= $do_array["delivery_order_data"]["state"];
$buyer_country				= $do_array["delivery_order_data"]["country"];
$Buyer_Tel					= $do_array["delivery_order_data"]["telephone"];
$Buyer_Mobile				= $do_array["delivery_order_data"]["mobile"];
$Buyer_Email				= $do_array["delivery_order_data"]["email"];
$Buyer_CST					= $do_array["delivery_order_data"]["gst_delivery"];


//consignee details
$Con_Com_Name				= $do_array["consignee_details_data"]["customerCompany"];
$Con_Name					= $do_array["consignee_details_data"]["buyerName"];
$consignee_address			= $do_array["consignee_details_data"]["address"];
$con_pincode				= $do_array["consignee_details_data"]["pincode"];
$con_city					= $do_array["consignee_details_data"]["city"];
$con_state					= $do_array["consignee_details_data"]["state"];
$con_country				= $do_array["consignee_details_data"]["country"];
$Con_Tel					= $do_array["consignee_details_data"]["telephone"];
$Con_Mobile					= $do_array["consignee_details_data"]["mobile"];
$Con_Email					= $do_array["consignee_details_data"]["email"];
$Con_CST					= $do_array["consignee_details_data"]["gst_consignee"];


//po details
$PO_Due_Date				= $do_array["purchase_order_details"]["po_due_date"];
//$PO_Due_Date				= $do_array["purchase_order_details"]["PO_Due_Date"];
$PO_NO						= $do_array["purchase_order_details"]["po_number"];
$PO_Date					= $do_array["purchase_order_details"]["po_creation_date"];
$Payment_Terms				= $do_array["purchase_order_details"]["payment_terms"];
$PO_path					= $do_array["purchase_order_details"]["upload_po"];

$Tax_Per					= $do_array["price_and_warranty_details"]["tax_percentage"];
$Tax_Stat					= $do_array["price_and_warranty_details"]["price_inclusive_of_taxes"];
$delivery_offer_warranty	= $do_array["price_and_warranty_details"]["warranty"];
$invoicing_instruction		= $do_array["price_and_warranty_details"]["send_invoice_by"];
$delay_reason				= $do_array["price_and_warranty_details"]["delay_reason"];
$Special_Ins				= $do_array["price_and_warranty_details"]["special_instruction"];
$special_invoicing_ins		= $do_array["price_and_warranty_details"]["special_invoicing_instruction"];
$mode_of_dispatch			= $do_array["dispatch_details"]["mode_of_dispatch"];
$dispatch_time				= $do_array["dispatch_details"]["dispatch_time"];
$freight					= $do_array["dispatch_details"]["freight"];
$freight_value				= "0";//$do_array["dispatch_details"]["freight_value"];
$insurance					= $do_array["dispatch_details"]["insurance"];

$ctr						= count($do_array["product_information_data"]);
$Prepared_by				=  $do_array["created_by"];
$offer_type					=  $do_array["offer_type"];
$Octroi_Value				=  "No";
$Octroi_Value_Rs			=  "0";
//$do_array["date_of_delivery_order"];

//print_r($do_array["date_of_delivery_order"]);


/*		if($_FILES["PO_path"]["name"] != "")
			{
				$filePath = $s->fileUpload("uploadscrm/do/", "PO_path", "DO_");
				if($filePath != -1)
				{
					 $dataArray["PO_path"] = $filePath;
					// exit;
				}
			else {
					$msg = 'Pl Check Invoice Name! Invoice Not Uploaded';
			}
			}		*/
		
		$filePath  = "/uploads/test.jpg";
		
		$dataArray["O_Id"] 					= $orders_id;
		$dataArray["PO_NO"] 				= $PO_NO;
		$dataArray["PO_Value"]				= "0";//addslashes($_REQUEST["PO_Value"]);
		$dataArray["PO_Due_Date"]			= $PO_Due_Date;
		$dataArray["Payment_Terms"]			= $Payment_Terms;		
		$dataArray["Special_Ins"] 			= $Special_Ins;
		$dataArray["do_type"] 				= $offer_type;
		$dataArray["invoicing_instruction"] = $invoicing_instruction;
		$dataArray["special_invoicing_ins"] = $special_invoicing_ins;
		
		$dataArray["PO_Date"] 				= $PO_Date;
		$dataArray["PO_From"] 				= $PO_From;
		$dataArray["D_Order_Date"] 			= $D_Order_Date;
		$dataArray["Cus_Com_Name"] 			= $Cus_Com_Name;
		$dataArray["Con_Com_Name"] 			= $Con_Com_Name;
		$dataArray["Buyer_Name"] 			= $Buyer_Name;
		$dataArray["Con_Name"] 				= $Con_Name;
		$dataArray["Buyer"] 				= $buyer_address;
		$dataArray["PO_path"] 				= $PO_path;
	
		$dataArray["buyer_country"] 		= ($buyer_country);
		$dataArray["buyer_state"] 			= ($buyer_state);
		$dataArray["buyer_city"] 			= ($buyer_city);
		$dataArray["buyer_pincode"] 		= ($buyer_pincode);
		
		
		$dataArray["con_country"] 			= ($con_country);
		$dataArray["con_state"] 			= ($con_state);
		$dataArray["con_city"] 				= ($con_city);
		$dataArray["con_pincode"] 			= ($con_pincode);
		$dataArray["Consignee"] 			= ($consignee_address);		
		
		
		
//		$dataArray["Consignee"] 			= $addmore_consignee"]["0"];//$Consignee"];
		$dataArray["Buyer_Tel"] 			= ($Buyer_Tel);
		$dataArray["Con_Tel"] 				= ($Con_Tel);
		$dataArray["Buyer_Fax"] 			= "0";//($Buyer_Fax);
		$dataArray["Con_Fax"] 				= "0";//($Con_Fax);
		$dataArray["Buyer_Mobile"] 			= ($Buyer_Mobile);
		$dataArray["Con_Mobile"] 			= ($Con_Mobile);
		$dataArray["Buyer_Email"] 			= ($Buyer_Email);
		$dataArray["Con_Email"] 			= ($Con_Email);
		$dataArray["Buyer_CST"] 			= ($Buyer_CST);
		$dataArray["Con_CST"] 				= ($Con_CST);
		$dataArray["Tax_Per"] 				= ($Tax_Per);
		$dataArray["Tax_C_Form"] 			= "0";//$Tax_C_Form"];
		$dataArray["Tax_Stat"] 				= ($Tax_Stat);
		$dataArray["Dispatch"] 				= ($dispatch_time);
		$dataArray["Delivery"] 				= ($mode_of_dispatch);
		$dataArray["Freight"] 				= ($freight_value);
		$dataArray["Freight_amount"] 		= ($freight_value);
		
		$dataArray["Octroi_Value"] 			= ($Octroi_Value);
		$dataArray["Octroi_Value_Rs"] 		= ($Octroi_Value_Rs);
		$dataArray["Insurance"] 			= ($insurance);
		$dataArray["delay_reason"] 			= ($delay_reason);
		$dataArray["Prepared_by"] 			= ($Prepared_by);
		$dataArray["delivery_offer_warranty"] = ($delivery_offer_warranty);


if($Payment_Terms=='7')
		{
			$dataArray_order_status["orders_status"] 			= "Order Closed";//$_REQUEST["Prepared_by"];
			$dataArray_order_status["offer_probability"]		= "7";			
			$dataArray_order_status["offer_warranty"] 			= $delivery_offer_warranty;//$_REQUEST["Prepared_by"];
			
        $result_order_status= DB::table('tbl_order')
            ->where('orders_id', $orders_id)
            ->update($dataArray_order_status);			
			
		
			//$result_order_status    =  $s->editRecord('tbl_order', $dataArray_order_status,"orders_id",$pcode); 
//changed on 01-aug-2020			
	$ArrayData_enq['enq_stage'] = "7";
	
	     $result_order_status= DB::table('tbl_web_enq_edit')
            ->where('order_id', $orders_id)
            ->update($ArrayData_enq);
	
	//$s->editRecord('tbl_web_enq_edit',$ArrayData_enq,'order_id',$pcode);

			
		}
		else
		{
			$dataArray_order_status["orders_status"] 			= "Confirmed";//$_REQUEST["Prepared_by"];
//			$dataArray_order_status["offer_warranty"] 			= addslashes($_REQUEST["delivery_offer_warranty"]);//$_REQUEST["Prepared_by"];
			$dataArray_order_status["offer_probability"]		= "6";			
			
			
			   $result_order_status= DB::table('tbl_order')
            ->where('orders_id', $orders_id)
            ->update($dataArray_order_status);			
			
		
			$ArrayData_enq['enq_stage'] = "6";
			 $result_order_status= DB::table('tbl_web_enq_edit')
					->where('order_id', $orders_id)
					->update($ArrayData_enq);
			
		}



"prev DO id:".$previous_do_id_check		= delivery_order_id($orders_id);  //exit;	
	
if($previous_do_id_check=='0')
{
     "delivery order id::".$inserted_do_id 		= DB::table('tbl_delivery_order')->insertGetId($dataArray);	 //exit;
	  
for($i=0; $i<$ctr; $i++)
{
	
$pro_id							= $do_array["product_information_data"][$i]["pro_id"];
$Pro_tax						= $do_array["product_information_data"][$i]["Pro_tax"];
$hsn_code						= $do_array["product_information_data"][$i]["hsn_code"];
$pro_name						= $do_array["product_information_data"][$i]["pro_name"];
$customer_product_name			= $do_array["product_information_data"][$i]["customer_product_name"];	
$pro_model						= $do_array["product_information_data"][$i]["pro_model"];
$pro_price						= $do_array["product_information_data"][$i]["pro_price"];


$proidentry						= $do_array["product_information_data"][$i]["proidentry"];
$pro_quantity					= $do_array["product_information_data"][$i]["pro_quantity"];
$order_pros_id					= $do_array["product_information_data"][$i]["order_pros_id"];
$GST_percentage					= $do_array["product_information_data"][$i]["GST_percentage"];
$service_period					= $do_array["product_information_data"][$i]["service_period"];
$service_period_id				= $do_array["product_information_data"][$i]["service_period_id"];
$discount_percentage			= $do_array["product_information_data"][$i]["discount_percentage"];
$pro_discount_amount			= $do_array["product_information_data"][$i]["pro_discount_amount"];	
$special_instruction			= $do_array["product_information_data"][$i]["special_instruction"];	


$dataArray1["ItemCode"] 		= addslashes($pro_model);
$dataArray1["pro_name"]			= addslashes($pro_name);
$dataArray1["Description"]		= addslashes($customer_product_name);
$dataArray1["Quantity"]			= addslashes($pro_quantity);
$dataArray1["Price"]			= addslashes($pro_price);
$dataArray1["S_Inst"]			= "0";//addslashes($SQLDOProductROW2->do_type);			
$dataArray1["service_period"]	= addslashes($service_period);			
$dataArray1["service_period_id"]= addslashes($service_period_id);			
$dataArray1["OID"] 				= $orders_id;
$dataArray1["pro_id"] 			= addslashes($pro_id);
$dataArray1["hsn_code"] 		= addslashes($hsn_code);
$dataArray1["PStatus"] 			= "active";

 "delivery order id::".$inserted_do_products_id 		= DB::table('tbl_do_products')->insertGetId($dataArray1);	 //exit;
}	  
 
	  
 $msg = array("msg"=>"true","do_id"=>$inserted_do_id,"do_products_id"=>$inserted_do_products_id);
}
else
{
	
	
//for($i=0; $i<$ctr; $i++)
//{
//	
//echo "<br>".$pro_id=$do_array["product_information_data"][$i]["pro_id"];
//$Pro_tax						= $do_array["product_information_data"][$i]["Pro_tax"];
//$hsn_code						= $do_array["product_information_data"][$i]["hsn_code"];
//$pro_name						= $do_array["product_information_data"][$i]["pro_name"];
//$customer_product_name			= $do_array["product_information_data"][$i]["customer_product_name"];	
//$pro_model						= $do_array["product_information_data"][$i]["pro_model"];
//$pro_price						= $do_array["product_information_data"][$i]["pro_price"];
//
//
//$proidentry						= $do_array["product_information_data"][$i]["proidentry"];
//$pro_quantity					= $do_array["product_information_data"][$i]["pro_quantity"];
//$order_pros_id					= $do_array["product_information_data"][$i]["order_pros_id"];
//$GST_percentage					= $do_array["product_information_data"][$i]["GST_percentage"];
//$service_period					= $do_array["product_information_data"][$i]["service_period"];
//$discount_percentage			= $do_array["product_information_data"][$i]["discount_percentage"];
//$pro_discount_amount			= $do_array["product_information_data"][$i]["pro_discount_amount"];	
//
//$special_instruction			= $do_array["product_information_data"][$i]["special_instruction"];	
//
//
//			$dataArray1["ItemCode"] 				= addslashes($pro_model);
//			$dataArray1["pro_name"]					= addslashes($pro_name);
//			$dataArray1["Description"]				= addslashes($customer_product_name);
//			$dataArray1["Quantity"]					= addslashes($pro_quantity);
//			$dataArray1["Price"]					= addslashes($pro_price);
//			$dataArray1["S_Inst"]					= "0";//addslashes($SQLDOProductROW2->do_type);			
//			$dataArray1["service_period"]			= addslashes($service_period);			
//			$dataArray1["OID"] 						= $orders_id;
//			$dataArray1["pro_id"] 					= addslashes($pro_id);
//			$dataArray1["hsn_code"] 				= addslashes($hsn_code);
//			$dataArray1["PStatus"] 					= "active";
//
// 
////$result    											=  $s->insertRecord('tbl_do_products', $dataArray1);  //exit;
//
//  "delivery order id::".$inserted_do_products_id 		= DB::table('tbl_do_products')->insertGetId($dataArray1);	 //exit;
//}	

$inserted_do_products_id								= "0";
	
 $msg = array("msg"=>"false","do_id"=>$previous_do_id_check,"msg2"=>"DO already created for this offer #.","do_products_id"=>$inserted_do_products_id);


}
			
  return response()->json([            
        'message' => $msg, 
		// 'result' => $result_enq, 
        ]);

}



 public function get_delivery_order_data(Request $request)
    {
if(isset($request->orders_id)){             
$orders_id					=	$request->orders_id;
$sql_do_details	= "SELECT 
DO_ID,
O_Id,
PO_NO,
PO_Due_Date,
Payment_Terms,
Special_Ins,
invoicing_instruction,
special_invoicing_ins,
PO_path,
PO_Date,
D_Order_Date,
PO_From,
Cus_Com_Name  as buyer_customerCompany,
Buyer_Name as buyerName,
Buyer  as buyer_address,
Buyer_Tel  as buyer_telephone,
Buyer_Mobile  as buyer_mobilr,
Buyer_Email  as buyer_email,
Buyer_CST  as gst_delivery,
buyer_country  as buyer_country,
buyer_state  as buyer_state,
buyer_city  as buyer_city,
buyer_pincode  as buyer_pincode,
Con_Com_Name as consignee_customerCompany,
Con_Name as consignee_name,
Consignee as consignee_address,
Con_Mobile as consignee_mobile,
Con_Tel as consignee_telephone,
Con_Email as consignee_email,
con_country  as consignee_country,
con_state  as consignee_state,
con_city  as consignee_city,
con_pincode  as consignee_pincode,
Con_CST as gst_consignee,
Tax_Per,
Tax_Stat,
Dispatch,
Delivery,
Freight,
Freight_amount,
Insurance,
delay_reason,
Prepared_by,
delivery_offer_warranty,
D_Order_Date1,
do_type 
from
tbl_delivery_order where  O_Id='$orders_id'";
$do_details_data 			=  DB::select(($sql_do_details));							
$num_rows	= count($do_details_data); 	  
$product_information_data	=  do_products_list_json($orders_id);
//	exit;

/*$enq_details_data = DB::table('tbl_web_enq_edit')->where('ID',$request->id)->get(['ID','enq_id','lead_id','order_id','Cus_name','Cus_email','Cus_mob','country','city','state','ref_source','cust_segment','country','hot_productnote','hot_productnoteother','Enq_Date','enq_remark_edited','enq_stage','snooze_days','snooze_date','hot_enquiry','Cus_msg','assigned_by','remind_me','acc_manager']);          */
$msg = array("msg"=>"true","delivery_order_details"=>$do_details_data,"product_information_data"=>$product_information_data);

        }
		
else
{
$msg = array("msg"=>"false","delivery_order_details"=>"Order id is missing: please pass orders_id as parameter");
}

 return response()->json([            
            'delivery_order_data' => $msg, 
        ]);		       
    }

//edit delilvery order
public function edit_delivery_order(Request $request)
{
$do_array					= $request->all();
/*echo "<pre>";
print_r($do_array);*/
$orders_id					= $do_array["orders_id"]; 	
$PO_From					= $do_array["created_by"]; 	
$D_Order_Date				= $do_array["date_of_delivery_order"]; 	

//buyer details
$Cus_Com_Name				= $do_array["delivery_order_data"]["customerCompany"];
$Buyer_Name					= $do_array["delivery_order_data"]["buyerName"];
$buyer_address				= $do_array["delivery_order_data"]["address"];
$buyer_pincode				= $do_array["delivery_order_data"]["pincode"];
$buyer_city					= $do_array["delivery_order_data"]["city"];
$buyer_state				= $do_array["delivery_order_data"]["state"];
$buyer_country				= $do_array["delivery_order_data"]["country"];
$Buyer_Tel					= $do_array["delivery_order_data"]["telephone"];
$Buyer_Mobile				= $do_array["delivery_order_data"]["mobile"];
$Buyer_Email				= $do_array["delivery_order_data"]["email"];
$Buyer_CST					= $do_array["delivery_order_data"]["gst_delivery"];

//consignee details
$Con_Com_Name				= $do_array["consignee_details_data"]["customerCompany"];
$Con_Name					= $do_array["consignee_details_data"]["buyerName"];
$consignee_address			= $do_array["consignee_details_data"]["address"];
$con_pincode				= $do_array["consignee_details_data"]["pincode"];
$con_city					= $do_array["consignee_details_data"]["city"];
$con_state					= $do_array["consignee_details_data"]["state"];
$con_country				= $do_array["consignee_details_data"]["country"];
$Con_Tel					= $do_array["consignee_details_data"]["telephone"];
$Con_Mobile					= $do_array["consignee_details_data"]["mobile"];
$Con_Email					= $do_array["consignee_details_data"]["email"];
$Con_CST					= $do_array["consignee_details_data"]["gst_consignee"];
//po details
$PO_Due_Date				= $do_array["purchase_order_details"]["po_due_date"];
//$PO_Due_Date				= $do_array["purchase_order_details"]["PO_Due_Date"];
$PO_NO						= $do_array["purchase_order_details"]["po_number"];
$PO_Date					= $do_array["purchase_order_details"]["po_creation_date"];
$Payment_Terms				= $do_array["purchase_order_details"]["payment_terms"];
$PO_path					= $do_array["purchase_order_details"]["upload_po"];

$Tax_Per					= $do_array["price_and_warranty_details"]["tax_percentage"];
$Tax_Stat					= $do_array["price_and_warranty_details"]["price_inclusive_of_taxes"];
$delivery_offer_warranty	= $do_array["price_and_warranty_details"]["warranty"];
$invoicing_instruction		= $do_array["price_and_warranty_details"]["send_invoice_by"];
$delay_reason				= $do_array["price_and_warranty_details"]["delay_reason"];
$Special_Ins				= $do_array["price_and_warranty_details"]["special_instruction"];
$special_invoicing_ins		= $do_array["price_and_warranty_details"]["special_invoicing_instruction"];
$mode_of_dispatch			= $do_array["dispatch_details"]["mode_of_dispatch"];
$dispatch_time				= $do_array["dispatch_details"]["dispatch_time"];
$freight					= $do_array["dispatch_details"]["freight"];
$freight_value				= "0";//$do_array["dispatch_details"]["freight_value"];
$insurance					= $do_array["dispatch_details"]["insurance"];

$ctr						= count($do_array["product_information_data"]);
$Prepared_by				= $do_array["created_by"];
$offer_type					= $do_array["offer_type"];
$Octroi_Value				= "No";
$Octroi_Value_Rs			= "0";
//$do_array["date_of_delivery_order"];
//print_r($do_array["date_of_delivery_order"]);
/*		if($_FILES["PO_path"]["name"] != "")
			{
			$filePath = $s->fileUpload("uploadscrm/do/", "PO_path", "DO_");
			if($filePath != -1)
			{
				 $dataArray["PO_path"] = $filePath;
				// exit;
			}
		else {
				$msg = 'Pl Check Invoice Name! Invoice Not Uploaded';
		}
		}		*/
		
		$filePath  = "/uploads/test.jpg";
		$dataArray["O_Id"] 					= $orders_id;
		$dataArray["PO_NO"] 				= $PO_NO;
		$dataArray["PO_Value"]				= "0";//addslashes($_REQUEST["PO_Value"]);
		$dataArray["PO_Due_Date"]			= $PO_Due_Date;
		$dataArray["Payment_Terms"]			= $Payment_Terms;		
		$dataArray["Special_Ins"] 			= $Special_Ins;
		$dataArray["do_type"] 				= $offer_type;
		$dataArray["invoicing_instruction"] = $invoicing_instruction;
		$dataArray["special_invoicing_ins"] = $special_invoicing_ins;
		
		$dataArray["PO_Date"] 				= $PO_Date;
		$dataArray["PO_From"] 				= $PO_From;
		$dataArray["D_Order_Date"] 			= $D_Order_Date;
		$dataArray["Cus_Com_Name"] 			= $Cus_Com_Name;
		$dataArray["Con_Com_Name"] 			= $Con_Com_Name;
		$dataArray["Buyer_Name"] 			= $Buyer_Name;
		$dataArray["Con_Name"] 				= $Con_Name;
		$dataArray["Buyer"] 				= $buyer_address;
		$dataArray["PO_path"] 				= $PO_path;
	
		$dataArray["buyer_country"] 		= ($buyer_country);
		$dataArray["buyer_state"] 			= ($buyer_state);
		$dataArray["buyer_city"] 			= ($buyer_city);
		$dataArray["buyer_pincode"] 		= ($buyer_pincode);
		
		
		$dataArray["con_country"] 			= ($con_country);
		$dataArray["con_state"] 			= ($con_state);
		$dataArray["con_city"] 				= ($con_city);
		$dataArray["con_pincode"] 			= ($con_pincode);
		$dataArray["Consignee"] 			= ($consignee_address);		
		
		
		
//		$dataArray["Consignee"] 			= $addmore_consignee"]["0"];//$Consignee"];
		$dataArray["Buyer_Tel"] 			= ($Buyer_Tel);
		$dataArray["Con_Tel"] 				= ($Con_Tel);
		$dataArray["Buyer_Fax"] 			= "0";//($Buyer_Fax);
		$dataArray["Con_Fax"] 				= "0";//($Con_Fax);
		$dataArray["Buyer_Mobile"] 			= ($Buyer_Mobile);
		$dataArray["Con_Mobile"] 			= ($Con_Mobile);
		$dataArray["Buyer_Email"] 			= ($Buyer_Email);
		$dataArray["Con_Email"] 			= ($Con_Email);
		$dataArray["Buyer_CST"] 			= ($Buyer_CST);
		$dataArray["Con_CST"] 				= ($Con_CST);
		$dataArray["Tax_Per"] 				= ($Tax_Per);
		$dataArray["Tax_C_Form"] 			= "0";//$Tax_C_Form"];
		$dataArray["Tax_Stat"] 				= ($Tax_Stat);
		$dataArray["Dispatch"] 				= ($dispatch_time);
		$dataArray["Delivery"] 				= ($mode_of_dispatch);
		$dataArray["Freight"] 				= ($freight_value);
		$dataArray["Freight_amount"] 		= ($freight_value);
		
		$dataArray["Octroi_Value"] 			= ($Octroi_Value);
		$dataArray["Octroi_Value_Rs"] 		= ($Octroi_Value_Rs);
		$dataArray["Insurance"] 			= ($insurance);
		$dataArray["delay_reason"] 			= ($delay_reason);
		$dataArray["Prepared_by"] 			= ($Prepared_by);
		$dataArray["delivery_offer_warranty"] = ($delivery_offer_warranty);


if($Payment_Terms=='7')
		{
			$dataArray_order_status["orders_status"] 			= "Order Closed";//$_REQUEST["Prepared_by"];
			$dataArray_order_status["offer_probability"]		= "7";			
			$dataArray_order_status["offer_warranty"] 			= addslashes($_REQUEST["delivery_offer_warranty"]);//$_REQUEST["Prepared_by"];
			
        $result_order_status= DB::table('tbl_order')
            ->where('orders_id', $orders_id)
            ->update($dataArray_order_status);			
			
		
			//$result_order_status    =  $s->editRecord('tbl_order', $dataArray_order_status,"orders_id",$pcode); 
//changed on 01-aug-2020			
	$ArrayData_enq['enq_stage'] = "7";
	
	     $result_order_status= DB::table('tbl_web_enq_edit')
            ->where('order_id', $orders_id)
            ->update($ArrayData_enq);
	//$s->editRecord('tbl_web_enq_edit',$ArrayData_enq,'order_id',$pcode);
		}
		else
		{
			$dataArray_order_status["orders_status"] 			= "Confirmed";//$_REQUEST["Prepared_by"];
//			$dataArray_order_status["offer_warranty"] 			= addslashes($_REQUEST["delivery_offer_warranty"]);//$_REQUEST["Prepared_by"];
			$dataArray_order_status["offer_probability"]		= "6";			
			
			
			   $result_order_status= DB::table('tbl_order')
            ->where('orders_id', $orders_id)
            ->update($dataArray_order_status);			
			
		
			$ArrayData_enq['enq_stage'] = "6";
			 $result_order_status= DB::table('tbl_web_enq_edit')
					->where('order_id', $orders_id)
					->update($ArrayData_enq);
			
		}

///"prev DO id:".$previous_do_id_check		= delivery_order_id($orders_id);  //exit;	
//edit do details
$edit_do_details_result= DB::table('tbl_delivery_order')
            ->where('O_Id', $orders_id)
          ->update($dataArray);
  
for($i=0; $i<$ctr; $i++)
{
	
$do_pro_edit_id					= $do_array["product_information_data"][$i]["ID"];
$pro_id							= $do_array["product_information_data"][$i]["pro_id"];
$Pro_tax						= $do_array["product_information_data"][$i]["Pro_tax"];
$hsn_code						= $do_array["product_information_data"][$i]["hsn_code"];
$pro_name						= $do_array["product_information_data"][$i]["pro_name"];
$customer_product_name			= $do_array["product_information_data"][$i]["customer_product_name"];	
$pro_model						= $do_array["product_information_data"][$i]["pro_model"];
$pro_price						= $do_array["product_information_data"][$i]["price"];


//$proidentry						= $do_array["product_information_data"][$i]["proidentry"];
$pro_quantity					= $do_array["product_information_data"][$i]["quantity"];
//$order_pros_id					= $do_array["product_information_data"][$i]["ID"];
$service_period					= $do_array["product_information_data"][$i]["service_period"];
$service_period_id				= $do_array["product_information_data"][$i]["service_period_id"];
$is_service						= $do_array["product_information_data"][$i]["is_service"];	
$special_instruction			= $do_array["product_information_data"][$i]["special_instructions"];	


$dataArray1["ItemCode"] 		= addslashes($pro_model);
$dataArray1["pro_name"]			= addslashes($pro_name);
$dataArray1["Description"]		= addslashes($customer_product_name);
$dataArray1["Quantity"]			= addslashes($pro_quantity);
$dataArray1["Price"]			= addslashes($pro_price);
$dataArray1["S_Inst"]			= "0";//addslashes($SQLDOProductROW2->do_type);			
$dataArray1["service_period"]	= addslashes($service_period);			
$dataArray1["service_period_id"]	= addslashes($service_period_id);			
$dataArray1["OID"] 				= $orders_id;
$dataArray1["pro_id"] 			= addslashes($pro_id);
$dataArray1["hsn_code"] 		= addslashes($hsn_code);
$dataArray1["per_item_tax_rate"]= $Pro_tax;
$dataArray1["is_service"]		= $is_service;

$dataArray1["PStatus"] 			= "active";
//"delivery order id::".$inserted_do_products_id 		= DB::table('tbl_do_products')->insertGetId($dataArray1);	 //exit;
//edit do product details
$edit_do_details_result			= DB::table('tbl_do_products')->where('ID', $do_pro_edit_id)->update($dataArray1); 
 
}	  
	  
 $msg = array("msg"=>"true","do_id_result"=>$edit_do_details_result,"do_id"=>$orders_id);
 //$msg = array("msg"=>"false","do_id_result"=>$edit_do_details_result,"msg2"=>"DO already created for this offer #.","do_id"=>$orders_id);
  return response()->json([            
        'message' => $msg, 
		// 'result' => $result_enq, 
        ]);

}

public function sales_cycle_total(Request $request)
{
$qtr_start_date_show	= "2024-04-01";
$qtr_end_date_show		= "2025-03-31";	
/*$offer_probability		= "3";
$hot_offer				= "1";	
$acc_manager			= "99";	
$offer_type				= "product";	
$product_category		= "1";	*/


$product_category  			= $request->product_category;
$cust_segment_search  		= $request->cust_segment;
$datevalid_from 			= $request->datevalid_from;
$datevalid_to				= $request->datevalid_to;
$follow_up_datevalid_to		= $request->follow_up_datevalid_to;
$acc_manager				= $request->acc_manager;
$offer_type					= $request->offer_type;
$hot_offer					= $request->hot_offer;

if($follow_up_datevalid_to!='')
	{
		
$follow_up_datevalid_to_search=" AND (date( t1.follow_up_date ) <= '$follow_up_datevalid_to' )";
	}
	else
	{
$follow_up_datevalid_to_search="";
	}



$hot_offer_total		= sales_cycle_offer_total($qtr_start_date_show,$qtr_end_date_show,'3', '1',$acc_manager,$offer_type,$product_category,$cust_segment_search,$datevalid_to,$datevalid_from,$follow_up_datevalid_to);	
$offer_total			= sales_cycle_offer_total($qtr_start_date_show,$qtr_end_date_show,'3', '0',$acc_manager,$offer_type,$product_category,$cust_segment_search,$datevalid_to,$datevalid_from,$follow_up_datevalid_to);	
$opportunity_total		= sales_cycle_offer_total($qtr_start_date_show,$qtr_end_date_show,'4', $hot_offer,$acc_manager,$offer_type,$product_category,$cust_segment_search,$datevalid_to,$datevalid_from,$follow_up_datevalid_to);	
	
	 $msg = array("msg"=>"true",
	 "hot_offer_total"=>$hot_offer_total,
	 "offer_total"=>$offer_total,
	 "opportunity_total"=>$opportunity_total
	 );
	 
	  return response()->json([            
        'message' => $msg, 
		// 'result' => $result_enq, 
        ]);
}

public function offer_product_details(Request $request)
{
	
$order_id  							= $request->order_id;	
$offer_product_details  			= product_name_generated_with_quantity_json_tbl_order_product_listing($order_id);
$pi_product_details  			= pi_total_offer($order_id);
$msg="true";
 return response()->json([            
        'message' => $msg, 
		'offer_product_details' => $offer_product_details, 
		'offer_details' => $pi_product_details, 		
        ]);
}




public function proforma_invoice_data_for_pdf(Request $request)
{
$order_id  					= $request->order_no;
 
if($order_id!='' )
	{
	//$orders_status='Pending';
	$order_no_search=" and  O_Id = '$order_id'";
	}
else
{
	$order_no_search="";
}



$searchRecord 	= " $order_no_search "; 
	 

  $sql_pi_pdf= "SELECT 
 tbl_performa_invoice.pi_id as performa_invoice_no,
  tbl_performa_invoice.advance_received,
 tbl_performa_invoice.O_Id as order_no,
 tbl_performa_invoice.PO_NO as buyerPoNumber, 
 tbl_performa_invoice.buyer_gst as buyerGSTNumber,
 tbl_performa_invoice.PO_Due_Date as po_due_date,
 tbl_performa_invoice.Payment_Terms,
 tbl_performa_invoice.Special_Ins as delivery_notes,
 tbl_performa_invoice.PO_path as po_upload_path,
 tbl_performa_invoice.PO_Date as po_date,
 tbl_performa_invoice.pi_generated_date,
 tbl_performa_invoice.Cus_Com_Name as customer_company_name,
 tbl_performa_invoice.Buyer_Name as customer_name,
 tbl_performa_invoice.Buyer as customer_address,
 tbl_performa_invoice.Buyer_Mobile as customer_mobile,
 tbl_performa_invoice.Buyer_Email as customer_email,
  tbl_performa_invoice.pi_status,
 tbl_performa_invoice.Prepared_by as createdBy, 
 tbl_performa_invoice.branch_sel as company_branch_id,
 tbl_performa_invoice.bank_sel as bank_id,
 tbl_pi_comment.comment,
    tbl_pi_comment.pi_status AS pi_comment_status,
    tbl_pi_comment.comment_date,
    tbl_pi_comment.comment_by
FROM tbl_performa_invoice  
LEFT JOIN tbl_pi_comment   
    ON tbl_performa_invoice.O_Id = tbl_pi_comment.order_id 
    AND tbl_pi_comment.comment_date = (
        SELECT MAX(comment_date) 
        FROM tbl_pi_comment 
        WHERE order_id = tbl_performa_invoice.O_Id
    )	
WHERE 1=1
   $searchRecord";
$result_pi_pdf 	=  DB::select(($sql_pi_pdf));		

$bank_id									= $result_pi_pdf["0"]->bank_id;
$branch_id									= $result_pi_pdf["0"]->company_branch_id;
$acc_manager_id								= $result_pi_pdf["0"]->createdBy;

$proforma_invoice_product_details  			= product_name_generated_with_quantity_json_tbl_order_product_listing($order_id);
$grand_total_offer  		= pi_total_offer($order_id);

$company_bank_details			  			= company_bank_address_by_bank_id($bank_id);
$company_branch_details			  			= company_branch_details_by_branch_id($branch_id);
$admin_name			  						= admin_name($acc_manager_id);

$msg="true";
 return response()->json([            
        'message' => $msg, 
		'created_by_name' => $admin_name, 	
		'pdf_proforma_details' => $result_pi_pdf, 
		'company_bank_details' => $company_bank_details, 		
		'company_branch_details' => $company_branch_details, 				
		'proforma_invoice_product_details' => $proforma_invoice_product_details, 		
		'grand_total_offer' => $grand_total_offer, 				
		
	
        ]);
}



public function send_cord_order(Request $request)
{
    $sendGridService = new SendGridService();
    $Prepared_by = $request->Prepared_by;
    $pcode = $request->pcode;
   
    $to1        = account_manager_email($Prepared_by);
    //$to       = "gaurav@stanlay.com".","."rumit@stanlay.com";
    //$to = ['gaurav@stanlay.com', 'rumit@stanlay.com', 'chiranjeev@stanlay.com'];
    $from       = account_manager_email($Prepared_by);
    $subject    = 'New Delivery Order '.$pcode.' has been successfully Created!';
    //$filename     = 'do_email_preview.php';
 
    //return view('emails.sendcordorder');
    //exit;
 
    $email_data = [
        'subject' => 'New Delivery Order',            
        'title' => 'Hi ',            
        'heading' => 'New Delivery Order '.$pcode.' has been successfully Created!',            
        'message' => '
        <p>Regards,</p>
        <p>The Knowbuild Team</p>',    
        'link' => ''
        ];
   
    $response1 = $sendGridService->sendMail(
            "rumit@stanlay.com",
            $email_data['subject'],
            'emails.sendcordorder',
            $email_data
    );
 
    $sms_msg="New Delivery Order ".$pcode." has been successfully Created!";
 
    $response = [
        'status' => 'success',
        'message' => $sms_msg
    ];
    return response()->json($response, 200);
 
    //echo $s->sendmail($to,$from,$subject,$filename);
    //$acc_manager_phone        = $s->account_manager_phone($PO_From);
    //$defaultSMS_phone         = "8826996333";
    //
}
		

public function  qbd_table(Request $request)
    {
$pro_id			= $request->pro_id;
$offer_type			= $request->offer_type;


if($offer_type!='' && $offer_type!='0' && $offer_type!='service')
	{
	//$orders_status='Pending';
$qbd_table		= quantity_slab_max_discount_table($pro_id); 
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
	{
$qbd_table		= quantity_slab_max_discount_table_service($pro_id); 
	}


$num_rows		= count($qbd_table);
//$num_rows		= count($result_products); 	  
if($num_rows>0)	  
{
   $msg = array("msg"=>"true","products_data"=>$qbd_table);
}
else
{
  $msg = array("msg"=>"false","products_data"=>"No records found");
}

return response()->json([            
         'qbd_table' => $qbd_table, 
        ]);
}


//co. addred for performa invoice com select and while creating offer
    public function company_branch_address()
    {
        $rs_company_branch_address 	= DB::table('tbl_company_branch_address')->where('deleteflag', '=', 'active')->orderby('id','asc')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'company_branch_address' => $rs_company_branch_address, 
        ]);
    }


    public function offer_validity_master()
    {
        $rs_offer_validity_master 	= DB::table('tbl_offer_validity_master')->where('deleteflag', '=', 'active')->orderby('offer_validity_no','asc')->select('offer_validity_id','offer_validity_no','offer_validity_name')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'offer_validity' => $rs_offer_validity_master, 
        ]);
    }
	
public function gstno_check_api(Request $request)
{
$GST_NO				= $_REQUEST["gst_no"];
$our_co_GST_no		= "07AAACA0859J1ZQ";		
if($GST_NO!='URP' && $GST_NO!='urp')
{
$access_token		= get_access_token(1);
$json_url			= "https://pro.mastersindia.co/gstinDetails?access_token=$access_token&user_gstin=$our_co_GST_no&gstin=$GST_NO";
$json 				= file_get_contents($json_url);
$data 				= json_decode($json, TRUE); //exit;
/*echo "<pre>";
print_r($data);
echo "</pre>";*/ /*exit;*/

$errorMessage		=@$data["results"]["errorMessage"];
$status				=@$data["results"]["status"];
$GST_trade_name		=@$data["results"]["message"]["TradeName"];
$GST_status			=@$data["results"]["message"]["Status"];
$GST_blocked_status	=@$data["results"]["message"]["BlkStatus"];
}

else
{
$data="URP";
$status="URP";
$GST_trade_name="URP";
$GST_status="URP";
$GST_blocked_status="URP";
$data="URP";
}	

/*if($GST_status=='' || $GST_status=='Failed' || $GST_status=='CNL' || $errorMessage=='3001: Requested data is not available' )
//if($GST_status=='CNL' || $errorMessage=='3001: Requested data is not available')
{
$status="URP";
$GST_trade_name="URP";
$GST_status="URP";
$GST_blocked_status="URP";
$data="URP";
}
else
{
$status="URP";
$GST_trade_name="URP";
$GST_status="URP";
$GST_blocked_status="URP";
$data="URP";
}		*/

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
		//	'data' => $data, 
			'GST_trade_name' => $GST_trade_name, 
			'GST_status' => $GST_status, 
			'GST_blocked_status' => $GST_blocked_status, 
		 'GST_check_result' => $data, 
			
        ]);
    }
	

    public function company_bank_address()
    {
        $rs_company_bank_address 	= DB::table('tbl_company_bank_address')->where('deleteflag', '=', 'active')->orderby('bank_id','asc')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'company_bank_address' => $rs_company_bank_address, 
        ]);
    }

		
	
}//class closed
