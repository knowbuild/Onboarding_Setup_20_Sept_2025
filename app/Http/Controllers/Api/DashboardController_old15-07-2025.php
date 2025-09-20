<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Models\{
    TaxInvoice,
    InvoiceProduct,
    CreditNoteInvoiceProduct,
    TesManager,
    Tes,
    Order,
    Application, 
    ApplicationService,
    Service,
    ServicesEntry,
    ProductMain, 
    ProductsEntry,
    OrderProduct,
    CustSegment,
    EnqSource,
    FinancialYear,
    TaxCreditNoteInvoice,
    Event,
    IndexG2,
    IndexS2,
    Lead,
    Company,
    
    User, 
    FiscalMonth,
};
class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
public function users(Request $request)
{
    $query = DB::table('tbl_admin')
        ->where('deleteflag', 'active')
        ->where('admin_status', 'active');

    // If admin_role_id is provided and not blank, apply the filter
    if (!empty($request->admin_role_id)) {
        $query->where('admin_role_id', $request->admin_role_id);
    }

    $rs_assign = $query->orderBy('admin_fname', 'asc')
        ->select('admin_id', 'admin_fname', 'admin_lname', 'admin_abrv', 'admin_telephone', 'admin_email')
        ->get();

    return response()->json([
        'users' => $rs_assign,
    ]);
}



public function users_as_per_team(Request $request)
{
    $admin_role_id     = $request->admin_role_id;
    $admin_team_id     = $request->admin_team_id;
    $AdminLoginID_SET  = $request->currentuserid;
    $page_id           = $request->page_id;

    // Account Manager Fallback
    $acc_manager = $request->acc_manager;
    if (empty($acc_manager)) {
        $acc_manager = $admin_role_id == '9' ? $AdminLoginID_SET : null;
    }

    // Team logic
    $acc_manager_lead      = $AdminLoginID_SET;
    $admin_sub_team_lead   = admin_sub_team_lead($admin_team_id);
    $admin_sub_team_lead2  = admin_sub_team_lead2($admin_team_id);
    $admin_team_lead       = admin_team_lead($AdminLoginID_SET);
    $perm_name             = "filter_perm";
    $filter_per            = indiv_permission_sel($page_id, $admin_role_id, $acc_manager, $perm_name);

    // Determine team members
    $tm1 = '';

    if ($acc_manager_lead == $admin_sub_team_lead || $acc_manager_lead == $admin_sub_team_lead2) {
        $query = "SELECT GROUP_CONCAT(DISTINCT CONCAT_WS(',', IFNULL(admin_id, ''), IFNULL(sub_team_lead, ''), IFNULL(sub_team_member, ''))) AS team_members
                  FROM tbl_admin_team_members
                  WHERE sub_team_lead = ?";
        $row = DB::select($query, [$acc_manager_lead]);
        $tm1 = $row[0]->team_members ?? '';
    } elseif ($admin_team_lead == $acc_manager_lead) {
        $query = "SELECT GROUP_CONCAT(admin_id) as team_members
                  FROM tbl_admin
                  WHERE admin_team = ? AND admin_team_lead = ?
                  ORDER BY admin_team DESC";
        $row = DB::select($query, [$admin_team_id, $admin_team_lead]);
        $tm1 = $row[0]->team_members ?? '';
    } else {
        $query = "SELECT CONCAT_WS(',', admin_id, sub_team_member1, sub_team_member2, sub_team_member3) as team_members
                  FROM tbl_admin
                  WHERE admin_id = ?";
        $row = DB::select($query, [$acc_manager_lead]);
        $tm1 = $row[0]->team_members ?? '';
    }

    // Get users
    if (in_array($admin_role_id, ['5', '20'])) {
        $sql_users_team_wise = "
            SELECT 
                a.admin_id,
                a.customer_code,
                a.admin_fname,
                a.admin_lname,
                a.admin_email,
                a.admin_gender,
                a.admin_abrv,
                a.admin_team,
                t.team_name AS team_name,
                a.admin_team_lead,
                a.sub_team_lead,
                a.sub_team_lead2,
                a.sub_team_lead3,
                a.sub_team_member1,
                a.sub_team_member2,
                a.sub_team_member3,
                a.admin_level,
                a.admin_role_id,
                a.admin_designation,
                a.admin_telephone,
                a.otp,
                d.designation_name
            FROM tbl_admin a
            LEFT JOIN tbl_designation_comp d ON d.designation_id = a.admin_designation
            LEFT JOIN tbl_team t ON t.team_id = a.admin_team
            ORDER BY a.admin_status, a.admin_fname
        ";
    } else {
        $sql_users_team_wise = "
            SELECT 
                a.admin_id,
                a.customer_code,
                a.admin_fname,
                a.admin_lname,
                a.admin_email,
                a.admin_gender,
                a.admin_abrv,
                a.admin_team,
                t.team_name AS team_name,
                a.admin_team_lead,
                a.sub_team_lead,
                a.sub_team_lead2,
                a.sub_team_lead3,
                a.sub_team_member1,
                a.sub_team_member2,
                a.sub_team_member3,
                a.admin_level,
                a.admin_role_id,
                a.admin_designation,
                a.admin_telephone,
                a.otp,
                d.designation_name
            FROM tbl_admin a
            LEFT JOIN tbl_designation_comp d ON d.designation_id = a.admin_designation
            LEFT JOIN tbl_team t ON t.team_id = a.admin_team
            WHERE a.admin_id IN ($tm1)
            ORDER BY a.admin_status, a.admin_fname
        ";
    }

    $row_users_team_wise = DB::select($sql_users_team_wise);

    return response()->json([
        'users' => $row_users_team_wise,
        'all_users' => $tm1,
    ]);
}




public function assign_enquiry_basis_segment(Request $request)
{

 //      $acc_manager			= $request->acc_manager;  
 "State".			$state			= $request->state;
 "<br>City".		$city			= $request->city;
 "<br>cus segment".	$cust_segment	= $request->cust_segment;	
   "<br>country".	$country		= $request->country;
 "<br>pro cat".		$app_cat_id 	= $request->app_cat_id;
 "<br>Pro_id".		$pro_id			= $request->pro_id;	

		if($pro_id	!= '')		
		{
			$pro_id_search = "  ";
		}
		else
		{
			$pro_id_search = "";
		}
		
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

     $query="Select DISTINCT(taa.admin_id), ta.admin_fname, ta.admin_lname, ta.admin_status from tbl_admin_allowed_state taa 
	 INNER JOIN tbl_admin ta on taa.admin_id=ta.admin_id 
	 where 1=1 
	 $country_search 
	 $state_search 
	 $city_search 
	 $cust_segment_search  
	 $pro_id_search 
	 $app_cat_id_search 
	 and taa.deleteflag = 'active' 
	 and ta.admin_status='active' order by taa.admin_id"; //exit;
     $acc_manager_data 			=  DB::select(($query)); 
       "<br>NUM11: ".  $num_rows	= count($acc_manager_data); 
	  
	  
if($num_rows=='0' || $num_rows=='')
{
/* echo "<br><br>NONE:". $query="Select * from tbl_admin_allowed_state  where 1=1 $country_search $state_search $cust_segment_search  $pro_id_search $app_cat_id_search  AND    deleteflag = 'active' and admin_status='active' order by admin_id";*/
$query="Select * from tbl_admin where 1=1 $country_search_tbl_admin $state_search_tbl_admin $cust_segment_search_tbl_admin  $pro_id_search $app_cat_id_search_tbl_admin  and deleteflag = 'active' and admin_status='active' order by admin_status,admin_fname";
}
	  
  
   $acc_manager_data 		=  DB::select(($query)); 
   $acc_manager_data_count	= count($acc_manager_data); 	  
//	$rs_role  = mysqli_query($GLOBALS["___mysqli_ston"],  $query);
  // "<br>NUM: ".$num_rows=mysqli_num_rows($rs_role);
	   
//	   $AdminLoginID_SET	= $request->currentuserid;  ;//Auth::user()->id;

$acc_manager_data = DB::select(($query));  
        return response()->json([            
            'users' => $acc_manager_data, 
        ]);
}

public function financial_year()
    {
        $years 	= DB::table('tbl_financial_year')->orderby('fin_name','asc')->select('fin_name','fin_id')->get();

        // Logic for your dashboard, e.g., returning dashboard data.
        return response()->json([            
            'years' => $years, 
        ]);
    }

public function account_receivables(Request $request)
    {
       $company		= $request->company; 
       $acc_manager			= $request->acc_manager;  
        if($acc_manager=='')
        {
                $acc_manager			= "";
        }
        if($company=='')
        {
                $company			= "";
        }
        $payment_received	= payment_received_by_aging($acc_manager,0,0,$company);
        $pending_account_receivables = pending_account_receivables($acc_manager,0,0,'5',$company);
        $total_pending_payments = ($pending_account_receivables-$payment_received);
        $account_receivables	= $total_pending_payments;	

        return response()->json([            
            'account_receivables' => $account_receivables, 
        ]);
    }

public function orders_in_hand(Request $request)
    {
        $financial_year		= $request->financial_year; 
        $acc_manager		= $request->acc_manager;   
       
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

        $tes_id		= get_tes_id($acc_manager,$fin_yr);

        if($acc_manager=='' || $acc_manager=='All')
        {
            $tes_id=$all_tes_id=get_all_tes_id_current_year($fin_yr);
        }
        else
        {               
            if($tes_id!='0' && $tes_id!='' )
            {
                $tes_id		= get_tes_id($acc_manager,$fin_yr);
            }
            else
            {
                $tes_id		= "0";			 
            }
        }

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

        $orders_in_hand			= (sales_dashboard_orders_in_hand_value($acc_manager,$q1_start_date_show,$q4_end_date_show,'5'));

        return response()->json([            
            'orders_in_hand' => $orders_in_hand, 
        ]);
    }

public function opportunities(Request $request)
    {
        $financial_year		= $request->financial_year; 
        $acc_manager		= $request->acc_manager;   
       
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

        $tes_id		= get_tes_id($acc_manager,$fin_yr);

        if($acc_manager=='' || $acc_manager=='All')
        {
            $tes_id=$all_tes_id=get_all_tes_id_current_year($fin_yr);
        }
        else
        {               
            if($tes_id!='0' && $tes_id!='' )
            {
                $tes_id		= get_tes_id($acc_manager,$fin_yr);
            }
            else
            {
                $tes_id		= "0";			 
            }
        }

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

        $opportunities			= (opportunity_value_for_dashboard($acc_manager,$q1_start_date_show,$q4_end_date_show,'4'));

        return response()->json([            
            'opportunities' => $opportunities, 
        ]);
    }

public function tasks_data(Request $request)
    {
        $acc_manager = $request->acc_manager;
        $today_date				= date("Y-m-d",strtotime(date("Y-m-d") . ''));
        $today_date_end			= date('Y-m-d',strtotime($today_date . ' +1 day'));

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
    	$max_results = 50; 
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

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
	{
	//$orders_status='Pending';
	$acc_manager_search_task=" and account_manager IN ($acc_manager)";
//	$acc_manager_search_follow_up=" and o.order_by=$acc_manager";
	}
else
{
		$acc_manager_search_task=" ";
}
	
$sql_tasks_data 		=         "select 
tc.id,
tc.comp_name,
tce.company_extn_name,
tc.office_type,
tc.co_extn_id,
o.customers_id,
o.customers_name,
o.customers_contact_no,
o.customers_email,
evt.id,
evt.title,
evt.start_event,
evt.account_manager,
evt.lead_type,
evt.opportunity_value,
evt.status,
ttm.tasktype_name,
ttm.task_icon
from events evt
LEFT JOIN tbl_tasktype_master ttm ON evt.evttxt=ttm.tasktype_abbrv
LEFT JOIN tbl_order o ON evt.lead_type=o.orders_id
LEFT JOIN tbl_comp tc ON o.customers_id=tc.id
LEFT JOIN tbl_company_extn tce ON tc.co_extn_id=tce.company_extn_id
where start_event BETWEEN '$today_date' AND '$today_date_end'
$acc_manager_search_task
order by start_event desc  LIMIT $from, $max_results ";
//        $sql_tasks_data 		= "select * from events where 1=1 $cond order by start_event desc ";
        $tasks_data 			=  DB::select(($sql_tasks_data)); 
        $pending_status_count	= count($tasks_data); 

        return response()->json([            
            'tasks_data' => $tasks_data, 
            'pending_status_count' => $pending_status_count, 
        ]);
    }

