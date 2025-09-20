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



public function inside_sales_listing(Request $request)
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
$overdue_filter=" AND tbl_web_enq.Enq_Date <= DATE_SUB(CURDATE(), INTERVAL $alarm_3_days DAY) ";
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

	if($todays_enq=='' && $todays_enq=='0')
	{	
	if($search_from!='' && $datepicker_to!='')
	{
		
$date_range_search=" AND (date( tbl_web_enq.Enq_Date ) BETWEEN '$search_from' AND '$datepicker_to')";
	}
	else
	{
$date_range_search=" ";		
	}
}
else
{
	$date_range_search=" AND DATE(tbl_web_enq.Enq_Date) = '$todays_enq_date'  ";		
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
tbl_enq_source.enq_source_name,
tbl_application.application_name,
CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source

LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
where    tbl_web_enq.mark_as_completed=0
$searchRecord
 order by $order_by  $order  LIMIT $from, $max_results";	 //exit;
 
$result_enq =  DB::select(($sql));



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
$date_range_search_today_assigned_s
 AND tbl_web_enq_edit.ID IS NOT NULL 
 order by $order_by  $order  LIMIT $from, $max_results";	 //exit;
 
$result_enq_assigned_today =  DB::select(($sql_assigned_today));
$num_rows_enq_assigned_today	= count($result_enq_assigned_today); 	  


$sql_paging_and_export_excel	= "SELECT
tbl_web_enq.ID as 'enq_id', 
tbl_web_enq.Cus_name ,
tbl_web_enq.Cus_email ,
tbl_web_enq.Cus_mob ,
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
tbl_enq_source.enq_source_name,
tbl_application.application_name,
CONCAT(tbl_admin.admin_fname,' ',tbl_admin.admin_lname) as acc_manager_name
FROM tbl_web_enq 
LEFT JOIN tbl_web_enq_edit ON tbl_web_enq_edit.enq_id = tbl_web_enq.ID 
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_web_enq.product_category  
LEFT JOIN tbl_enq_source ON tbl_enq_source.enq_source_description=tbl_web_enq.ref_source

LEFT JOIN tbl_admin ON tbl_admin.admin_id=tbl_web_enq_edit.acc_manager
where tbl_web_enq.mark_as_completed=0
$searchRecord
 order by $order_by  $order  ";
$result_enq_paging_export_to_excel 	=  DB::select(($sql_paging_and_export_excel));					
$num_rows							= count($result_enq_paging_export_to_excel); 	  
$assigned_today						= "0";
	return response()->json([ 
			'enquiry_data' => $result_enq,
			'export_enquiry_data' => $result_enq,
			'num_rows_count' => $num_rows,
			'assigned_today' => $num_rows_enq_assigned_today
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


 public function  delete_multiple_enquiry(Request $request)
    {    
        $date 	  							= date('Y-m-d');
        $ID									= $request->ID;// exit;
		
		
		print_r($ID);
//		$inside_sales_enq_delete	= DB::table('tbl_web_enq')->where('ID', $ID)->delete();
//		$sales_enq_delete			= DB::table('tbl_web_enq_edit')->where('enq_id', $ID)->delete();

	echo "ctr".	$ctr									= count($ID);
		for($i=0; $i<$ctr; $i++)
{
        $ID									= $ID[$i];// exit;
		$ArrayDataedited['deleteflag'] 			= "inactive";
        $inside_sales_enq_delete= DB::table('tbl_web_enq')
            ->where('ID', $ID)
            ->update($ArrayDataedited);


        $sales_enq_delete= DB::table('tbl_web_enq_edit')
            ->where('enq_id', $ID)
            ->update($ArrayDataedited);

}

       if($inside_sales_enq_delete){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

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


if($acc_manager!='' && $acc_manager!='0')
{
	
$acc_manager_search= " AND ds.Enq_Date BETWEEN DATE_SUB(CURDATE(), INTERVAL $month MONTH) AND CURDATE()";//  -- Filter for April & December
}
else
{
$acc_manager_search= " ";//  -- Filter for April & December
}

//echo $q4_end_date_show;

   $weekly_enquiry_metrics 	= "WITH RECURSIVE date_series AS (
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
    COUNT(twe.ID) AS inquiry_count
FROM date_series ds
LEFT JOIN tbl_web_enq twe ON ds.Enq_Date = DATE(twe.Enq_Date)
where 1=1 $month_search
GROUP BY year, month_name, week_in_month, day_name, ds.Enq_Date
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
       $query="Select taa.admin_id, ta.admin_fname, ta.admin_lname, ta.admin_status,ta.admin_email,ta.admin_telephone from tbl_admin_allowed_state taa INNER JOIN tbl_admin ta on taa.admin_id=ta.admin_id where 1=1 $country_search $state_search $city_search $cust_segment_search  $pro_id_search $app_cat_id_search and taa.deleteflag = 'active' and ta.admin_status='active' order by taa.admin_id";
	//$rs_role  = mysqli_query($GLOBALS["___mysqli_ston"],  $query);
$rs_role = DB::select(($query));	
  "<br>NUM: ".$num_rows	= count($rs_role); 	  
//echo "<br>NUM: ".$num_rows=mysqli_num_rows($rs_role);


if($num_rows=='0' || $num_rows=='')
{
	
/*	 echo "<br><br>NONE:". $query="Select * from tbl_admin_allowed_state  where 1=1 $country_search $state_search $cust_segment_search  $pro_id_search $app_cat_id_search  AND    deleteflag = 'active' and admin_status='active' order by admin_id";*/

     "<br><br>".	  $query="Select admin_id, admin_fname, admin_lname, admin_status, admin_email, admin_telephone from tbl_admin where 1=1 $country_search_tbl_admin $state_search_tbl_admin $cust_segment_search_tbl_admin  $pro_id_search $app_cat_id_search_tbl_admin  and deleteflag = 'active' and admin_status='active' order by admin_status,admin_fname";

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
	
}//class closed