public function escalations_data(Request $request)
    {   
        $dead_duck = "";
        $acc_manager_request = "";
        if($request->acc_manager!='')
        {
            $acc_manager_request			= $request->acc_manager;
        }
        
		
        if($request->dead_duck!='')
        {
            $dead_duck			= $request->dead_duck;
        }
		else
		{
		   $dead_duck			= 0;
		}

        $ref_source_request 			= $request->ref_source_request;
        $hot_enq_search=" and tbl_web_enq_edit.hot_enquiry='1' ";

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//           $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager_request' ";
	         $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN ($acc_manager_request) ";
        }
        else
        {
            $acc_manager_search_escalation=" ";
        }
        if($ref_source_request!='' && $ref_source_request!='0' )
        {
            $ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        }
        else
        {
            $ref_source_search=" ";
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
    	$max_results = 50; 
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
        $searchRecord = $acc_manager_search_escalation.$ref_source_search;
            
         $sql_enq_data	= "SELECT 
            DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
            tbl_web_enq_edit.enq_id,
            tbl_web_enq_edit.dead_duck,
            tbl_web_enq_edit.ID,
            tbl_web_enq_edit.ref_source,
            tbl_web_enq_edit.old_enq_date,
            tbl_web_enq_edit.lead_id,
            tbl_web_enq_edit.order_id,
            tbl_web_enq_edit.acc_manager,
            tbl_web_enq_edit.Cus_name,
			tbl_web_enq_edit.enq_type,
			tbl_web_enq_edit.price_type,
            tbl_web_enq_edit.Cus_email,     
            tbl_web_enq_edit.Cus_mob,
            tbl_web_enq_edit.Cus_msg,
            DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
            tbl_web_enq_edit.enq_remark_edited
            FROM tbl_web_enq_edit 
            where tbl_web_enq_edit.deleteflag='active' $searchRecord 
            and tbl_web_enq_edit.lead_id='0'
            and tbl_web_enq_edit.dead_duck='$dead_duck'
            HAVING days_since_enq > 0
            order by days_since_enq   LIMIT $from, $max_results ";
            $enq_data =  DB::select(($sql_enq_data));  
	        $enq_data_count=count($enq_data);
            return response()->json([            
                'escalations_data' => $enq_data,
				'enq_data_count' => $enq_data_count
            ]);
    }



public function escalations_data_snooze(Request $request)
    {   
        $dead_duck = "";
        $acc_manager_request = "";
        if($request->acc_manager!='')
        {
            $acc_manager_request			= $request->acc_manager;
        }
        
		
        if($request->dead_duck!='')
        {
            $dead_duck			= $request->dead_duck;
        }
		else
		{
		   $dead_duck			= 2;
		}

        $ref_source_request 			= $request->ref_source_request;
        $hot_enq_search=" and tbl_web_enq_edit.hot_enquiry='1' ";

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//           $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager_request' ";
	         $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN ($acc_manager_request) ";
        }
        else
        {
            $acc_manager_search_escalation=" ";
        }
        if($ref_source_request!='' && $ref_source_request!='0' )
        {
            $ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        }
        else
        {
            $ref_source_search=" ";
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
    	$max_results = 10; 
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
        $searchRecord = $acc_manager_search_escalation.$ref_source_search;
            
         $sql_enq_data_snooze	= "SELECT 
            DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
            tbl_web_enq_edit.enq_id,
            tbl_web_enq_edit.dead_duck,
            tbl_web_enq_edit.ID,
            tbl_web_enq_edit.ref_source,
            tbl_web_enq_edit.old_enq_date,
            tbl_web_enq_edit.lead_id,
            tbl_web_enq_edit.order_id,
            tbl_web_enq_edit.acc_manager,
            tbl_web_enq_edit.Cus_name,
            tbl_web_enq_edit.Cus_email,     
            tbl_web_enq_edit.Cus_mob,
            tbl_web_enq_edit.Cus_msg,
            DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
            tbl_web_enq_edit.enq_remark_edited
            FROM tbl_order
            RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
            where tbl_web_enq_edit.deleteflag='active' $searchRecord 
            and tbl_web_enq_edit.lead_id='0'
            and tbl_web_enq_edit.dead_duck='$dead_duck'
            order by days_since_enq  LIMIT $from, $max_results ";
           $enq_data_snooze =  DB::select(($sql_enq_data_snooze));  
			
	        $enq_data_snooze_count=count($enq_data_snooze);
            return response()->json([            
                'escalations_snooze_data' => $enq_data_snooze,
				'enq_data_snooze_count' => $enq_data_snooze_count
            ]);
    }
    
//main modules menu
    public function menu_data(Request $request)
    {
        $menu_data = DB::table('tbl_website_page_module')->select('module_id','module_name','display_order','permalink','icon_image','icon_class')->where('deleteflag', '=', 'active')->orderby('display_order','asc')->get(); 
       
        return response()->json([            
        'menu_data' => $menu_data, 
        ]);
    }

   
//modules sub menu
    public function sub_menu_data(Request $request)
    {    
        $module_id = $request->module_id;     
        $sub_menu_data = DB::table('tbl_website_page')->select('page_id','module_id','page_title','page_name','display_order','help_text')->where('module_id', '=', $module_id)->where('deleteflag', '=', 'active')->orderby('display_order','asc')->get(); 
        
        return response()->json([            
        'sub_menu_data' => $sub_menu_data, 
        ]);
    }


    public function total_executed(Request $request)
    {
        $financial_year		= $request->financial_year; 
        $acc_manager			= $request->acc_manager;   
       
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

        $tes_id		= get_tes_id($acc_manager,$fin_yr);


//$searchForValue = ',';
//$stringValue = $acc_manager;
//
//if( strpos($stringValue, $searchForValue) !== false ) {
//    $acc_manager="comma";
//}


        if($acc_manager=='' || $acc_manager=='All'  )
        {
            $tes_id=$all_tes_id=get_all_tes_id_current_year($fin_yr);
        }
        else
        {               
            if($tes_id!='0' && $tes_id!='' )
            {
                $tes_id		= get_tes_id($acc_manager,$fin_yr);
            }
            else
            {
                $tes_id		= "0";			 
            }
        }


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


       $cr_note_amt		= sum_of_credit_note(0,$q1_start_date_show,$q4_end_date_show,$acc_manager,$aging_min=0,$aging_max=0,$company_name=0);
        $this_fy_year		= invoice_total_by_date($acc_manager,$q1_start_date_show,$q4_end_date_show);  
        $total_executed		= ($this_fy_year-$cr_note_amt);	

		 if($tes_id!='' && $tes_id!='0')
		 {
		 $total_target	= TES_Total_Target($tes_id);            		 		 

		 }
		 else
		 {
		 $total_target	= $total_executed;//'1000000000';            		 		 
		 }	 
		
		 
 
        
        return response()->json([            
            'total_executed' => $total_executed, 
			'total_target' => $total_target, 
        ]);
    }

    public function kill_enq_data(Request $request)
    {
    
        if(isset($request->id)){             
            $kill_enq_data = DB::table('tbl_web_enq_edit')->where('enq_id',$request->id)->get(['ID','enq_id','acc_manager','dead_duck','enq_remark_edited','Cus_msg']);          
        }

        return response()->json([            
        'kill_enq_data' => $kill_enq_data, 
        ]);
    }

    public function  kill_enquiry(Request $request)
    {
    
        $date 	  				= date('Y-m-d');
        $dead_duck				= $request->dead_duck;
        $enq_remark_edited		= $request->Cus_msg;

        $ID						= $request->ID;
        $EID					= $request->enq_id;
        $snooze_days			= "0";

        $ArrayData['dead_duck'] = $dead_duck;
        $ArrayData['enq_remark'] = $enq_remark_edited;
        $snooze_days							= "0";
        $snooze_date							= date('Y-m-d h:m:s',strtotime('+'.$snooze_days.' days'));
        $ArrayDataedited['snooze_days'] 		= 0;
        $ArrayDataedited['snooze_date'] 		= date('Y-m-d h:m:s');
        $ArrayDataedited['dead_duck'] 			= $dead_duck;
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

        DB::table('tbl_web_enq_edit')
            ->where('enq_id', $EID)
            ->update($ArrayDataedited);

        DB::table('tbl_web_enq')
            ->where('ID', $EID)
            ->update($ArrayData);
        
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
        ]);
    }

//mark as active
    public function  mark_as_active_enquiry(Request $request)
    {
    
        $date 	  				= date('Y-m-d');
        $dead_duck				= $request->dead_duck;
        $enq_remark_edited		= $request->Cus_msg;
        $ID						= $request->ID;
        $EID					= $request->enq_id;
        $snooze_days			= "0";

        $ArrayData['dead_duck'] = $dead_duck;
        $ArrayData['enq_remark'] = $enq_remark_edited;
        $snooze_days							= "0";
        $snooze_date							= date('Y-m-d h:m:s',strtotime('+'.$snooze_days.' days'));
        $ArrayDataedited['snooze_days'] 		= 0;
        $ArrayDataedited['snooze_date'] 		= date('Y-m-d h:m:s');
        $ArrayDataedited['dead_duck'] 			= $dead_duck;
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

        DB::table('tbl_web_enq_edit')
            ->where('enq_id', $EID)
            ->update($ArrayDataedited);

        DB::table('tbl_web_enq')
            ->where('ID', $EID)
            ->update($ArrayData);
        
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
        ]);
    }


//remind me
    public function set_a_reminder(Request $request)
    {
    
        $date 	  				= date('Y-m-d');
        $ID						= $request->ID; // 45113 // edited_enq
        $EID					= $request->enq_id; // 83720 masater
		$orders_id				= get_orders_id_by_edited_enq_id($ID);		

        $follow_up_date			= $request->follow_up_date;
		$current_user_id		= $request->current_user_id;
		$enq_remark_edited		= "Remind me on this date: ".$follow_up_date;
        $snooze_days			= "0";

		$ArrayDataedited['remind_me'] 			= $follow_up_date;	
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");


/*****************************************************************************///update followup date if it has orders id
		if($orders_id!='') {            
		$ArrayData_ord['follow_up_date'] 		= $follow_up_date;
		DB::table('tbl_order')
		->where('orders_id', $orders_id)
		->update($ArrayData_ord); 
		}
/*****************************************************************************/

       $query = DB::table('tbl_web_enq_edit')
//            ->where('enq_id', $ID)
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
//      $fileArray_tbl_enq_remarks["snooze_days"]				= $snooze_days;
  //    $fileArray_tbl_enq_remarks["snooze_date"]				= $snooze_date;

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
	
    public function  snooze_enquiry(Request $request)
    {    
        $date 	  				= date('Y-m-d');
        $dead_duck				= $request->dead_duck;
        $enq_remark_edited		= $request->Cus_msg;
        $ID						= $request->ID;
        $EID					= $request->enq_id;
        $snooze_days			= isset($request->snooze_days) ? $request->snooze_days : 0;

        $ArrayData['dead_duck'] = $dead_duck;
        $ArrayData['enq_remark'] = $enq_remark_edited;
       // $snooze_days							= "0";
        $snooze_date							= date('Y-m-d h:m:s',strtotime('+'.$snooze_days.' days'));
	    $ArrayDataedited['snooze_days'] 		= $snooze_days;
	    $ArrayDataedited['snooze_date'] 		= $snooze_date;
        $ArrayDataedited['dead_duck'] 			= $dead_duck;
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

        DB::table('tbl_web_enq_edit')
            ->where('enq_id', $EID)
            ->update($ArrayDataedited);

        DB::table('tbl_web_enq')
            ->where('ID', $EID)
            ->update($ArrayData);
        
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
        ]);
    }

    public function  dead_enquiry(Request $request)
    {    
        $date 	  				= date('Y-m-d');
        $dead_duck				= $request->dead_duck;
        $enq_remark_edited		= $request->Cus_msg;
        $ID						= $request->ID;
        $EID					= $request->enq_id;

        $ArrayData['dead_duck'] = $dead_duck;
        $ArrayData['enq_remark'] = $enq_remark_edited;
        $snooze_days							= "0";
        $snooze_date							= date('Y-m-d h:m:s',strtotime('+'.$snooze_days.' days'));
        $ArrayDataedited['snooze_days'] 		= 0;
        $ArrayDataedited['snooze_date'] 		= date('Y-m-d h:m:s');
        $ArrayDataedited['dead_duck'] 			= $dead_duck;
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

        DB::table('tbl_web_enq_edit')
            ->where('enq_id', $ID)
            ->update($ArrayDataedited);

        DB::table('tbl_web_enq')
            ->where('ID', $ID)
            ->update($ArrayData);
        
        //$currentuserid = Auth::user()->id; 
        $fileArray_tbl_enq_remarks["enq_id"]					= $ID;
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
        ]);
    }


//add note

public function get_add_note_enq_data(Request $request)  
    {
    
        if(isset($request->id)){             
		$sql	= "SELECT 
        tbl_enq_remarks.id,
		tbl_enq_remarks.enq_id,
		tbl_enq_remarks.remarks,
		tbl_enq_remarks.dead_duck,
		tbl_enq_remarks.added_by,
		tbl_enq_remarks.remarks_added_date_time,
		tbl_admin.admin_fname,
		tbl_admin.admin_lname
        FROM tbl_enq_remarks
		
        RIGHT JOIN tbl_admin ON tbl_admin.admin_id=tbl_enq_remarks.added_by  
		where tbl_enq_remarks.enq_id = ".$request->id."
        order by tbl_enq_remarks.id desc";
		
		
		$add_note_enq_data =  DB::select(($sql));
		
            //$add_note_enq_data = DB::table('tbl_web_enq_edit')->where('enq_id',$request->id)->get(['ID','enq_id','acc_manager','dead_duck','enq_remark_edited','Cus_msg']);          
        }

        return response()->json([            
        'add_note_enq_data' => $add_note_enq_data, 
        ]);
    }

    public function  add_note_to_enquiry(Request $request)
    {
    
        $date 	  								= date('Y-m-d');
        $dead_duck								= $request->dead_duck;
        $enq_remark_edited						= $request->enq_remark_edited;
		$added_by_current_user					= $request->current_user_login_id;
        $ID										= $request->id;
        $EID									= $request->enq_id;
        $ArrayData['dead_duck'] 				= $dead_duck;
        $ArrayData['enq_remark'] 				= $enq_remark_edited;
        $snooze_days							= "0";
        $snooze_date							= date('Y-m-d h:m:s',strtotime('+'.$snooze_days.' days'));
        $ArrayDataedited['snooze_days'] 		= 0;
        $ArrayDataedited['snooze_date'] 		= date('Y-m-d h:m:s');
        $ArrayDataedited['dead_duck'] 			= $dead_duck;
        $ArrayDataedited['enq_remark_edited'] 	= $enq_remark_edited;	
        $ArrayDataedited['mel_updated_on'] 		= date("Y-m-d H:i:s");

        DB::table('tbl_web_enq_edit')
            ->where('enq_id', $ID)
            ->update($ArrayDataedited);

        DB::table('tbl_web_enq')
            ->where('ID', $ID)
            ->update($ArrayData);

        //$currentuserid = Auth::user()->id; 
        $fileArray_tbl_enq_remarks["enq_id"]					= $ID;
        $fileArray_tbl_enq_remarks["remarks"]					= $enq_remark_edited;
        $fileArray_tbl_enq_remarks["dead_duck"]					= $dead_duck;
        $fileArray_tbl_enq_remarks["added_by"]					= $added_by_current_user;
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
        ]);
    }


public function get_enq_old_remarks(Request $request)
    {
        if(isset($request->id)){             

//            $get_enq_old_remarks = DB::table('tbl_enq_remarks')->where('enq_id',$request->id)->get(['id','enq_id','remarks','dead_duck','added_by','remarks_added_date_time']);          

        $sql	= "SELECT 
        tbl_enq_remarks.id,
		tbl_enq_remarks.enq_id,
		tbl_enq_remarks.remarks,
		tbl_enq_remarks.dead_duck,
		tbl_enq_remarks.added_by,
		tbl_enq_remarks.remarks_added_date_time,
		tbl_admin.admin_fname,
		tbl_admin.admin_lname
        FROM tbl_enq_remarks
		
        RIGHT JOIN tbl_admin ON tbl_admin.admin_id=tbl_enq_remarks.added_by  
		where tbl_enq_remarks.enq_id = ".$request->id."
        order by tbl_enq_remarks.id desc";
        $get_enq_old_remarks =  DB::select(($sql));

        }

        return response()->json([            
        'get_enq_old_remarks' => $get_enq_old_remarks, 
        ]);
    }


    public function  tasks_update(Request $request)
    {  

        $id						= $request->task_id;
        $status					= $request->status;

        $ArrayData['status'] = $status;
        $ArrayData['last_updated_on'] = date('Y-m-d h:m:s');
        
        $result = DB::table('events')
            ->where('id', $id)
            ->update($ArrayData);
       
       if($result){
            $msg = "true"; 
        }else{
            $msg = "false";
       };  

        return response()->json([            
        'message' => $msg, 
        ]);
    }

    public function dashboard_all_data_count(Request $request)
    {    
        $acc_manager_request		= $request->acc_manager;  
        $ref_source_request			= $request->ref_source_request; 
        
        $acc_manager_search_escalation = "";
        $ref_source_search = "";        

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//            $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager_request' ";
           $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN ($acc_manager_request) ";		
        }        
        if($ref_source_request!='' && $ref_source_request!='0')
        {
            $ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        }        
        
        $searchRecord = $acc_manager_search_escalation.$ref_source_search;

        $sql	= "SELECT 
        tbl_order.orders_id,
        tbl_web_enq_edit.hot_enquiry, 
        tbl_order.offercode,
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.lead_id, 
        tbl_web_enq_edit.order_id, 
        tbl_web_enq_edit.enq_id,
        tbl_web_enq_edit.ID,
        tbl_web_enq_edit.Cus_name,
        tbl_web_enq_edit.Cus_email,     
        tbl_web_enq_edit.Cus_mob,
        tbl_web_enq_edit.Cus_msg,
        tbl_web_enq_edit.ref_source,
        tbl_web_enq_edit.cust_segment,
        DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
        tbl_web_enq_edit.old_enq_date,  
        tbl_web_enq_edit.dead_duck,
        tbl_web_enq_edit.enq_remark_edited,
        tbl_web_enq_edit.acc_manager,
        tbl_web_enq_edit.product_category,
        tbl_web_enq_edit.mel_updated_on
        FROM tbl_order
        RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
        where tbl_web_enq_edit.deleteflag='active' $searchRecord 
        order by days_since_enq  LIMIT 0, 20";
        $all_enq_data =  DB::select(($sql)); 

        $all_enq_data_count = count($all_enq_data);  

        return response()->json([            
        'all_enq_data_count' => $all_enq_data_count, 
        ]);

    } 
    
    public function dashboard_opportunities_data_count(Request $request)
    {    
        $acc_manager			= $request->acc_manager;  
        $month			= $month = date('m');
        $financial_year		= $request->financial_year; 
        $acc_manager_search_escalation = "";
       
        if($financial_year == '')
        {
            if (date('m')>3) {
                $year = date('Y').'-'.(date('Y')+1);
            } else {
                $year = (date('Y')-1).'-'.date('Y');
            }

            $financial_year = $year;
            $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode = explode("-",$financial_year);

        

        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
        {
            $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager' ";
        } 


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

        $hvc_value="1000001";
        $offer_probability_search_rec=" and t1.offer_probability IN (4,5)";	
        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
            {
                $acc_manager_search=" and order_by='$acc_manager'";
            }
            else
            { 
                $acc_manager_search="";
            }
        $date_filter_hvc= "AND ( t1.follow_up_date BETWEEN '$q1_start_date_show' AND '$q4_start_date_show' ) "; 
        $searchRecord_opportunities 	= "$acc_manager_search $offer_probability_search_rec $date_filter_hvc ";	
        
       $sql_opportunities_hvc = "SELECT 
        t3.time_lead_added,
        t1.orders_id,
        t2.pro_id
        from tbl_order as t1 
        INNER JOIN tbl_order_product as t2  on t1.orders_id=t2.order_id 
        INNER JOIN tbl_lead as t3  on t1.lead_id=t3.id 
        where 1=1 $searchRecord_opportunities 
        and ensure_sale_month='$month'
        $date_filter_hvc
        and t1.total_order_cost_new > $hvc_value
        group by t1.orders_id order by  t1.orders_id desc ";       
        $opportunities_data_hvc =  DB::select(($sql_opportunities_hvc));
        $opportunities_data_hvc_count=count($opportunities_data_hvc);
        $currmonthsel_follow = date('m');

        $offer_probability_search_rec_follow_up = " and tbl_order.offer_probability IN (4,5)";	
        $searchRecord = $acc_manager_search_escalation.$offer_probability_search_rec_follow_up;
        $currmonthsel = date('m');

        $date_filter= "AND ( tbl_order.follow_up_date BETWEEN '$q1_start_date_show' AND '$q4_start_date_show' ) ";
       $sql_order_follow_up	= "SELECT 
        tbl_order.orders_id, 
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.enq_stage
        FROM tbl_order
        RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
        where tbl_web_enq_edit.deleteflag='active' 
        and MONTH( tbl_order.follow_up_date) IN ( '$currmonthsel')
        $date_filter
        $searchRecord order by tbl_order.follow_up_date desc ";
        $offers_requiring_review =  DB::select(($sql_order_follow_up));

        $offers_requiring_review_count=count($offers_requiring_review);

        $sql_ensure_sale_month="Select orders_id from tbl_order where order_by='$acc_manager' and ensure_sale_month='$month' and deleteflag='active' "; 
        $ensure_sale_month =  DB::select(($sql_ensure_sale_month));
        $ensure_sale_month_count = count($ensure_sale_month);

        $opportunities_data_count = $ensure_sale_month_count + $offers_requiring_review_count + $opportunities_data_hvc_count;

        return response()->json([            
        'all_opportunities_data_count' => $opportunities_data_count, 
        ]);

    }

    public function dashboard_payments_data_count(Request $request)
    {
        $acc_manager_request			= $request->acc_manager;  
        $acc_manager_search_receivable = "";

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//            $acc_manager_search_receivable =" and tti.prepared_by='$acc_manager_request' ";
            $acc_manager_search_receivable =" and tti.prepared_by IN  ($acc_manager_request) ";
        }
           
        $searchRecord_payment_receivable = $acc_manager_search_receivable;

        $sql_receivable="SELECT tti.invoice_id
        from tbl_tax_invoice tti 
        INNER JOIN tbl_supply_order_payment_terms_master s
        LEFT JOIN tbl_tax_credit_note_invoice ttcni ON ttcni.invoice_id=tti.invoice_id
        where 
        tti.payment_terms=s.supply_order_payment_terms_id
        and tti.invoice_id>230000
        AND ttcni.invoice_id IS NULL
        and tti.invoice_status='approved'
        and tti.invoice_closed_status='No'
        $searchRecord_payment_receivable ";

        $accounts_receivable =  DB::select(($sql_receivable));
        $accounts_receivable_count = count($accounts_receivable);

        return response()->json([            
            'all_payments_data_count' => $accounts_receivable_count, 
        ]);
    }

    public function dashboard_open_tenders_data_count(Request $request)
    {
        $acc_manager_request			= $request->acc_manager;
        $acc_manager_tender_search = "";
       
        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//            $acc_manager_tender_search="and acc_manager='$acc_manager_request'";
            $acc_manager_tender_search="and acc_manager IN ($acc_manager_request)";
        }
       
        $sql_tender_assigned	= "SELECT  tnd_id,customer_name,tnd_value,due_on,acc_manager,hot_productnoteother,
        tnd_status,
        tnd_technical_bid,tnd_tech_document,tnd_commercial_bid 
        from tbl_tender where tnd_del_status = 'active' and acc_manager!='0' $acc_manager_tender_search 
        AND tnd_status NOT IN ('Dead', 'lost') 
        order by due_on desc  ";

        $open_tenders =  DB::select(($sql_tender_assigned));
        $open_tenders_count=count($open_tenders);

        return response()->json([            
            'all_open_tenders_data_count' => $open_tenders_count, 
        ]);
    }

    public function dashboard_latest_price_update_data_count(Request $request)
    {
        $acc_manager_request			= $request->acc_manager;

        $today_date				= date("Y-m-d");
        $today_date_pqv=date("Y-m-d",strtotime($today_date . ' +1 day'));
        $today_date_end_30=date('Y-m-d',strtotime($today_date . ' -30 day'));

        $sql_latest_price_update = "SELECT tbl_products.pro_id, tbl_products.pro_title, tbl_products.upc_code, tbl_products.ware_house_stock, tbl_products_entry.pro_price_entry, tbl_products_entry.app_cat_id as app_cat_id_multi, tbl_products_entry.last_modified, tbl_products_entry.price_list, tbl_products_entry.model_no FROM tbl_products_entry left join tbl_products on tbl_products.pro_id=tbl_products_entry.pro_id WHERE tbl_products.deleteflag = 'active' AND tbl_products.STATUS = 'active' AND tbl_products_entry.deleteflag = 'active' AND tbl_products_entry.STATUS = 'active' AND tbl_products_entry.price_list!='' and tbl_products_entry.price_list='pvt' and tbl_products.product_type_class_id IN ('1','2') 
        and `tbl_products_entry`.last_modified BETWEEN '$today_date_end_30' AND '$today_date_pqv'
        ORDER BY `tbl_products_entry`.`last_modified` DESC ";
            
        $latest_price_update =  DB::select(($sql_latest_price_update));
        $latest_price_update_count=count($latest_price_update);

        return response()->json([            
            'all_latest_price_data_count' => $latest_price_update_count, 
        ]);
    }

    public function dashboard_trends_data_count(Request $request)
    {
        $acc_manager_request			= $request->acc_manager;  
        $ref_source_request			= $request->ref_source_request; 
        
        $acc_manager_search_escalation = "";
        $ref_source_search = "";        

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//            $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager_request' ";
            $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN($acc_manager_request) ";
        }        
        if($ref_source_request!='' && $ref_source_request!='0')
        {
            $ref_source_search=" and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        }        
        
        $searchRecord = $acc_manager_search_escalation.$ref_source_search;

        $sql	= "SELECT 
        tbl_order.orders_id,
        tbl_web_enq_edit.hot_enquiry, 
        tbl_order.offercode,
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.lead_id, 
        tbl_web_enq_edit.order_id, 
        tbl_web_enq_edit.enq_id,
        tbl_web_enq_edit.ID,
        tbl_web_enq_edit.Cus_name,
        tbl_web_enq_edit.Cus_email,     
        tbl_web_enq_edit.Cus_mob,
        tbl_web_enq_edit.Cus_msg,
        tbl_web_enq_edit.ref_source,
        tbl_web_enq_edit.cust_segment,
        DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
        tbl_web_enq_edit.old_enq_date,  
        tbl_web_enq_edit.dead_duck,
        tbl_web_enq_edit.enq_remark_edited,
        tbl_web_enq_edit.acc_manager,
        tbl_web_enq_edit.product_category,
        tbl_web_enq_edit.mel_updated_on
        FROM tbl_order
        RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
        where tbl_web_enq_edit.deleteflag='active' $searchRecord 
        order by days_since_enq  LIMIT 0, 20";
        $all_enq_data =  DB::select(($sql)); 

        $all_enq_data_count = count($all_enq_data);  

        return response()->json([            
        'all_trends_data_count' => $all_enq_data_count, 
        ]);
    }

    public function dashboard_enquiries_old(Request $request)
    {        
        $acc_manager_request			= $request->acc_manager;  
        $ref_source_request			= $request->ref_source_request; 
        
        $acc_manager_search_escalation = "";
        $ref_source_search = "";        

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager_request' ";
        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN($acc_manager_request) ";
        }
        else
        {
            $acc_manager_search_escalation="";
        }
        if($ref_source_request!='' && $ref_source_request!='0')
        {
        $ref_source_search = " and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        }
        
        $hot_enq_search = " and tbl_web_enq_edit.hot_enquiry='1' ";
        
        $searchRecord = $acc_manager_search_escalation.$ref_source_search;

         $sql	= "SELECT 
        tbl_order.orders_id,
        tbl_web_enq_edit.hot_enquiry, 
        tbl_order.offercode,
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.lead_id, 
        tbl_web_enq_edit.order_id, 
        tbl_web_enq_edit.enq_id,
        tbl_web_enq_edit.ID,
        tbl_web_enq_edit.Cus_name,
        tbl_web_enq_edit.Cus_email,     
        tbl_web_enq_edit.Cus_mob,
        tbl_web_enq_edit.Cus_msg,
        tbl_web_enq_edit.ref_source,
        tbl_web_enq_edit.cust_segment,
        DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
        tbl_web_enq_edit.old_enq_date,  
        tbl_web_enq_edit.dead_duck,
        tbl_web_enq_edit.enq_remark_edited,
        tbl_web_enq_edit.acc_manager,
        tbl_web_enq_edit.product_category,
        tbl_web_enq_edit.mel_updated_on
        FROM tbl_order
        RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
        where tbl_web_enq_edit.deleteflag='active' $searchRecord 
        order by days_since_enq  LIMIT 0, 20";
        $all_enq_data =  DB::select(($sql)); 

        $all_enq_data_count=count($all_enq_data); 

        return response()->json([            
        'all_enq_data' => $all_enq_data, 
        'all_enq_data_count' => $all_enq_data_count, 
        ]);
    }
	
	
	
    public function dashboard_enquiries(Request $request)
    {        
        $acc_manager_request			= $request->acc_manager;  
        $ref_source_request				= $request->ref_source_request; 
        
        $acc_manager_search_escalation  = "";
        $ref_source_search 				= "";        

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager_request' ";
        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN($acc_manager_request) ";
        }
        else
        {
         $acc_manager_search_escalation="";
        }
        if($ref_source_request!='' && $ref_source_request!='0')
        {
        $ref_source_search = " and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        }
        
 
        $dead_duck_search 	= " and tbl_web_enq_edit.dead_duck='0' ";
		$lead_search 		= " and tbl_web_enq_edit.lead_id='0' ";
		$enq_stage_search 	= " and tbl_web_enq_edit.enq_stage='1' ";


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
    	$max_results = 10; 
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
			
        $searchRecord 		= $acc_manager_search_escalation.$ref_source_search.$dead_duck_search.$lead_search.$enq_stage_search;

        $sql	= "SELECT 
        tbl_web_enq_edit.hot_enquiry, 
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.lead_id, 
        tbl_web_enq_edit.order_id, 
        tbl_web_enq_edit.enq_id,
        tbl_web_enq_edit.ID,
        tbl_web_enq_edit.Cus_name,
        tbl_web_enq_edit.Cus_email,     
        tbl_web_enq_edit.Cus_mob,
		tbl_web_enq_edit.enq_type,
        tbl_web_enq_edit.Cus_msg,
        tbl_web_enq_edit.ref_source,
        tbl_web_enq_edit.cust_segment,
        DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
        tbl_web_enq_edit.old_enq_date,  
        tbl_web_enq_edit.dead_duck,
        tbl_web_enq_edit.enq_remark_edited,
        tbl_web_enq_edit.acc_manager,
        tbl_web_enq_edit.product_category,
        tbl_web_enq_edit.mel_updated_on from
		tbl_web_enq_edit 
        where tbl_web_enq_edit.deleteflag='active' $searchRecord 
        order by days_since_enq  LIMIT $from, $max_results ";
        $all_enq_data =  DB::select(($sql)); 

        $all_enq_data_count=count($all_enq_data); 

        return response()->json([            
        'all_enq_data' => $all_enq_data, 
        'all_enq_data_count' => $all_enq_data_count, 
        ]);
    }	

    public function dashboard_hot_enquiries(Request $request)
    {        
        $acc_manager_request			= $request->acc_manager;  
        $ref_source_request				= $request->ref_source_request; 
        
        $acc_manager_search_escalation = "";
        $ref_source_search = "";        

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager='$acc_manager_request' ";
        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN($acc_manager_request) ";
        }
        else
        {
            $acc_manager_search_escalation="";
        }
        if($ref_source_request!='' && $ref_source_request!='0')
        {
        $ref_source_search = " and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        }
        
        $hot_enq_search 	= " and tbl_web_enq_edit.hot_enquiry='1' ";
      	$dead_duck_search 	= " and tbl_web_enq_edit.dead_duck='0' ";
		$lead_search 		= " and tbl_web_enq_edit.lead_id='0' ";
		$enq_stage_search 	= " and tbl_web_enq_edit.enq_stage='1' ";


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
    	$max_results = 10; 
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
        $searchRecord 		= $acc_manager_search_escalation.$ref_source_search.$dead_duck_search.$lead_search.$enq_stage_search.$hot_enq_search;

/*     echo "hot: ".   $sql	= "SELECT 
        tbl_order.orders_id, 
        tbl_order.offercode,
        tbl_web_enq_edit.lead_id, 
        tbl_web_enq_edit.hot_enquiry,
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.order_id, 
        tbl_web_enq_edit.enq_id,
        tbl_web_enq_edit.ID,
        tbl_web_enq_edit.Cus_name,
        tbl_web_enq_edit.Cus_email,     
        tbl_web_enq_edit.Cus_mob,
        tbl_web_enq_edit.Cus_msg,
        tbl_web_enq_edit.ref_source,
        tbl_web_enq_edit.cust_segment,
        DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
        tbl_web_enq_edit.old_enq_date,  
        tbl_web_enq_edit.dead_duck,
        tbl_web_enq_edit.enq_remark_edited,
        tbl_web_enq_edit.acc_manager,
        tbl_web_enq_edit.product_category,
        tbl_web_enq_edit.mel_updated_on
        FROM tbl_order
        RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
        where tbl_web_enq_edit.deleteflag='active' $searchRecord 
        $hot_enq_search
        order by days_since_enq  LIMIT 0, 20";
		*/
		
		
		
 $sql	= "SELECT 
        tbl_web_enq_edit.hot_enquiry, 
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.order_id, 
        tbl_web_enq_edit.enq_id,
        tbl_web_enq_edit.ID,
        tbl_web_enq_edit.Cus_name,
        tbl_web_enq_edit.Cus_email,     
        tbl_web_enq_edit.Cus_mob,
        tbl_web_enq_edit.Cus_msg,
		tbl_web_enq_edit.enq_type,
        tbl_web_enq_edit.ref_source,
        tbl_web_enq_edit.cust_segment,
        DATE (tbl_web_enq_edit.Enq_Date) as Enq_Date,
        tbl_web_enq_edit.old_enq_date,  
        tbl_web_enq_edit.dead_duck,
        tbl_web_enq_edit.enq_remark_edited,
        tbl_web_enq_edit.acc_manager,
        tbl_web_enq_edit.product_category,
        tbl_web_enq_edit.mel_updated_on
		 from
		tbl_web_enq_edit 
        where tbl_web_enq_edit.deleteflag='active' 
		$searchRecord 
        order by days_since_enq  LIMIT $from, $max_results ";		
		
        $hot_enq_data =  DB::select(($sql)); 
        $hot_enq_data_count=count($hot_enq_data); 
        return response()->json([            
        'hot_enq_data' => $hot_enq_data, 
        'hot_enq_data_count' => $hot_enq_data_count, 
        ]);
    }

    public function dashboard_requiring_followup_enquiries(Request $request)
    {        
        $acc_manager_request			= $request->acc_manager;  
        $ref_source_request			= $request->ref_source_request; 
        
        $acc_manager_search_escalation = "";
        $ref_source_search = "";        

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager ='$acc_manager_request' ";
        $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN($acc_manager_request) ";
        }
        else
        {
            $acc_manager_search_escalation="";
        }
        if($ref_source_request!='' && $ref_source_request!='0')
        {
        $ref_source_search = " and tbl_web_enq_edit.ref_source='$ref_source_request' ";
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
    	$max_results = 10; 
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
        $searchRecord = $acc_manager_search_escalation.$ref_source_search;

          $sql_enq_review	= "SELECT 
        tbl_order.orders_id, 
        tbl_order.offercode,
        tbl_order.total_order_cost,
        tbl_order.follow_up_date,
        tbl_order.Price_value,
        tbl_order.orders_status,
        tbl_order.date_ordered,
        DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
        tbl_web_enq_edit.order_id, 
		tbl_web_enq_edit.enq_type,
        tbl_web_enq_edit.enq_id,
        tbl_web_enq_edit.ID,
        tbl_web_enq_edit.lead_id,
        tbl_web_enq_edit.Cus_name,
        tbl_web_enq_edit.Cus_email,     
        tbl_web_enq_edit.Cus_mob,
        tbl_web_enq_edit.Cus_msg,
        tbl_web_enq_edit.city,
        tbl_web_enq_edit.state,
        tbl_web_enq_edit.hot_enquiry,
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
        FROM tbl_order
        RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
        where tbl_web_enq_edit.deleteflag='active' $searchRecord 
        and tbl_web_enq_edit.lead_id='0'
        HAVING days_since_enq <= 50
        order by tbl_web_enq_edit.ID desc LIMIT $from, $max_results  ";
        $all_enq_data_review =  DB::select(($sql_enq_review));

        $all_enq_data_review_count=count($all_enq_data_review);

        return response()->json([            
        'all_enq_data_review' => $all_enq_data_review, 
        'all_enq_data_review_count' => $all_enq_data_review_count, 
        ]);
    }

    public function dashboard_sales_to_ensure_enquiries(Request $request) 
    {        
        $acc_manager		= $request->acc_manager;  
        $financial_year		= $request->financial_year; 
		$ensure_sale_month	= $request->ensure_sale_month;  
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
    	$max_results = 10; 
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
		
		
		
		
		if($ensure_sale_month=='' )
		{
		
        $month				= $month = date('m');
$ensure_sale_month_search= " and tbl_order.ensure_sale_month='$month'";		
		}
		
else if($ensure_sale_month=='0')
		{
$ensure_sale_month_search="";
		}		
		
		else
		{
	   $month				= $ensure_sale_month;			
		$ensure_sale_month_search= " and tbl_order.ensure_sale_month='$month'";

		}

        $acc_manager_search_escalation = "";
       
        if($financial_year == '')
        {
            if (date('m')>3) {
                $year = date('Y').'-'.(date('Y')+1);
            } else {
                $year = (date('Y')-1).'-'.date('Y');
            }

            $financial_year = $year;
            $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode = explode("-",$financial_year);        

        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
        {
            $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager IN ($acc_manager) ";
        } 

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

         $date_filter_ensure= " AND ( tbl_order.ensure_sale_month_date BETWEEN '$q1_start_date_show 01:00:00' AND '$q4_end_date_show 01:00:00' ) "; 
        if($acc_manager=='' || $acc_manager=='All')
        {        
            $date_filter_ensure_acc_manager= " "; 
        }
        else
        {
            $date_filter_ensure_acc_manager= " AND order_by ='$acc_manager'  "; 
        }        
         $sql_ensure_sale_month="SELECT 
		 tbl_order.*, t3.app_cat_id,t3.ref_source,
		     	 all_cities.city_name as city_name,
		'country_name', tbl_country.country_name, 
    	'state_name', tbl_zones.zone_name, 
    	'city_name', all_cities.city_name
				 
				 from tbl_order 
		 INNER JOIN      tbl_lead AS t3 ON tbl_order.lead_id = t3.id
LEFT JOIN tbl_country 
    ON tbl_country.country_id = tbl_order.shipping_country_name
LEFT JOIN tbl_zones 
    ON tbl_zones.zone_id = tbl_order.shipping_state 
LEFT JOIN all_cities 
    ON all_cities.city_id = tbl_order.shipping_city
		 
		 where tbl_order.orders_status IN ('Pending','Confirmed' ) 
		 and tbl_order.offer_probability <=5 
		 $date_filter_ensure_acc_manager 
		 $ensure_sale_month_search  
		 $date_filter_ensure  
		 and tbl_order.deleteflag='active' 
		 LIMIT $from, $max_results ";  
        
        $ensure_sale_month =  DB::select(($sql_ensure_sale_month));
        $ensure_sale_month_count=count($ensure_sale_month);
		
/*        $ensure_sale_month =  DB::select(($sql_ensure_sale_month));
        $ensure_sale_month_count = count($ensure_sale_month);*/

        return response()->json([            
        'ensure_sale_month' => $ensure_sale_month, 
        'ensure_sale_month_count' => $ensure_sale_month_count, 
        ]);
    }

    public function dashboard_offers_requiring_review_enquiries(Request $request)
    {        
        
        $acc_manager					= $request->acc_manager;  
        $month							= $month = date('m');
        $financial_year					= $request->financial_year; 
        $acc_manager_search_escalation="";
        $ref_source_request 			= $request->ref_source_request;


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
    	$max_results = 10; 
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

	       
        if($financial_year == '')
        {
            if (date('m')>3) {
                $year = date('Y').'-'.(date('Y')+1);
            } else {
                $year = (date('Y')-1).'-'.date('Y');
            }

            $financial_year 			= $year;
            $fin_yr						= show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr						= $financial_year;
        }

        $financial_year_explode 		= explode("-",$financial_year);

        
        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
        {
            $acc_manager_search_escalation="and tbl_web_enq_edit.acc_manager  IN ($acc_manager) ";
        } 


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

        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
        {
            $acc_manager_search_escalation = "and tbl_web_enq_edit.acc_manager IN($acc_manager) ";
        }
        else
        {
            $acc_manager_search_escalation="";
        }
        if($ref_source_request!='' && $ref_source_request!='0')
        {
            $ref_source_search = " and tbl_web_enq_edit.ref_source='$ref_source_request' ";
        } 
               
        
        $offer_probability_search_rec_follow_up = " and tbl_order.offer_probability IN (4,5)";	
        $searchRecord = $acc_manager_search_escalation.$offer_probability_search_rec_follow_up;
        $currmonthsel = date('m');

        $date_filter = "AND ( tbl_order.follow_up_date BETWEEN '$q1_start_date_show' AND '$q4_end_date_show' ) ";
          $sql_order_follow_up	= "SELECT 
    tbl_order.orders_id, 
    tbl_order.Price_type,
    tbl_order.customers_id, 
    tbl_order.offercode,
	tbl_order.offer_currency,
    tbl_order.total_order_cost_new,
    tbl_order.follow_up_date,
    tbl_order.Price_value,
    tbl_order.orders_status,
    tbl_order.ensure_sale_month,
    tbl_order.shipping_company,
    tbl_order.billing_company,
    tbl_order.hot_offer,
    tbl_order.shipping_country_name,
    tbl_order.shipping_country_name as shipping_country,	
    tbl_order.shipping_zip_code,
    tbl_order.shipping_state,
    tbl_order.shipping_city,
    tbl_order.date_ordered,
    tbl_order.customers_name, 
    tbl_order.customers_email, 
    tbl_order.shipping_street_address AS shipping_address, 
    tbl_order.edited_enq_id, 
    tbl_order.offer_type, 
    tbl_order.offer_currency, 
    tbl_order.customers_contact_no, 
    tbl_order.time_ordered, 
    tbl_order.total_order_cost, 
    tbl_order.offer_warranty, 
    tbl_order.delivery_day, 
    tbl_order.offer_validity, 
    tbl_order.show_discount, 
    tbl_order.offer_subject, 
    tbl_order.order_in_favor_of, 
    tbl_order.payment_terms, 
    tbl_order.offer_probability, 
    tbl_order.order_by, 
    tbl_order.lead_id,
    tbl_order.tes_linked_customer_id,
    tbl_order.order_type,
    tbl_order.payment_mode,
    tbl_order.shipping_name,
    tbl_order.shipping_telephone_no,
    tbl_order.shipping_fax_no,
    tbl_order.billing_name,
    tbl_order.billing_street_address,
    tbl_order.billing_city,
    tbl_order.billing_zip_code,
    tbl_order.billing_state,
    tbl_order.billing_country_name,
    tbl_order.billing_telephone_no,
    tbl_order.billing_fax_no,
    tbl_order.last_modified,
    tbl_order.shipComment,
    tbl_order.trackingNo,
    tbl_order.orders_date_finished,
    tbl_order.coupon_discount,
    tbl_order.coupon_id,
    tbl_order.coupon_type,
    tbl_order.coupon_value,
    tbl_order.cyber_amount,
    tbl_order.cyber_credit,
    tbl_order.tax_cost,
    tbl_order.shipping_method_cost,
    tbl_order.additional_disc,
    tbl_order.tax_per_amt,
    tbl_order.discount_per_amt,
    tbl_order.taxes_perc,
    tbl_order.discount_perc,
    tbl_order.offer_calibration,
    tbl_order.tax_included,
    tbl_order.tds_check_on_portal,
    tbl_order.freight_amount,
    tbl_order.ensure_sale_month_date,

    DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
    tbl_web_enq_edit.Enq_Date AS full_enq_datetime,
    DATE(tbl_web_enq_edit.Enq_Date) AS enq_date_only,
    tbl_web_enq_edit.order_id, 
    tbl_web_enq_edit.enq_id,
    tbl_web_enq_edit.ID,
	tbl_web_enq_edit.enq_type,
    tbl_web_enq_edit.Cus_name,
    tbl_web_enq_edit.Cus_email,     
    tbl_web_enq_edit.Cus_mob,
    tbl_web_enq_edit.Cus_msg,
    tbl_web_enq_edit.city,
    tbl_web_enq_edit.state,
    tbl_web_enq_edit.ref_source,
    tbl_web_enq_edit.cust_segment,
    tbl_web_enq_edit.old_enq_date,  
    tbl_web_enq_edit.hot_productnote,
    tbl_web_enq_edit.hot_productnoteother,
    tbl_web_enq_edit.dead_duck,
    tbl_web_enq_edit.enq_remark_edited,
    tbl_web_enq_edit.acc_manager,
    tbl_web_enq_edit.product_category,
    tbl_web_enq_edit.deleteflag,
    tbl_web_enq_edit.enq_stage,
	tbl_comp.comp_name as company_full_name
        FROM tbl_order
        RIGHT JOIN tbl_web_enq_edit ON tbl_order.orders_id=tbl_web_enq_edit.order_id  
		RIGHT JOIN tbl_comp ON tbl_order.customers_id = tbl_comp.id
        where tbl_web_enq_edit.deleteflag='active' 
        and MONTH( tbl_order.follow_up_date) IN ('$currmonthsel')
        $date_filter
        $searchRecord order by tbl_order.follow_up_date desc  LIMIT $from, $max_results ";
        $offers_requiring_review =  DB::select(($sql_order_follow_up));
		
		
// Convert and enrich each record
$enriched_offers = array_map(function ($row) {
    $rowArray = (array) $row;

    // Get currency details
    $offer_currency = $rowArray['offer_currency'] ?? '';
    $offer_currency_details = currencySymbol($offer_currency);

    // Add currency fields
    $rowArray['currency_symbol'] = $offer_currency_details[0] ?? '';
    $rowArray['currency_value'] = $offer_currency_details[1] ?? '';
    $rowArray['currency_css_symbol'] = $offer_currency_details[2] ?? '';

    return $rowArray;
}, $offers_requiring_review);		
		
		
        $offers_requiring_review_count = count($offers_requiring_review);

        return response()->json([            
        'offers_requiring_review' => $enriched_offers, 
        'offers_requiring_review_count' => $offers_requiring_review_count, 
        ]);
    }

    public function dashboard_high_value_case_enquiries(Request $request)
    {        
        
        $acc_manager			= $request->acc_manager;  
//        $month			= $month = date('m');

		$ensure_sale_month	= $request->ensure_sale_month; 
		
		
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
    	$max_results = 10; 
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
			 
		if($ensure_sale_month=='' || $ensure_sale_month=='0')
		{
		
        $month				= $month = date('m');
		}
		else
		{
	   $month				= $month = $ensure_sale_month;
		}

        $financial_year		= $request->financial_year; 

        $acc_manager_search = "";
        $acc_manager_search_escalation = "";
        $ref_source_request 			= $request->ref_source_request;
	       
        if($financial_year == '')
        {
            if (date('m')>3) {
                $year = date('Y').'-'.(date('Y')+1);
            } else {
                $year = (date('Y')-1).'-'.date('Y');
            }

            $financial_year = $year;
            $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode = explode("-",$financial_year);

        

        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
        {
            $acc_manager_search_escalation=" and tbl_web_enq_edit.acc_manager  IN ($acc_manager) ";
        } 


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


        $offer_probability_search_rec = " and t1.offer_probability IN (4,5)";	
        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
        {
            $acc_manager_search = " and order_by='$acc_manager'";
        }
        
        $date_filter_hvc = "AND ( t1.follow_up_date BETWEEN '$q1_start_date_show' AND '$q4_start_date_show' ) "; 
        $searchRecord_opportunities 	= "$acc_manager_search $offer_probability_search_rec $date_filter_hvc ";
        $hvc_value="1000001";

     $sql_opportunities_hvc = "SELECT 
         t3.time_lead_added,
    t3.enq_id,
    t1.orders_id,
    t1.ensure_sale_month,
    t1.total_order_cost_new,
    t1.customers_id,
    t1.customers_name,
    t1.customers_email,
    t1.customers_contact_no,
    t1.Price_type,
    t1.offercode,
    t1.date_ordered,
    t1.time_ordered,
    t1.order_by,
    t1.lead_id,
    t1.offer_probability,
    t1.shipping_company,
    t1.billing_company,
    t1.hot_offer,
    t1.shipping_country_name as shipping_country,
    t1.shipping_zip_code,
    t1.shipping_state,
    t1.shipping_city,
    t1.follow_up_date,
    t1.Price_value,
    t1.orders_status,
    t1.shipping_street_address AS shipping_address,
    t1.edited_enq_id,
    t1.offer_type,
    t1.offer_currency,
    t1.total_order_cost,
    t1.offer_warranty,
    t1.delivery_day,
    t1.offer_validity,
    t1.show_discount,
    t1.offer_subject,
    t1.order_in_favor_of,
    t1.payment_terms,
    t1.tes_linked_customer_id,
    t1.order_type,
    t1.payment_mode,
    t1.shipping_name,
    t1.shipping_telephone_no,
    t1.shipping_fax_no,
    t1.billing_name,
    t1.billing_street_address,
    t1.billing_city,
    t1.billing_zip_code,
    t1.billing_state,
    t1.billing_country_name,
    t1.billing_telephone_no,
    t1.billing_fax_no,
    t1.last_modified,
    t1.shipComment,
    t1.trackingNo,
    t1.orders_date_finished,
    t1.coupon_discount,
    t1.coupon_id,
    t1.coupon_type,
    t1.coupon_value,
    t1.cyber_amount,
    t1.cyber_credit,
    t1.tax_cost,
    t1.shipping_method_cost,
    t1.additional_disc,
    t1.tax_per_amt,
    t1.discount_per_amt,
    t1.taxes_perc,
    t1.discount_perc,
    t1.offer_calibration,
    t1.tax_included,
    t1.tds_check_on_portal,
    t1.freight_amount,
    t1.ensure_sale_month_date,
t1.ensure_sale_month as ensure_sale_month_old,
	t1.ensure_sale_month_date,	
	DATE_FORMAT(t1.ensure_sale_month_date, '%Y-%m') AS ensure_sale_month,		
   
    DATEDIFF(CURDATE(), tbl_web_enq_edit.Enq_Date) AS days_since_enq,
    tbl_web_enq_edit.Enq_Date AS full_enq_datetime,
    DATE(tbl_web_enq_edit.Enq_Date) AS enq_date_only,
    tbl_web_enq_edit.order_id,
    tbl_web_enq_edit.enq_id,
    tbl_web_enq_edit.ID,
    tbl_web_enq_edit.Cus_name,
    tbl_web_enq_edit.Cus_email,
    tbl_web_enq_edit.Cus_mob,
    tbl_web_enq_edit.Cus_msg,
	tbl_web_enq_edit.enq_type,
    tbl_web_enq_edit.city,
    tbl_web_enq_edit.state,
    tbl_web_enq_edit.ref_source,
    tbl_web_enq_edit.cust_segment,
    tbl_web_enq_edit.old_enq_date,
    tbl_web_enq_edit.hot_productnote,
    tbl_web_enq_edit.hot_productnoteother,
    tbl_web_enq_edit.dead_duck,
    tbl_web_enq_edit.enq_remark_edited,
    tbl_web_enq_edit.acc_manager,
    tbl_web_enq_edit.product_category,
    tbl_web_enq_edit.deleteflag,
    tbl_web_enq_edit.enq_stage,

    tbl_comp.comp_name AS company_full_name,
    t2.pro_id,
    t2.pro_name

FROM tbl_order AS t1
INNER JOIN tbl_order_product AS t2 ON t1.orders_id = t2.order_id
INNER JOIN tbl_lead AS t3 ON t1.lead_id = t3.id
RIGHT JOIN tbl_comp ON t1.customers_id = tbl_comp.id
LEFT JOIN tbl_web_enq_edit ON t1.orders_id = tbl_web_enq_edit.order_id
        where 1=1 $searchRecord_opportunities 
        and ensure_sale_month='$month'
        $date_filter_hvc
        and t1.total_order_cost_new > $hvc_value
        group by t1.orders_id order by  t1.orders_id desc   LIMIT $from, $max_results ";  
        
        $opportunities_data_hvc =  DB::select(($sql_opportunities_hvc));



// Convert and enrich each record
$enriched_opportunities_data_hvc = array_map(function ($row) {
    $rowArray = (array) $row;

    // Get currency details
    $offer_currency = $rowArray['offer_currency'] ?? '';
    $offer_currency_details = currencySymbol($offer_currency);

    // Add currency fields
    $rowArray['currency_symbol'] = $offer_currency_details[0] ?? '';
    $rowArray['currency_value'] = $offer_currency_details[1] ?? '';
    $rowArray['currency_css_symbol'] = $offer_currency_details[2] ?? '';

    return $rowArray;
}, $opportunities_data_hvc);	


        $opportunities_data_hvc_count = count($opportunities_data_hvc);

        return response()->json([            
        'opportunities_data_hvc' => $enriched_opportunities_data_hvc, 
        'opportunities_data_hvc_count' => $opportunities_data_hvc_count, 
        ]);
    }

    public function dashboard_account_receivables(Request $request)
    {
	$financial_year			= $request->financial_year; 
	    $acc_manager_request	= $request->acc_manager; 
		$month					= $request->month;

        $acc_manager_search_receivable="";

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
    	$max_results = 10; 
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


        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//            $acc_manager_search_receivable=" and tti.prepared_by='$acc_manager_request' ";
            $acc_manager_search_receivable=" and tti.prepared_by IN($acc_manager_request) ";
            //$acc_manager_search_receivable=" and prepared_by='$acc_manager_request' ";
        } 
	

       
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


		if($month=='' && $month=='0')
		{
        $month= date('m');
		}
		else
		{
			$month = $request->month;
		}

        if($q1_start_date_show!='')
        {
            $fy_date_filter_invoice_date_by_payment_terms=" and DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)  BETWEEN '$q1_start_date_show' AND '$q4_end_date_show' ";
        }
        else
        {
            $fy_date_filter_invoice_date_by_payment_terms="";
        }


/*		if($hot_offer_month!='' && $hot_offer_month!='0')
{
$search_acc_manager_acc_receivables_month=" AND DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) >= CURDATE() - INTERVAL $hot_offer_month MONTH";
}
else
{
	$search_acc_manager_acc_receivables_month=" ";
}*/

		

        if($month!='' && $month!='0' && $month!='All')
        {
            $search_acc_manager_acc_receivables_month= " and MONTH(DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) IN ('".$month."') ";
            //$ensure_sale_month_filter_payment_received= " and MONTH((tbl_payment_received.inserted_date)) IN ('$month')";
        }
        else
        {
            $search_acc_manager_acc_receivables_month = "  ";
         //   $ensure_sale_month_filter_payment_received = "";
        }
//.$fy_date_filter_invoice_date_by_payment_terms
        $searchRecord_payment_receivable = $acc_manager_search_receivable.$search_acc_manager_acc_receivables_month;

        $sql_receivable="SELECT 
        tbl_lead.id as lead_id,
        tbl_lead.enq_id,
        tti.invoice_id, 
        tti.invoice_generated_date, 
        tti.cus_com_name, 
        tti.con_name, 
        tti.con_mobile, 
        tti.con_email, 
        tti.invoice_status, 
        tti.freight_amount, 
        tti.freight_gst_amount, 
        tti.sub_total_amount_without_gst, 
        tti.total_gst_amount, 
		(tti.sub_total_amount_without_gst + tti.total_gst_amount + tti.freight_amount) * tti.exchange_rate as total_invoice_amount,		
        (tpr.payment_received_value) as payment_received,
        ((tti.freight_amount+tti.sub_total_amount_without_gst+tti.total_gst_amount)* tti.exchange_rate)  -  IFNULL((tpr.payment_received_value),0) as  total_balance_amount,
        tti.gst_sale_type, 
		tti.payment_terms, 
        tti.exchange_rate,
        tti.invoice_currency, 
        tti.prepared_by, 
        tti.buyer_gst, 
        tti.o_id, 
        tti.payment_terms, 
        tti.invoice_closed_status,
        s.supply_order_payment_terms_abbrv,
        s.supply_order_payment_terms_id,
        s.supply_order_payment_terms_name,
        DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)  AS  invoice_date_with_payment_terms,
        DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging
        from tbl_tax_invoice tti 
        INNER JOIN tbl_supply_order_payment_terms_master s
        LEFT JOIN tbl_tax_credit_note_invoice ttcni ON ttcni.invoice_id=tti.invoice_id
		INNER JOIN tbl_lead ON tbl_lead.id=tti.o_id
		LEFT JOIN tbl_payment_received tpr ON tti.invoice_id=tpr.invoice_id 
        where 
        tti.payment_terms=s.supply_order_payment_terms_id
        and tti.invoice_id>230000
        AND ttcni.invoice_id IS NULL
        and tti.invoice_status='approved'
        and tti.invoice_closed_status='No'
        $searchRecord_payment_receivable
        ORDER BY aging DESC LIMIT $from, $max_results "; 


        //$sql_receivable = "SELECT * FROM view_invoice_details WHERE 1 $acc_manager_search_receivable";

        $accounts_receivable =  DB::select(($sql_receivable));
        $accounts_receivable_count=count($accounts_receivable);

        return response()->json([            
            'accounts_receivable' => $accounts_receivable, 
            'accounts_receivable_count' => $accounts_receivable_count, 
        ]);

    }

    public function dashboard_payment_received(Request $request)
    {
		
		$financial_year			= $request->financial_year; 
	    $acc_manager_request	= $request->acc_manager; 
		$month 					= $request->month;
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
				$max_results = 10; 
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

        $acc_manager_search_payment = "";
		if($month=='' && $month=='0')
		{
        $month= date('m');
		}
		else
		{
			$month = $request->month;
		}

        if($q1_start_date_show!='')
        {
            $fy_date_filter_payment_recd=" and tbl_payment_received.inserted_date BETWEEN '$q1_start_date_show' AND '$q4_end_date_show' ";
        }
        else
        {
            $fy_date_filter_payment_recd="";
        }

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//        $acc_manager_search_payment=" and tbl_tax_invoice.prepared_by='$acc_manager_request' ";
        $acc_manager_search_payment=" and tbl_tax_invoice.prepared_by IN($acc_manager_request) ";
        }       
        
        if($month!='' && $month!='0' && $month!='All')
        {
            $ensure_sale_month_filter_ac_receivables= " and MONTH(DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) IN ('".$month."') ";
            $ensure_sale_month_filter_payment_received= " and MONTH((tbl_payment_received.inserted_date)) IN ('$month')";
        }
        else
        {
            $ensure_sale_month_filter_ac_receivables = "  ";
            $ensure_sale_month_filter_payment_received = "";
        }

        $searchRecord_payment_recd = $acc_manager_search_payment.$fy_date_filter_payment_recd.$ensure_sale_month_filter_payment_received;

$sql_payment_recd	= "SELECT 
tbl_lead.id as lead_id,
tbl_lead.enq_id,
tbl_tax_invoice.invoice_id,
tbl_tax_invoice.cus_com_name,
tbl_tax_invoice.buyer_name,
tbl_tax_invoice.buyer_mobile,
tbl_tax_invoice.buyer_email,
tbl_tax_invoice.o_id,
tbl_tax_invoice.invoice_status,
tbl_payment_received.o_id, 
tbl_payment_received.invoice_id, 
tbl_payment_received.payment_received_via, 
tbl_payment_received.inserted_date, 
tbl_payment_received.payment_received_value, 
tbl_payment_received.payment_received_in_bank, 
tbl_payment_received.payment_received_date, 
tbl_payment_received.transaction_id, 
tbl_payment_received.inserted_date 
FROM tbl_tax_invoice 
RIGHT JOIN tbl_payment_received ON tbl_tax_invoice.o_id=tbl_payment_received.o_id 
INNER JOIN tbl_lead ON tbl_lead.id=tbl_tax_invoice.o_id
where tbl_payment_received.deleteflag='active'
 and tbl_payment_received.invoice_id!='0' 
        $searchRecord_payment_recd 
        order by tbl_payment_received.payment_received_date  LIMIT $from, $max_results ";

        $payments_received =  DB::select(($sql_payment_recd));
        $payments_received_count=count($payments_received);

        return response()->json([            
            'payments_received' => $payments_received, 
            'payments_received_count' => $payments_received_count, 
        ]);
    }

    public function dashboard_open_tenders(Request $request)
    {
        $acc_manager_request			= $request->acc_manager;
        $acc_manager_tender_search = "";



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
    	$max_results = 10; 
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

        if($acc_manager_request!='' && $acc_manager_request!='0' && $acc_manager_request!='All')
        {
//            $acc_manager_tender_search="and acc_manager='$acc_manager_request'";
            $acc_manager_tender_search="and acc_manager IN($acc_manager_request)";
        }
        
        $sql_tender_assigned	= "SELECT  id,tnd_id,customer_name,tnd_value,due_on,acc_manager,hot_productnoteother,
        tnd_status,
        tnd_technical_bid,tnd_tech_document,tnd_commercial_bid,tender_identified_on 
        from tbl_tender where tnd_del_status = 'active' and acc_manager!='0' $acc_manager_tender_search 
        AND tnd_status NOT IN ('Dead', 'lost') 
        order by due_on desc  LIMIT $from, $max_results ";

        $open_tenders =  DB::select(($sql_tender_assigned));
        $open_tenders_count=count($open_tenders);

        return response()->json([            
            'open_tenders' => $open_tenders, 
            'open_tenders_count' => $open_tenders_count, 
        ]);
    }

    public function dashboard_latest_price_updates(Request $request)
    {
        $acc_manager_request			= $request->acc_manager;
        $today_date				= date("Y-m-d");
        
        $today_date_pqv=date("Y-m-d",strtotime($today_date . ' +1 day'));
        $today_date_end_30=date('Y-m-d',strtotime($today_date . ' -30 day'));


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
    	$max_results = 10; 
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

        $sql_latest_price_update = "SELECT tbl_products.pro_id, 
		tbl_products.pro_title, 
		tbl_application.application_name, 
		tbl_products.upc_code, 
		tbl_products.ware_house_stock, 
		tbl_products_entry.pro_price_entry, 
	tbl_products_entry.pro_desc_entry as product_description,	 
	tbl_products_entry.app_cat_id as app_cat_id_multi, 
	tbl_products_entry.last_modified, 
	tbl_products_entry.price_list, 
	tbl_products_entry.model_no FROM 
	tbl_products_entry 
        left join tbl_products on tbl_products.pro_id=tbl_products_entry.pro_id
        left join tbl_application on tbl_products_entry.app_cat_id=tbl_application.application_id
        
        WHERE tbl_products.deleteflag = 'active' 
		AND tbl_products.STATUS = 'active' 
		AND tbl_products_entry.deleteflag = 'active' 
		AND tbl_products_entry.STATUS = 'active' 
		AND tbl_products_entry.price_list!='' 
		and tbl_products_entry.price_list='pvt' and tbl_products.product_type_class_id IN ('1','2') 
        and `tbl_products_entry`.last_modified BETWEEN '$today_date_end_30' AND '$today_date_pqv'
        ORDER BY `tbl_products_entry`.`last_modified` DESC  LIMIT $from, $max_results ";
          
        $latest_price_update =  DB::select(($sql_latest_price_update));
        $latest_price_update_count=count($latest_price_update);

        return response()->json([            
            'latest_price_update' => $latest_price_update, 
            'latest_price_update_count' => $latest_price_update_count, 
        ]);

    }

    public function get_company_name_extn(Request $request)
    { 
        $customers_id = $request->customers_id;
        $row =DB::table('tbl_comp')
            ->select(            
            ("IF(tbl_company_extn.company_extn_name != 'None *', CONCAT(tbl_comp.comp_name, ' ', tbl_company_extn.company_extn_name), tbl_comp.comp_name) as full_name")
            )
            ->join('tbl_company_extn', 'tbl_comp.co_extn_id', '=', 'tbl_company_extn.company_extn_id')
            ->where('tbl_comp.id','=',$customers_id)
            ->first();

        return response()->json([ 
            'full_name' => $row->full_name, 
        ]);
    }

    public function dashboard_potential_sale_charts(Request $request)
    { 
        $acc_manager            = $request->acc_manager;
        
        $financial_year			= $request->financial_year; 
        $month					= $request->month; 
        $hot_offer_month		= $request->month; 		
//        $hot_offer_month		= $request->hot_offer_month; 

        if($financial_year=='')
        {
            if (date('m')>3) {
                $year = date('Y').'-'.(date('Y')+1);
            } else {
                $year = (date('Y')-1).'-'.date('Y');
            }

        $financial_year = $year;

        $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode=explode("-",$financial_year);
        
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
       
        $order_executed_bar_graph_yearly = round(qtr_target_achived_by_invoice_for_graph($acc_manager,$q1_start_date_show,$q4_end_date_show,$hot_offer_month),2);
        $order_in_hand_bar_graph_yearly = round($qtr_target_achived_by_stage=sales_dashboard_orders_in_hand_value($acc_manager,$q1_start_date_show,$q4_end_date_show,'5',$hot_offer_month));
        $opportunities_bar_graph_yearly = round($qtr_target_achived_by_stage=opportunity_value_for_dashboard($acc_manager,$q1_start_date_show,$q4_end_date_show,'4',$hot_offer_month));
        $hot_offer_bar_graph_yearly = round(hot_offer_value_for_dashboard($acc_manager,$q1_start_date_show,$q4_end_date_show,'1',$hot_offer_month));
        $revenue_tracker_total = $order_executed_bar_graph_yearly+$order_in_hand_bar_graph_yearly+$opportunities_bar_graph_yearly+$hot_offer_bar_graph_yearly;

        return response()->json([ 
            'order_executed_bar_graph_yearly' => $order_executed_bar_graph_yearly, 
            'order_in_hand_bar_graph_yearly' => $order_in_hand_bar_graph_yearly,
            'opportunities_bar_graph_yearly' => $opportunities_bar_graph_yearly,
            'hot_offer_bar_graph_yearly' => $hot_offer_bar_graph_yearly,
        ]);
    }


    public function dashboard_potential_trend_charts(Request $request)
    { 
        $acc_manager            = $request->acc_manager;
        $financial_year			= $request->financial_year; 
        $month					= $request->month; 
        $hot_offer_month		= $request->month; 

        if($financial_year=='')
        {
            if (date('m')>3) {
                $year = date('Y').'-'.(date('Y')+1);
            } else {
                $year = (date('Y')-1).'-'.date('Y');
            }

        $financial_year 		= $year;

        $fin_yr					= show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr				= $financial_year;
        }

        $financial_year_explode = explode("-",$financial_year);
        
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
        $orders_count_this_fy_yr=orders_count_this_fy_yr($acc_manager,$q1_start_date_show,$q4_end_date_show,$month);

        $valchart_order_count = [];
        $valchart_enq_count = [];
        $valchart_invoice_count = [];
        $groupchart_order_month = [];
        $groupchart_enq_month = [];
        $groupchart_enq_month_name = [];
        $groupchart_invoice_month_name = [];
        $groupchart_invoice_month = [];

        $groupchart_lead_month_name = [];
        $groupchart_lead_month = [];


        if($orders_count_this_fy_yr>0)
        {
        foreach($orders_count_this_fy_yr as $o => $result_orders_count_this_fy_yr) {
        $groupchart_order_month[] = substr($result_orders_count_this_fy_yr->month_name,0,3);
        $valchart_order_count[] = $result_orders_count_this_fy_yr->total_order_count;
        }

        if($valchart_order_count>0)
        {
        $data_pie_chart_label_order_month = $groupchart_order_month;
        $data_pie_chart_order_series_month = $valchart_order_count;

        }
        else
        {
        $data_pie_chart_label_order_month = "0";
        $data_pie_chart_order_series_month = "0";

        }
        }
        else
        {
        $data_pie_chart_label_order_month = "0";
        $data_pie_chart_order_series_month = "0";

        }

        $sales_enquiry_count_this_fy_yr=sales_enquiry_count_this_fy_yr($acc_manager,$q1_start_date_show,$q4_end_date_show,$month);

        if($sales_enquiry_count_this_fy_yr>0)
        {
        foreach($sales_enquiry_count_this_fy_yr as $s => $result_sales_enquiry_count_this_fy_yr) {

        $groupchart_enq_month[] = $result_sales_enquiry_count_this_fy_yr->month;
        $groupchart_enq_month_name[] = $result_sales_enquiry_count_this_fy_yr->month_name;
        $valchart_enq_count[] = $result_sales_enquiry_count_this_fy_yr->total_sales_enq_count;
        }

        if($valchart_enq_count>0)
        {
        $data_pie_chart_label_enq_month = $groupchart_enq_month_name;
        $data_pie_chart_series_month = $valchart_enq_count;

        }
        else
        {
        $data_pie_chart_label_enq_month = "0";
        $data_pie_chart_series_month = "0";

        }
        }
        else
        {
        $data_pie_chart_label_enq_month = "0";
        $data_pie_chart_series_month = "0";

        }

        $invoice_count_this_fy_yr=total_invoice_count_this_fy_yr($acc_manager,$q1_start_date_show,$q4_end_date_show,$month);

        if($invoice_count_this_fy_yr>0)
        {
        foreach($invoice_count_this_fy_yr as $o => $result_invoice_count_this_fy_yr) {

        $groupchart_invoice_month[] = $result_invoice_count_this_fy_yr->month;
        $groupchart_invoice_month_name[] = $result_invoice_count_this_fy_yr->month_name;
        $valchart_invoice_count[] = $result_invoice_count_this_fy_yr->tot_invoice_count;
        }

        if($valchart_invoice_count>0)
        {
        $data_pie_chart_label_invoice_month = $groupchart_invoice_month_name;
        $data_pie_chart_invoice_series_month = $valchart_invoice_count;

        }
        else
        {
        $data_pie_chart_label_invoice_month = "0";
        $data_pie_chart_invoice_series_month = "0";

        }
        }

        else
        {
        $data_pie_chart_label_invoice_month = "0";
        $data_pie_chart_invoice_series_month = "0";
        }
		
		
		
 $lead_count_this_fy_yr=leads_count_this_fy_yr($acc_manager,$q1_start_date_show,$q4_end_date_show,$month);

        if($lead_count_this_fy_yr>0)
        {
        foreach($lead_count_this_fy_yr as $o => $result_lead_count_this_fy_yr) {

        $groupchart_lead_month[] = $result_lead_count_this_fy_yr->month;
        $groupchart_lead_month_name[] = $result_lead_count_this_fy_yr->month_name;
        $valchart_lead_count[] = $result_lead_count_this_fy_yr->total_leads_count;
        }

        if($valchart_lead_count>0)
        {
        $data_pie_chart_label_lead_month = $groupchart_lead_month_name;
        $data_pie_chart_lead_series_month = $valchart_lead_count;

        }
        else
        {
        $data_pie_chart_label_lead_month = [0];
        $data_pie_chart_lead_series_month = [0];

        }
        }

        else
        {
        $data_pie_chart_label_lead_month = [0];
        $data_pie_chart_lead_series_month = [0];
        }		

        return response()->json([ 
            'data_pie_chart_series_month' => $data_pie_chart_series_month, 
            'data_pie_chart_order_series_month' => $data_pie_chart_order_series_month,
            'data_pie_chart_invoice_series_month' => $data_pie_chart_invoice_series_month,
			'data_pie_chart_lead_series_month' => $data_pie_chart_lead_series_month,
            'data_pie_chart_label_order_month' => $data_pie_chart_label_order_month,
        ]);
}

public function dashboard_accounts_receivables_charts(Request $request)
{ 
        $acc_manager            = $request->acc_manager;
        $hot_offer_month		= $request->hot_offer_month;         
        $financial_year			= $request->financial_year;  

        if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
        {
            $search_acc_manager=" and prepared_by='$acc_manager'";
            $search_acc_manager_acc_receivables=" and tti.prepared_by='$acc_manager'";
            $acc_manager_search="and order_by='$acc_manager'";
            $acc_manager_search_follow_up="and o.order_by=$acc_manager";
        }
        else
        {
            $search_acc_manager=" ";
            $search_acc_manager_acc_receivables=" ";
            $acc_manager_search=" ";
            $acc_manager_search_follow_up="";
        }

        if($hot_offer_month!='' && $hot_offer_month!='0')
        {
        $search_acc_manager_acc_receivables_month=" AND DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY) >= CURDATE() - INTERVAL $hot_offer_month MONTH";
        }
        else
        {
            $search_acc_manager_acc_receivables_month=" ";
        }

        $sql_receivable_result_pie_chart="SELECT 
            tti.cus_com_name,
            tti.invoice_id,
            tti.prepared_by,
            tti.exchange_rate,
            tti.invoice_currency,
            SUM((tti.freight_amount + tti.sub_total_amount_without_gst + tti.total_gst_amount) * tti.exchange_rate) - IFNULL(SUM(tpr.payment_received), 0) AS total_payment,
        DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging,
        DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)  AS  invoice_date_with_payment_terms
        FROM 
            tbl_tax_invoice tti
        LEFT JOIN 
            (SELECT 
                invoice_id,
                SUM(payment_received_value) AS payment_received 
            FROM 
                tbl_payment_received 
            GROUP BY 
                invoice_id) tpr 
        ON 
            tti.invoice_id = tpr.invoice_id
        LEFT JOIN 
            tbl_tax_credit_note_invoice ttcni 
        ON 
            ttcni.invoice_id = tti.invoice_id
        INNER JOIN 
            tbl_supply_order_payment_terms_master s 
        ON 
            tti.payment_terms = s.supply_order_payment_terms_id
        WHERE 
            tti.invoice_id > 230000 
            AND tti.invoice_closed_status = 'No' 
            AND tti.invoice_status = 'approved' 
            AND ttcni.invoice_id IS NULL 
        $search_acc_manager_acc_receivables
        $search_acc_manager_acc_receivables_month

        GROUP BY 
            tti.cus_com_name
        ORDER BY 
            total_payment DESC limit 0,12";

        $result_pie_chart =  DB::select(($sql_receivable_result_pie_chart));
        $result_pie_chart_count=count($result_pie_chart);

        foreach($result_pie_chart as $i => $result_pie_chart_array) {
            $groupchart[] = ($result_pie_chart_array->cus_com_name);
            $valchart[] = round($result_pie_chart_array->total_payment,1);
        }

        if($result_pie_chart_count>0)
        {
        $data_pie_chart_label = json_encode($groupchart);
        $data_pie_chart_series = json_encode($valchart);
        $data_pie_chart_label1 = $groupchart;
        $data_pie_chart_series1 = $valchart;
        }
        else
        {
        $data_pie_chart_label = "0";
        $data_pie_chart_series = "0";
        $data_pie_chart_label1 = 0;
        $data_pie_chart_series1 = 0;

        }

        return response()->json([ 
            'data_pie_chart_series' => $data_pie_chart_series, 
            'data_pie_chart_label' => $data_pie_chart_label
        ]);
}
public function dashboard_revenue_by_company_charts(Request $request)
{
    $validator = Validator::make($request->all(), [
        'financial_year' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 400);
    }

    $accountManager = $request->acc_manager;
    if (!empty($accountManager)) {
        $accountManager = explode(',', $accountManager);
    }

    $financial_year = $request->financial_year;
    $month = $request->month;

      [$startYear, $endYear] = explode('-', $financial_year);

    // Determine date range
    if ($month) {
        $year = ($month >= 1 && $month <= 3) ? $endYear : $startYear;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();
    } else {
        $startDate = Carbon::createFromDate($startYear, 4, 1)->startOfDay();
        $endDate = Carbon::createFromDate($endYear, 3, 31)->endOfDay();
    }

    // Get approved invoices within date range
    $invoices = TaxInvoice::with('order')
        ->where('invoice_status', 'approved')
        ->whereBetween('invoice_generated_date', [$startDate, $endDate])
        ->when(!empty($accountManager), function ($q) use ($accountManager) {
            $q->whereIn('prepared_by', $accountManager);
        })
        ->get();

    // Group invoices by customer
    $grouped = $invoices->groupBy(fn($invoice) => $invoice->order->customers_id ?? null)
        ->filter(); // remove null keys

    // Prepare top customers list
    $customers = $grouped->map(function ($invoices, $customer_id) {
        $first = $invoices->first();
        $order = $first->order;
        $lead_id = $order?->lead_id;

        $avg_days = $invoices->map(function ($invoice) use ($order) {
            return $order ? Carbon::parse($order->date_ordered)->diffInDays($invoice->invoice_generated_date) : null;
        })->filter()->avg();

        return (object)[
            'customers_id' => $customer_id,
            'cus_com_name' => $first->cus_com_name,
            'lead_id' => $lead_id,
            'days' => round($avg_days),
            'tot_value' => $invoices->sum('sub_total_amount_without_gst'),
        ];
    })->sortByDesc('tot_value')->take(50)->values();

    if ($customers->isEmpty()) {
        return response()->json([
            'status' => 'no_data',
            'message' => 'No top customers found.',
        ], 200);
    }

    // Prepare chart labels and values
    $labels = [];
    $revenues = [];

    foreach ($customers as $customer) {
        $labels[] = trim($customer->cus_com_name);
        $revenues[] = (int) round($customer->tot_value);
    }

    return response()->json([
        'data_pie_chart_series_rev_by_cus' => json_encode($revenues),
        'data_pie_chart_label_rev_by_cus' => json_encode($labels),
    ]);
}

public function dashboard_revenue_by_company_charts1(Request $request)
{ 
        $acc_manager            = $request->acc_manager;
        $hot_offer_month		= $request->hot_offer_month;         
        $financial_year			= $request->financial_year; 

        if($financial_year=='')
        {
            if (date('m')>3) {
                $year = date('Y').'-'.(date('Y')+1);
            } else {
                $year = (date('Y')-1).'-'.date('Y');
            }
        $financial_year = $year;
        $fin_yr=show_financial_year_id($financial_year);
        }
        else
        {
            $fin_yr= $financial_year;
        }

        $financial_year_explode=explode("-",$financial_year);

        if($acc_manager!='' && $acc_manager!='0')
        {
            $acc_manager_search = " and tti.prepared_by='$acc_manager' ";
        }
        else
        {
            $acc_manager_search = " ";
        }
            
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
            
            if($hot_offer_month!='' && $hot_offer_month!='0')
            {
            $search_acc_manager_revenue_by_co_month=" and tti.invoice_generated_date >= CURDATE() - INTERVAL $hot_offer_month MONTH ";
            }
            else
            {
                $search_acc_manager_revenue_by_co_month=" ";
            }   
            
            $sql_revenue_by_co_result_pie_chart = "SELECT 
            ROUND(AVG(DATEDIFF(tti.invoice_generated_date,o.date_ordered))) as days,
            o.customers_id, 
            o.lead_id,
            tti.cus_com_name, 
            tti.sub_total_amount_without_gst, 
            SUM(tti.sub_total_amount_without_gst) as tot_value 
            FROM tbl_tax_invoice tti 
            INNER JOIN tbl_order o ON tti.o_id=o.orders_id 
            where 1=1 AND tti.invoice_status='approved' 
            AND ( tti.invoice_generated_date BETWEEN '$q1_start_date_show' AND '$q4_start_date_show' ) 
            $acc_manager_search
            $search_acc_manager_revenue_by_co_month
            GROUP by o.customers_id ORDER BY tot_value DESC
            limit 0,12 ";
                
            $result_pie_chart_revenue_by_co =  DB::select(($sql_revenue_by_co_result_pie_chart));
            $result_pie_chart_revenue_by_co_count=count($result_pie_chart_revenue_by_co);    
            
            foreach($result_pie_chart_revenue_by_co as $j => $result_pie_chart_array_rev_by_co) {
                $groupchart_rev_by_co[] = ($result_pie_chart_array_rev_by_co->cus_com_name);
                $valchart_rev_by_co[] = round($result_pie_chart_array_rev_by_co->tot_value,1);
            }
            
            if($result_pie_chart_revenue_by_co_count>0)
            {
            $data_pie_chart_label_rev_by_cus = json_encode($groupchart_rev_by_co);
            $data_pie_chart_series_rev_by_cus = json_encode($valchart_rev_by_co);
            $data_pie_chart_label_rev_by_cus1 = $groupchart_rev_by_co;
            $data_pie_chart_series_rev_by_cus1 = $valchart_rev_by_co;
            
            }
            else
            {
            $data_pie_chart_label_rev_by_cus 	= "0";
            $data_pie_chart_series_rev_by_cus 	= "0";
            $data_pie_chart_label_rev_by_cus1 	= "0";
            $data_pie_chart_series_rev_by_cus1 	= "0";      
            }

            return response()->json([ 
                'data_pie_chart_series_rev_by_cus' => $data_pie_chart_series_rev_by_cus, 
                'data_pie_chart_label_rev_by_cus' => $data_pie_chart_label_rev_by_cus
            ]);
    }

    public function hot_unhot_enquiry(Request $request)
    {
            $date 	  		= date('Y-m-d');        
            $hot_enquiry	= $request->hot_enquiry;
            $enq_id			= $request->ID;
           
            if($hot_enquiry!='') {            
                $ArrayData['hot_offer'] 		= $hot_enquiry;
                DB::table('tbl_order')
                    ->where('edited_enq_id', $enq_id)
                    ->update($ArrayData); 

                $ArrayData_enq['hot_enquiry'] 	= $hot_enquiry;
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

        return response()->json([            
            'success' => $success, 
        ]);
    }

    public function month_sales_to_ensure_update(Request $request){      
        
        $dataArray  			= [];
        $success="false";

        if($request->order_id > 0 ){
            
            $date1 	  			= date('Y-m-d');
            $ensure_sale_month	= $request->month_sales_ensure_month;
			$ensure_sale_year	= $request->month_sales_ensure_year;
		 	$month 				= $ensure_sale_month; // Pass the month as an integer (e.g., 2 for February)


if($ensure_sale_year=='')
{

  // Get the current year
    $currentYear = (int)date('Y');
    
    // Determine the financial year
    if ($month < 4) {
        // If the month is before April, move to the next financial year
         $startYear = $currentYear;
        $endYear = $currentYear + 1;
    } else {
        // Otherwise, financial year starts in the current year
        $startYear = $currentYear;
        $endYear = $currentYear + 1;
    }
 $startYear  ."-".  $endYear ; 


    if ($month < 4) {
		$ensure_year	=	$endYear;
	}
	else
	{
	$ensure_year=$startYear;
	}
		
            if($ensure_sale_month=='')
            {
                $ensure_sale_month="0";
                $month_sale_ensure_date 	  	= "1970-01-02 01:00:00";//date("Y-".$ensure_sale_month."-d");
            }
            else
            {
                $ensure_sale_month				= $request->month_sales_ensure_month;
                $ensure_sale_month1				= explode("-",$request->month_sales_ensure_month);
                //$month_sale_ensure_date 	  	= date($ensure_year."-".$ensure_sale_month."-d 01:00:00", strtotime('-4 day', strtotime($date1))); 
				  $month_sale_ensure_date 	  	= ($ensure_sale_month."-01 01:00:00"); 
            }
     
      $year = (date('Y')-1).'-'.date('Y');

// echo $year; //exit;



//echo "MONTH: ".$ensure_sale_month;
//echo "YEAR: ".$ensure_sale_year;


// "total date: ".$ensure_sale_year.'-'.$ensure_sale_month.'-'.'1'; 

"<br> :".$month_sale_ensure_date 	  	= $month_sale_ensure_date;//date($ensure_year."-".$ensure_sale_month."-d 01:00:00", strtotime(1));
}
else
{
	"<br> :".$month_sale_ensure_date 	  	= $month_sale_ensure_date;//date($ensure_sale_year."-".$ensure_sale_month."-d 01:00:00", strtotime(1));
}

              "====<br>ensure_sale_month:".   		$dataArray["ensure_sale_month"]			= $ensure_sale_month1[1];  
         "====<br>month_sale_ensure_date:".   $dataArray["ensure_sale_month_date"]	= $month_sale_ensure_date;   //exit;
            
            $result_st = DB::table('tbl_order')
            ->where('orders_id', $request->order_id)
            ->update($dataArray);

            $success="true";
        }        
               
        return response()->json([            
            'success' => $success, 
        ]);
    } 

    public function offer_stage_update(Request $request){   

        $success = "false"; 
        $date 	  	= date('Y-m-d');                    
        $offer_probability		= $request->offer_probability;
        $order_id				= $request->order_id;
        
        if($offer_probability!='') {
            
            $ArrayData['offer_probability'] = $offer_probability;
            $result_st1 = DB::table('tbl_order')
                ->where('orders_id', $order_id)
                ->update($ArrayData);
            
            $ArrayData_enq['enq_stage'] = $offer_probability;
            $ArrayData_enq['mel_updated_on'] = date("Y-m-d H:i:s");
            $result_st2 = DB::table('tbl_web_enq_edit')
                ->where('order_id', $order_id)
                ->update($ArrayData_enq);
            $success = "true";
        }        

        return response()->json([            
            'success' => $success, 
        ]);
    } 


    
    public function dashboard_overview(Request $request)
    {
        //account_receivables
        $company		= $request->company; 
        $financial_year		= $request->financial_year; 
        $acc_manager			= $request->acc_manager;  

        if($acc_manager=='')
        {
                $acc_manager			= "";
        }
        if($company=='')
        {
                $company			= "";
        }
        $payment_received	= payment_received_by_aging($acc_manager,0,0,$company);
        $pending_account_receivables = pending_account_receivables($acc_manager,0,0,'5',$company);
        $total_pending_payments = ($pending_account_receivables-$payment_received);
        $account_receivables	= $total_pending_payments;	
        //END account_receivables

        //orders_in_hand
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

        $tes_id		= get_tes_id($acc_manager,$fin_yr);

        if($acc_manager=='' || $acc_manager=='All')
        {
            $tes_id=$all_tes_id=get_all_tes_id_current_year($fin_yr);
        }
        else
        {               
            if($tes_id!='0' && $tes_id!='' )
            {
                $tes_id		= get_tes_id($acc_manager,$fin_yr);
            }
            else
            {
                $tes_id		= "0";			 
            }
        }

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
        $orders_in_hand			= (sales_dashboard_orders_in_hand_value($acc_manager,$q1_start_date_show,$q4_end_date_show,'5'));
        //END orders_in_hand

        //opportunities
        $opportunities			= (opportunity_value_for_dashboard($acc_manager,$q1_start_date_show,$q4_end_date_show,'4'));
        //END opportunities

        //total_executed
        if($tes_id!='' && $tes_id!='0')
        {
        $total_target	= TES_Total_Target($tes_id);            		 		 

        }
        else
        {
        $total_target	= '1000000000';            		 		 
        }
       $cr_note_amt		= sum_of_credit_note(0,$q1_start_date_show,$q4_end_date_show,$acc_manager,$aging_min=0,$aging_max=0,$company_name=0);
       $this_fy_year		= invoice_total_by_date($acc_manager,$q1_start_date_show,$q4_end_date_show);  
       $total_executed		= ($this_fy_year-$cr_note_amt);	
        //END total_executed



        return response()->json([ 
            'account_receivables' => $account_receivables, 
            'orders_in_hand' => $orders_in_hand, 
            'opportunities' => $opportunities,            
            'total_executed' => $total_executed, 
			'total_target' => $total_target, 
        ]);
    }


    
    public function test_auth(Request $request)
    {
        return response()->json([            
            'success' => 'true', 
        ]);
    }



   /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePostRequest $request)
    {
        $post = Post::create($request->all());

        return response()->json([
            'status' => true,
            'message' => "Post Created successfully!",
            'post' => $post
        ], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(StorePostRequest $request, Post $post)
    {
        $post->update($request->all());

        return response()->json([
            'status' => true,
            'message' => "Post Updated successfully!",
            'post' => $post
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return response()->json([
            'status' => true,
            'message' => "Post Deleted successfully!",
        ], 200);
    }
	
	
	
public function orders_executed_bar_graph(Request $request)
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



if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
{

$search_acc_manager=" and prepared_by='$acc_manager'";
$search_acc_manager_acc_receivables=" and tti.prepared_by='$acc_manager'";

    //$orders_status='Pending';
    $acc_manager_search="and order_by='$acc_manager'";
    $acc_manager_search_follow_up="and o.order_by=$acc_manager";
    }
else
{
	$search_acc_manager=" ";
$search_acc_manager_acc_receivables=" ";
}

    "<br> credit_note amt to be minus: ".$sql_receivable_result_pie_series2="select 
date_format(invoice_generated_date,'%b') as months,
SUM((sub_total_amount_without_gst ) * exchange_rate) as total_executed
from tbl_tax_invoice 
where invoice_id>23000 
and invoice_status='approved'
$search_acc_manager
and invoice_generated_date BETWEEN '".$q1_start_date_show."' AND '".$q4_end_date_show."' 
group by year(invoice_generated_date),
month(invoice_generated_date) ORDER by year(invoice_generated_date) asc, month(invoice_generated_date) asc"; //exit;
//$result_pie_series2  = mysqli_query($GLOBALS["___mysqli_ston"], $sql_receivable_result_pie_series2);
//$rsnum_receivable_pie_series2  = mysqli_num_rows($result_pie_series2);


$result_pie_series2 	=  DB::select(($sql_receivable_result_pie_series2));					
/*echo "<pre>";
print_r($result_pie_series2 );

exit;*/

/*while($row2 = mysqli_fetch_array($result_pie_series2))
{
//extract($row2);
/*echo "<pre>";
print_r($row2);*/
/*$group          = $row2["months"];
$val            = $row2["total_executed"]; 
$datapie2[]         = array("x"=>$group,"y"=>intval($val));
}*/

//$result_enq_paging_export_to_excel 	=  DB::select(($sql_paging_and_export_excel));					
//$num_rows							= count($result_enq_paging_export_to_excel); 

//
/*echo "<pre>";
    print_r($datapie2);*/
//  mysqli_close($GLOBALS["___mysqli_ston"]);
/*$data = json_encode($datapie);
$data2 = json_encode($datapie2);*/
return response()->json([ 
			'orders_executed_bar_grapg' => $result_pie_series2,		 
		]);
	
}
}//class closed
