<?php
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

function trace($request){
    return dd($request->input());
}
function changeDateFormate($date,$date_format){
    return \Carbon\Carbon::createFromFormat('Y-m-d', $date)->format($date_format); 
}
function productImagePath($image_name){
    return public_path('images/products/'.$image_name);
}

function product_acl_code($id){
    $res = DB::table('tbl_products_entry')->select('model_no')->where('pro_id', '=', $id)->first(); 
    return isset($res->model_no) ? $res->model_no : '';
}
function pro_upc_code($id){
    $res = DB::table('tbl_products')->select('upc_code')->where('pro_id', '=', $id)->first(); 
    return isset($res->upc_code) ? $res->upc_code : '';
}
function product_name($id){
    $res = DB::table('tbl_products')->select('pro_title')->where('pro_id', '=', $id)->first();     
    return isset($res->pro_title) ? $res->pro_title : '';
}
function product_stock($id){
    $res = DB::table('tbl_products')->select('ware_house_stock')->where('pro_id', '=', $id)->first(); 
    return isset($res->ware_house_stock) ? $res->ware_house_stock : '';
}
function product_price_entry($id){
    $res = DB::table('tbl_products_entry')->select('pro_price_entry')->where('pro_id', '=', $id)->where('price_list', '=', 'pvt')->first(); 
    return isset($res->pro_price_entry) ? $res->pro_price_entry : '';
}


function get_hsn_code_service_entry($id){
    $res = DB::table('tbl_services_entry')->select('hsn_code')->where('service_id_entry', '=', $id)->first(); 
    return isset($res->hsn_code) ? $res->hsn_code : '';
}

function getIDenq_id($eid){
    $res = DB::table('tbl_web_enq_edit')->select('ID')->where('enq_id', '=', $eid)->first(); 
    return isset($res->ID) ? $res->ID : '';
}



function max_estimated_value_lead(){
    
    $max_estimated_value = '';
     
        $sql = "SELECT MAX(estimated_value) as max_estimated_value FROM `tbl_lead` ORDER BY `id` DESC";
        $row = DB::select(DB::raw($sql));  
        $max_estimated_value    = $row[0];
   
    return $max_estimated_value;    
}


function max_estimated_value_offer(){
    
    $max_estimated_value = '';
     
        $sql = "SELECT MAX(total_order_cost_new) as max_estimated_value FROM `tbl_order`";
        $row = DB::select(DB::raw($sql));  
        $max_estimated_value    = $row[0];
   
    return $max_estimated_value;    
}


function enquiry_history($pcode){
   
$enquiry_history = '';
    
$sql = "SELECT 'Inquiry' as 'task_name', ID  AS 'task_id', Enq_Date AS 'task_date', 'Enq assigned' AS comment, 'crm/images/dashboard/das-icon/view_offer/inquiry.png' as 'task_icon'

FROM tbl_web_enq_edit 
where order_id='$pcode'

UNION ALL
SELECT 'Lead', id, time_lead_added, 'Lead Created ' , 'crm/images/dashboard/das-icon/view_offer/lead.png'
from tbl_lead l
INNER JOIN tbl_order o ON o.lead_id=l.id
where  o.orders_id='$pcode'


UNION ALL
SELECT 'Offer',  CONCAT(offercode,'-',orders_id), date_ordered,'Offer Created ', 'crm/images/dashboard/das-icon/view_offer/offer.png'
from tbl_order
where orders_id='$pcode'

 
UNION ALL
SELECT 'Delivery Order', O_Id, D_Order_Date, 'DO Created ' , 'crm/images/dashboard/das-icon/view_offer/po.png'
from tbl_delivery_order
where O_Id='$pcode'

UNION ALL
SELECT 'Proforma Invoice', O_Id, pi_generated_date, 'PI Generated', 'crm/images/dashboard/das-icon/view_offer/po.png'
from tbl_performa_invoice
where O_Id='$pcode'

UNION ALL
SELECT 'Invoice', invoice_id, invoice_generated_date, 'Invoice Generated', 'crm/images/dashboard/das-icon/view_offer/invoice.png'
from tbl_tax_invoice
where o_id='$pcode'

UNION ALL
SELECT 'tasks', evttxt, start_event, title,  evttxt
from events
where lead_type='$pcode' ";
        $row = DB::select(DB::raw($sql));  
        $enquiry_history    = $row;
   
    return $enquiry_history;    
}


function ApplicationHsnService($IdValue){

	if(is_numeric($IdValue)){

        $rowApplication = DB::table('tbl_application_service')->select('hsn_code')->where('application_service_id', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
		$tax_class_id	  = $rowApplication->hsn_code;
	}else{
		$tax_class_id 	= $IdValue;
	}
	return $tax_class_id;
}

function text_limiter($text,$lmt=''){
    return Str::limit($text, $lmt);
}

function fetchAlarm_config($valueType){

    $row_mail = DB::table('tbl_alarm_email_configuration')->select('admin_email','financial_year_start','alarm_1','alarm_1_additional_email','alarm_2','alarm_2_additional_email','alarm_3','alarm_3_additional_email','alarm_enq_not_assigned','enq_not_assigned_alarm_email','enq_not_assigned_alarm_email2','enq_not_assigned_alarm_email3','sales_head_email','inventory_report_email','inventory_report_email2','incoming_stock_report_email','incoming_stock_report_email2','outgoing_stock_report_email','outgoing_stock_report_email2')->where('deleteflag', '=', 'active')->first();     
    
    if($valueType == "admin"){
        return $row_mail->admin_email;
    }else if($valueType == "financial_year_start"){
        return $row_mail->financial_year_start;
    }else if($valueType == "alarm_1"){
        return $row_mail->alarm_1;
    }else if($valueType == "alarm_1_additional_email"){
        return $row_mail->alarm_1_additional_email;
    }else if($valueType == "alarm_2"){
        return $row_mail->alarm_2;
    }else if($valueType == "alarm_2_additional_email"){
        return $row_mail->alarm_2_additional_email;
    }else if($valueType == "alarm_3"){
        return $row_mail->alarm_3;
    }else if($valueType == "alarm_3_additional_email"){
        return $row_mail->alarm_3_additional_email;
    }else if($valueType == "alarm_enq_not_assigned"){
        return $row_mail->alarm_enq_not_assigned;
    }else if($valueType == "enq_not_assigned_alarm_email"){
        return $row_mail->enq_not_assigned_alarm_email;
    }else if($valueType == "enq_not_assigned_alarm_email2"){
        return $row_mail->enq_not_assigned_alarm_email2;
    }else if($valueType == "enq_not_assigned_alarm_email3"){
        return $row_mail->enq_not_assigned_alarm_email3;
    }else if($valueType == "sales_head_email"){
        return $row_mail->sales_head_email;
    }else if($valueType == "inventory_report_email"){
        return $row_mail->inventory_report_email;
    }else if($valueType == "inventory_report_email2"){
        return $row_mail->inventory_report_email2;
    }else if($valueType == "incoming_stock_report_email"){
        return $row_mail->incoming_stock_report_email;
    }else if($valueType == "incoming_stock_report_email2"){
        return $row_mail->incoming_stock_report_email2;
    }else if($valueType == "outgoing_stock_report_email"){
        return $row_mail->outgoing_stock_report_email;
    }else if($valueType == "outgoing_stock_report_email2"){
        return $row_mail->outgoing_stock_report_email2;
    }else{
        return "error";
    }       
}

function enq_source_abbrv($ref_source){
    if(!is_numeric($ref_source)){
        $row = DB::table('tbl_enq_source')->select('enq_source_abbrv')->where('enq_source_description', '=', $ref_source)->where('deleteflag', '=', 'active')->where('enq_source_status', '=', 'active')->first();
    }else{       
        $row = DB::table('tbl_enq_source')->select('enq_source_abbrv')->where('enq_source_id', '=', $ref_source)->where('deleteflag', '=', 'active')->where('enq_source_status', '=', 'active')->first();
    }
     
    if(isset($row->enq_source_abbrv)){
        $enq_source_abbrv	  = $row->enq_source_abbrv;
    }else{
        $enq_source_abbrv	  = "";
    }
    return $enq_source_abbrv;    
}

function application_name($ID){
   
    $application_name = '';
    if($ID > 0){
        $row = DB::table('tbl_application')->select('application_name')->where('application_id', '=', $ID)->where('deleteflag', '=', 'active')->where('application_status', '=', 'active')->first(); 
        $application_name = '';
        if(isset($row->application_name)){
            $application_name = $row->application_name;
        }
    }
    return $application_name;
}

/*function account_manager_name_full($ID){

    $name = '';
    if($ID > 0){
        $row = DB::table('tbl_admin')->select('name')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
        
       // $name = isset($row->admin_fname) ? $row->admin_fname : ''.' '.$row->admin_lname;
       $name = isset($row->name) ? $row->name : '';
    }
    return $name;
}
*/

function account_manager_name_full($ID){ 
    $row = DB::table('tbl_admin')->select('admin_fname','admin_lname')->where('id', '=', $ID)->first();  
    $name	  = isset($row->admin_fname) ? $row->admin_lname : '';
//    $name	  = $row->admin_fname.' '.$row->admin_lname;
    return ucfirst($name);
}

function get_offer_type_by_lead_id($leadid){

    $offer_type = '';
    if($leadid > 0){
        $row = DB::table('tbl_lead')->select('offer_type')->where('id', '=', $leadid)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();    
        $offer_type = isset($row->offer_type) ? $row->offer_type : '';
    }
    return $offer_type;
}

function lead_cust_segment_name($cust_segment_id){
   
    if(!is_numeric($cust_segment_id)){
        $row = DB::table('tbl_cust_segment')->select('cust_segment_name')->where('cust_segment_description', '=', $cust_segment_id)->where('deleteflag', '=', 'active')->where('cust_segment_status', '=', 'active')->first();        
    }else{
        $row = DB::table('tbl_cust_segment')->select('cust_segment_name')->where('cust_segment_id', '=', $cust_segment_id)->where('deleteflag', '=', 'active')->where('cust_segment_status', '=', 'active')->first(); 
    }
    $cust_segment_name	= isset($row->cust_segment_name) ? $row->cust_segment_name : '';
    return $cust_segment_name;
}

function StateName($STvalue){
    
    if(is_numeric($STvalue)){
        $row = DB::table('tbl_zones')->select('zone_name')->where('zone_id', '=', $STvalue)->where('deleteflag', '=', 'active')->first();         
        $state	  = isset($row->zone_name) ? $row->zone_name : '';
    }else{
        $state = $STvalue;
    }
    return ucfirst($state);
}

function CityName($STvalue){
    
    $city = '';
    if(is_numeric($STvalue)){
        $rowcity = DB::table('all_cities')->select('city_name')->where('city_id', '=', $STvalue)->where('deleteflag', '=', 'active')->first();  
        $city	  = isset($rowcity->city_name) ? $rowcity->city_name : '';
    }else{
        $city = $STvalue;
    }
    return ucfirst($city);
}

/*
function enqiry_details($ID){

    $details = '';
    if($ID > 0){
        $rowState = DB::table('all_cities')->select('enq_id','Cus_msg','Cus_Name','Cus_email','Cus_mob','city','state','acc_manager','country','ref_source','cust_segment','Enq_Date','hot_productnote','hot_productnoteother','product_category')->where('ID', '=', $ID)->where('deleteflag', '=', 'active')->first();    
        $details    = $rowState;
    }
    return $details;
}
*/

function enqiry_details($ID){
    
    $details = '';
    if($ID > 0){
        $sql = "select enq_id,Cus_msg,Cus_Name,Cus_email,Cus_mob,city,state,acc_manager,country,ref_source,cust_segment,Enq_Date,hot_productnote,hot_productnoteother,product_category from tbl_web_enq_edit where ID = '$ID' and deleteflag = 'active'";
        $row = DB::select(DB::raw($sql));  
        $details    = $row[0];
    }
    return $details;    
}

function generated_enqiry_details($ID){

    $details = '';
    if($ID > 0){
        $rowState = DB::table('tbl_web_enq_edit')->select('enq_id','Cus_msg','Cus_Name','Cus_email','Cus_mob','city','state','acc_manager','country','ref_source','cust_segment','Enq_Date','hot_productnote','hot_productnoteother','product_category')->where('ID', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
        $details	  = $rowState;
        return $details;
    }
    return $details;
}

function showSubApplicationCategories($cat_id, $dashes = '',$check_pid = ''){ 

	$dashes .= '&nbsp;&nbsp;&nbsp;&nbsp;&raquo;';     
    $row = DB::table('tbl_application')->select('application_id','application_name')
                ->where('parent_id1', '=', $cat_id)
                ->where('deleteflag', '=', 'active')
                ->orderby('application_name','asc')
                ->where('application_status', '=', 'active')->get(); 

	if(!empty($row)){
        foreach($row as $val){          
			if($val['application_id'] == $check_pid){
			    echo "<option value='$check_pid' selected='selected'>". $dashes . stripslashes($val['application_name']) . " </option>";
			}else{
			    echo "<option value='".$val['application_id']."'>".$dashes . stripslashes($val['application_name']) . "</option><br />";   
			}  
		}    
	}
}

function admin_name($ID){ 
    $row = DB::table('tbl_admin')->select('admin_fname','admin_lname')->where('id', '=', $ID)->first();  
//    $name	  = isset($row->admin_fname) ? $row->admin_lname : '';
    $name	  = $row->admin_fname.' '.$row->admin_lname;
    return ucfirst($name);
}

function date_format_india_with_time($date){
    return  $date_formate_with_time=date("d M, Y, h:i A", strtotime($date));
}

function get_lead_id_by_enquiry_edited_id($id){
   
    $row = DB::table('tbl_lead')->select('ID')->where('enq_id', '=', $id)->where('deleteflag', '=', 'active')->first(); 
    $ID	  = isset($row->ID) ? $row->ID : '';
    return $ID;
}


function get_lead_details($lead_id){
   
    $row = DB::table('tbl_lead')->select('id','lead_email', 'lead_phone',  'cust_segment', 'app_cat_id', 'lead_contact_state', 'lead_phone', 'lead_contact_country', 'ref_source', 'lead_contact_city','lead_contact_zip_code','salutation','lead_fname','lead_lname','lead_contact_address1','comp_name','enq_id')->where('id', '=', $lead_id)->where('deleteflag', '=', 'active')->first(); 
	
   // $ID	  = isset($row->ID) ? $row->ID : '';
    return $row;
}



function account_manager_email($ID){

    $row = DB::table('tbl_admin')->select('email')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first();     
    $admin_email = isset($row->email) ? $row->email : '';
    return $admin_email;
}

function admin_team($ID){
    
    $row = DB::table('tbl_admin')->select('admin_team')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $admin_team	 = isset($row->admin_team) ? $row->admin_team : '';	
    return $admin_team;
}

function admin_role_id($ID){

     $row = DB::table('tbl_admin')->select('admin_role_id')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $admin_role_id	 = isset($row->admin_role_id) ? $row->admin_role_id : '';	
    return $admin_role_id;
}



/*function admin_role_id($ID){

    $row = DB::table('tbl_admin')->select('admin_role_id')->where('admin_id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $admin_role_id	 = isset($row->role_id) ? $row->role_id : '';	
    return $admin_role_id;
}
*/
function admin_sub_team_lead_gg($ID){   

    $row = DB::table('tbl_team')->select('sub_team_lead')->where('team_id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $sub_team_lead	 = isset($row->sub_team_lead) ? $row->sub_team_lead : '';
    if($sub_team_lead=='' || $sub_team_lead=='0'){
        $sub_team_lead="0";
    }
    return $sub_team_lead;
}

function admin_sub_team_lead2_gg($ID){ 
    
    $row = DB::table('tbl_team')->select('sub_team_lead2')->where('team_id', '=', $ID)->where('deleteflag', '=', 'active')->first();
    $sub_team_lead = isset($row->sub_team_lead2) ? $row->sub_team_lead2 : '';	
    if($sub_team_lead=='' || $sub_team_lead=='0'){
        $sub_team_lead="0";
    }
    return $sub_team_lead;
}

function admin_team_lead($ID){   

    $row = DB::table('tbl_admin')->select('admin_team_lead')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first();
    $admin_team_lead	 = isset($row->admin_team_lead) ? $row->admin_team_lead : '';	
    if($admin_team_lead=='' || $admin_team_lead=='0'){
    $admin_team_lead="0";
    }
    return $admin_team_lead;
}

function get_alphanumeric_id_enq_order_id($id){  
    
    $row = DB::table('tbl_order')->select('orders_id','offercode')->where('orders_id', '=', $id)->where('deleteflag', '=', 'active')->first();
    $offercode	  = isset($row->offercode) ? $row->offercode : '';
    $orders_id = isset($row->orders_id) ? $row->orders_id : '';
    $opcode = $offercode.'-'.$orders_id;
    return $opcode;
}

function product_category_name($ID){

    $row = DB::table('tbl_application')->select('application_name')->where('application_id', '=', $ID)->where('deleteflag', '=', 'active')->first();
    $application_name	  = isset($row->application_name) ? $row->application_name : '';
    return $application_name;
}

function enq_source_name($ref_source){

    if(!is_numeric($ref_source)){
        $row = DB::table('tbl_enq_source')->select('enq_source_name')->where('enq_source_description', '=', $ref_source)->where('deleteflag', '=', 'active')->where('enq_source_status', '=', 'active')->first(); 
    }else{
        $row = DB::table('tbl_enq_source')->select('enq_source_name')->where('enq_source_id', '=', $ref_source)->where('deleteflag', '=', 'active')->where('enq_source_status', '=', 'active')->first(); 
    }
    $enq_source_name    =   isset($row->enq_source_name) ? $row->enq_source_name : '';
    return $enq_source_name;
}

function get_follow_up_date($id){ 

    $row = DB::table('tbl_order')->select('follow_up_date')->where('orders_id', '=', $id)->first();
    $follow_up_date	  = isset($row->follow_up_date) ? $row->follow_up_date : '';
    return $follow_up_date;
}

function enq_stage_name($ID){

    $row = DB::table('tbl_stage_master')->select('stage_name')->where('stage_id', '=', $ID)->where('deleteflag', '=', 'active')->first();
    $stage_name	  = isset($row->stage_name) ? $row->stage_name : '';
    return $stage_name;
}

function get_order_status($orderID){ 

    $orders_status = '';
    $row = DB::table('tbl_order')->select('orders_status')->where('orders_id', '=', $orderID)->where('deleteflag', '=', 'active')->first();
    $orders_status	  = isset($row->orders_status) ? $row->orders_status : '';
    return $orders_status;    
}

function get_delivery_order_date($id){

    $row = DB::table('tbl_delivery_order')->select('D_Order_Date')->where('O_Id', '=', $id)->first();
    $D_Order_Date	  = isset($row->D_Order_Date) ? $row->D_Order_Date : '';
    return $D_Order_Date;
}

function product_name_generated_for_excel($ID){   

    $pro_name1 = '';
    $row = DB::table('tbl_order_product')->select('pro_name')->where('order_id', '=', $ID)->get();   
    foreach($row as $val){
        if($val->pro_name!='' && $val->pro_name!='0' ){
            $pro_name[] = $val->pro_name;
        }
    }    
    if(!empty($pro_name)) {
        $pro_name1 = @implode("\n",$pro_name);
    }    
    return $pro_name1;
}

function currencySymbol($where){

    $row = DB::table('tbl_currencies')->select('currency_symbol','currency_value')->where('currency_id', '=', $where)->where('deleteflag', '=', 'active')->first();
    if(!empty($row)){
        $symbol[0] = $row->currency_symbol;
        $symbol[1] = $row->currency_value;
        return $symbol;
    }
}

function currencySymbolDefault($where){

	if($where == 0){
        $currency_row = DB::table('tbl_currencies')->select('currency_symbol','currency_value')->where('currency_super_default', '=', 'yes')->where('deleteflag', '=', 'active')->first();		
		$symbol =  $currency_row->currency_symbol;
		return $symbol;
		
	}else if($where == 1){
        $currency_row = DB::table('tbl_currencies')->select('currency_symbol','currency_value')->where('currency_status', '=', 'yes')->where('deleteflag', '=', 'active')->first();	        
        $symbol = $currency_row->currency_symbol;
        return $symbol;
		
	}
}

function offer_total_new($pcode){

    $symbol						= currencySymbol(1);
    $currency1 					= $symbol[0];
    $curValue 					= $symbol[1];

    $row 						= DB::table('tbl_order')->select('total_order_cost_new')->where('orders_id', '=', $pcode)->first();    
    $total_order_cost_new	 	= $row->total_order_cost_new;    
    return $total_order_cost_new;
}


function sales_cycle_offer_total($qtr_start_date_show,$qtr_end_date_show,$offer_probability,$hot_offer,$acc_manager,$offer_type,$product_category,$cust_segment_search,$datevalid_to,$datevalid_from,$follow_up_datevalid_to)
{ 

if($hot_offer!='')
{
	$hot_offer_search= " and o.hot_offer='$hot_offer' ";
}
else
{
	$hot_offer_search= " ";	
}

if($acc_manager!='')
{
	$acc_manager_search= " and o.order_by='$acc_manager' ";
}
else
{
	$acc_manager_search= " ";	
}


if($offer_type!='')
{
	$offer_type_search= " and o.offer_type='$offer_type' ";
}
else
{
	$offer_type_search= " ";	
}


if($product_category!='' && $product_category!='0')
	{
	//$orders_status='Pending';
	$product_category_search=" and t3.app_cat_id='$product_category'";
	}
	else
	{
		$product_category_search=" ";
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


/*if($month_search!='' && $month_search!='0')
	{
	//$orders_status='Pending';
	$month_search_search="and o.ensure_sale_month='$month_search'";
	}
	else
	{
	$month_search_search=" ";
	}*/


if($datevalid_from!='' && $datevalid_to!='')
	{
		
$date_range_search=" AND (date( o.date_ordered ) BETWEEN '$datevalid_from' AND '$datevalid_to')";
	}

else
{
	$date_range_search ="";
}


$sql = "SELECT o.orders_id, 
o.offer_probability, 
o.date_ordered, 
top.order_pros_id, 
SUM(DISTINCT((top.pro_price * top.pro_quantity))) as total_pro_sum  
FROM tbl_order o  
INNER JOIN tbl_order_product top ON top.order_id = o.orders_id
INNER JOIN 
   tbl_lead AS t3 ON o.lead_id = t3.id

WHERE o.date_ordered BETWEEN '$qtr_start_date_show' and '$qtr_end_date_show'
$hot_offer_search
$acc_manager_search
$date_range_search
$cust_segment_search_search
$product_category_search
$offer_type_search
and o.offer_probability = '$offer_probability'

ORDER BY o.offer_probability ASC;";   

$row 			  = DB::select(DB::raw($sql));
   
$total_pro_sum	  = isset($row[0]->total_pro_sum) ? $row[0]->total_pro_sum : '0';   
return $total_pro_sum;
}

function get_customers_id_by_order_id($ID){

    $row = DB::table('tbl_order')->select('customers_id')->where('orders_id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $customers_id	  = isset($row->customers_id) ? $row->customers_id : '';
    return $customers_id;
}

function company_names($ID){

    $row = DB::table('tbl_comp')->select('comp_name')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $name	  = isset($row->comp_name) ? $row->comp_name : '';
    return ucwords($name);
}

function Get_POTOTALVALUE($ID) {
    
    $row = DB::table('tbl_do_products')->select('Price','Quantity')->where('OID', '=', $ID)->get(); 
    $totalAmt = 0;
    foreach($row as $val){   
        $totalAmt	  += (@$val->Quantity * $val->Price);	
    }
    return $totalAmt;
}

function sub_co_count($ID){ 

    $sub_co_count = DB::table('tbl_comp')->select('id')->where('parent_id', '=', $ID)->where('deleteflag', '=', 'active')->count(); 
    return $sub_co_count;
}

function company_extn_name($ID){

    $row = DB::table('tbl_company_extn')->select('company_extn_name')->where('company_extn_id', '=', $ID)->where('deleteflag', '=', 'active')->where('company_extn_status', '=', 'active')->first(); 
    $company_extn_name	  = isset($row->company_extn_name) ? $row->company_extn_name : '';
    return $company_extn_name;
}


function company_name_return($IdValue){

	$comp_name = '';
    if(is_numeric($IdValue)){
		//$sqlCountry = "select comp_name,co_extn_id, co_extn, office_type,co_division,co_city from tbl_comp where id = '$IdValue' and deleteflag = 'active'";
        $rowCountry = DB::table('tbl_comp')->select('comp_name','co_extn_id', 'co_extn', 'office_type','co_division','co_city')->where('id', '=', $IdValue)->where('deleteflag', '=', 'active')->first(); 
        if(!empty($rowCountry)){
		$co_extn_id		= isset($rowCountry->co_extn_id) ? $rowCountry->co_extn_id : '';
		$office_type	= isset($rowCountry->office_type) ? $rowCountry->office_type : '';
		$co_division	= isset($rowCountry->co_division) ? $rowCountry->co_division : '';
		$co_city		= isset($rowCountry->co_city) ? $rowCountry->co_city : '';
        if($co_extn_id!='0' && $co_extn_id!='6' && $co_extn_id!='')		
        {
            $co_extn_id_name=company_extn_name($co_extn_id);
        }
        /*if($co_division!='0' && $co_division!='')		
        {
            $co_division_name=" / ".$rowCountry->co_division;
        }
        else
        {
            $co_division_name="";
        }
        if($co_city!='0' && $co_city!='')		
        {
            $co_city_name=" / ".$rowCountry->co_city;
        }
        else
        {
            $co_city_name="";
        }*/
        $co_division_name="";
        $co_city_name="";
        $comp_name1=str_replace('Pvt Ltd', "", $rowCountry->comp_name);
        $comp_name2=str_replace('PVT. LTD.', "", $comp_name1);
        $comp_name3=str_replace('Limited', "", $comp_name2);
        $comp_name4=str_replace('Ltd', "", $comp_name3);
        $comp_name5=str_replace('Pvt ltd', "", $comp_name4);
        $comp_name6=str_replace('LLP', "", $comp_name5);
        $comp_name7=str_replace('Pvt. .', "", $comp_name6);
        $comp_name8=str_replace('Pvt.', "", $comp_name7);		
        $comp_name9=str_replace('PVT LIMITED', "", $comp_name8);		
        $comp_name10=str_replace('pvt ltd', "", $comp_name9);		
        $comp_name11=str_replace('LTD', "", $comp_name10);		
        $comp_name12=str_replace('PVT.', "", $comp_name11);		
        $comp_name13=str_replace('limited', "", $comp_name12);		
        $comp_name14=str_replace('Private', "", $comp_name13);		
        $comp_name15=str_replace('PVT ', "", $comp_name14);		
        $comp_name16=str_replace('(P)', "", $comp_name15);		
        $comp_name17=str_replace('PRIVATE LIMITED', "", $comp_name16);
        $comp_name18=str_replace('LIMITED...', "", $comp_name17);		
        $comp_name19=str_replace('LIMITED', "", $comp_name18);	
        
        $co_extn_id_name = isset($co_extn_id_name) ? $co_extn_id_name : '';
        $co_division_name = isset($co_division_name) ? $co_division_name : '';
        $co_city_name = isset($co_city_name) ? $co_city_name : '';

        $comp_name	= $comp_name19." ".$co_extn_id_name."".$co_division_name."".$co_city_name;
    }
	}
   
	return ucfirst($comp_name);
}

function company_tree_latest2($limit=1000){   

    $row = DB::table('tbl_comp')
    ->select('tbl_comp.id','tbl_comp.comp_name','tbl_comp.co_extn_id','tbl_comp.co_extn','tbl_comp.co_division','tbl_comp.co_city','tbl_comp.office_type',  'tbl_company_extn.company_extn_name')
    ->join('tbl_company_extn','tbl_comp.co_extn_id','=','tbl_company_extn.company_extn_id')
    ->get();

    echo "<pre>";
    print_r($row);
    exit;
    
   // $row = DB::table('tbl_comp')->select('id','comp_name','co_extn_id','co_extn','co_division','co_city','office_type')->where('india_mart_co', '=', 'no')->where('deleteflag', '=', 'active')->orderBy('id', 'desc')->take($limit)->get(); 

    //$row = DB::table('tbl_company_extn')->select('company_extn_name')->where('company_extn_id', '=', $ID)->where('deleteflag', '=', 'active')->where('company_extn_status', '=', 'active')->first();

        //$co_extn_id_name=company_extn_name($co_extn_id);

    foreach($row as $row_cat){
        $categories[]=$row_cat;
    }

    echo "<pre>";
    print_r($categories);
    exit;
    //$rowCountry = DB::table('tbl_comp')->select('comp_name','co_extn_id', 'co_extn', 'office_type','co_division','co_city')->where('id', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
    
    /*foreach($row as $row_cat){
    $categories[]=$row_cat;
    }
    */
    $items= $categories;
    return $items;
}


function showSubCompany($cat_id, $dashes, $edit_perm, $del_perm){   
   
    $AdminLoginID_SET = Auth::user()->id;
    $row = DB::table('tbl_comp')->select('id','parent_id','fname','comp_name','lname','mobile_no','create_date','status','city','acc_manager','co_extn_id','co_division','co_city','office_type','key_customer')->where('parent_id', '=', $cat_id)->where('deleteflag', '=', 'active')->get(); 
    
    
    if(!empty($row)){
       foreach($row as $rowSub){
?>
<tr  class="text"  style="background-color:#fff">
<td class="pad" valign="middle" width="5%"><input type="checkbox" name="comp_id[]" id="comp_name" value="<?php echo $rowSub->id;?>" />
<?php if($rowSub->key_customer=='1'){?>
                <img src="/assets/images/retail_cust.png" title="Retail" border="0"  />
                <?php }?>
<?php if($rowSub->key_customer=='2'){?>
                <img src="/assets/images/sub_key.png" title="Sub Key Customer" border="0"  />
                <?php }?>       
<?php if($rowSub->key_customer=='3'){?>
                <img src="/assets/images/key_customer.png" title="Key Customer" border="0"  />
                <?php }?> </td>
<td class="pad" width="20%"><?php echo $dashes."&raquo; ". stripslashes(company_name_return($rowSub->id)); ?>
<?php //echo $this->company_extn_name($rowSub['co_extn_id']); ?>
<?php if($rowSub->co_division!='0' && $rowSub->co_division!=''){echo "/".$rowSub->co_division;}?>
<?php if($rowSub->co_city!='0' && $rowSub->co_city!='') {echo "/".$rowSub->co_city;}?>
<?php //echo "Count:-".lead_count_comp($rowSub['id']);?></td>
<td  class="pad" align="left"><?php echo CityName($rowSub->city);?></td>
<td align="left" valign="middle"><?php echo ucfirst($rowSub->fname) ; ?> <?php echo ucfirst($rowSub->lname) ; ?></td>
<td  class="pad" align="left">

<?php if($rowSub->acc_manager!=''){ echo account_manager_name_full($rowSub->acc_manager);} else { echo "N/A";}?></td>
<td class="pad" valign="middle" ><?php echo ucfirst(stripslashes($rowSub->mobile_no)) ; ?></td>
<td align="left" valign="middle"><?php echo date("d/M/Y",strtotime($rowSub->create_date)) ; ?></td>
<?php if($AdminLoginID_SET==4) { ?>
<td align="center" valign="middle"><?php 	if($rowSub->status == "active")	{?>
<img src="/assets/images/green.gif" title="Active" border="0"  /> &nbsp; &nbsp; <a href="/admin/company_manager/?action=InactiveStatus&id=<?php echo $rowSub->id; ?>"><img src="/assets/images/red_light.gif" title="Inactive" border="0"  /></a>
<?php
}
// if($rowSub['status'] == "inactive")
else{
?>
<a href="/admin/company_manager/?action=ActiveStatus&id=<?php echo $rowSub->id; ?>"><img src="/assets/images/green_light.gif" title="Active" border="0"  /></a> &nbsp; &nbsp; <img src="/assets/images/red.gif" title="Inactive" border="0"  />
<?php		

}?></td>
<?php } ?>
<td align="center" valign="middle"><?php if(isset($edit_perm)){?>
<a href="/admin/company_manager/edit/<?php echo $rowSub->id; ?>"> <img src="/assets/images/e.gif" border="0"  alt="Edit"/></a>
<?php } else {?>
<a href="#;"> <img src="/assets/images/protect.png" border="0"  alt="Edit"/></a>
<?php }

if(isset($del_perm))

{

?>
&nbsp; &nbsp; <a href="/admin/company_manager/delete/<?php echo $rowSub->id; ?>" onclick='return del();'> <img src="/assets/images/x.gif" border="0" alt="Delete" onclick="return confirm('Are You Sure to Delete this Company?')" /></a>
<?php }

else

    {

        ?>
<?php /*?>   &nbsp; &nbsp; <a href="#;" onclick='return del();'> <img src="images/protect.png" border="0" alt="Delete" onclick="return confirm('Are You Sure to Delete this Company?')" /></a><?php */?>
<?php //}
} ?></td>
<td align="center" valign="middle"><a href="/admin/company_contact_person?pcode=<?php echo $rowSub->id; ?>" title="View More Company Persons Name" target="_blank"><img src="/assets/images/new-user2.png" border="0"  alt="View Company Persons Name" width="45" align="middle"/></a></td>
<td align="center" valign="middle">&nbsp; <a href="/admin/lead_history?pcode=<?php echo $rowSub->id; ?>" title="View Company Leads/Offers/Orders History" target="_blank"> <img src="/assets/images/history.svg" border="0"  alt="View Company Lead History" width="30" align="middle" /></a></td>
<td align="center" valign="middle">&nbsp;</td>
</tr>
<?php 

        }

     }

}

function company_tree(){  

    $row = DB::table('tbl_comp')->select('id','parent_id','comp_name','co_extn_id','co_division','co_city','office_type', 'address', 'create_date')->where('india_mart_co', '=', 'no')->where('deleteflag', '=', 'active')->orderBy('id', 'desc')->take(1000)->get(); 
    foreach($row as $row_cat){
        $categories[]=$row_cat;
    }
    $items= $categories;
    $childs = array();
    foreach($items as $item)
        $childs[$item->parent_id][] = $item;
    foreach($items as $item) if (isset($childs[$item->id]))
        $item->childs = $childs[$item->id];
        $tree = $childs[0];
    return $tree;
}

function company_tree_latest($limit=1000){   

    $row = DB::table('tbl_comp')->select('id','comp_name','co_extn_id','co_division','co_city','office_type')->where('india_mart_co', '=', 'no')->where('deleteflag', '=', 'active')->orderBy('id', 'desc')->take($limit)->take(1000)->get(); 
    foreach($row as $row_cat){
    $categories[]=$row_cat;
    }
    $items= $categories;
    return $items;
}

function addmin_role($ID){ 

    $roleRes = DB::table('tbl_admin_role_type')->select('admin_role_name')->where('admin_role_id', '=', $ID)->first();     
    return isset($roleRes->admin_role_name) ? $roleRes->admin_role_name : '';
}

function team_name($ID){ 

    $rows = DB::table('tbl_team')->select('team_name')->where('team_id', '=', $ID)->first();     
    return isset($rows->team_name) ? $rows->team_name : '';
}

function account_manager_name($ID){

    $row = DB::table('tbl_admin')->select('admin_fname','admin_lname')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first();     
    $admin_fname	  = isset($row->admin_fname) ? $row->admin_fname : '';
    $admin_lname	  = isset($row->admin_lname) ? $row->admin_lname : '';
    $full_name = $admin_fname. ' '. $admin_lname;
    return $full_name;
}

function designation_name($ID){
	if(is_numeric($ID)){
        $row = DB::table('tbl_designation')->select('designation_name')->where('designation_id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
		$designation_name	 = isset($row->designation_name) ? $row->designation_name : '';	
	}else{
		$designation_name 	= $ID;
	}
	return ucfirst($designation_name);
}

function emp_contact_number($ID){

    $admin_telephone = '';
	if(is_numeric($ID)){
        $row = DB::table('tbl_admin')->select('admin_telephone')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first();
		$admin_telephone	 = isset($row->admin_telephone) ? $row->admin_telephone : '';	
	}
	return $admin_telephone;
}

function emp_email($ID){

    $admin_email = '';
	if(is_numeric($ID)){
        $row = DB::table('tbl_admin')->select('email')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first();
		$admin_email	 = isset($row->email) ? $row->email : '';	
	}
	return $admin_email;
}

// This function use to change the date formate
function getDateformate($date,$informate='dmy',$formate='ymd',$spliter='-',$symbolChange='-'){  
 
$dateArray  = explode($spliter,$date);
if(sizeof($dateArray)==1) {
    $dateArray  = explode("/",$date);
    $changeDate = $dateArray[0].$symbolChange.$dateArray[1].$symbolChange.$dateArray[2];
    return $changeDate;
}
if($informate == 'dmy'){
    if($formate == 'ymd'){
        $changeDate = $dateArray[2].$symbolChange.$dateArray[1].$symbolChange.$dateArray[0];
        return $changeDate;
    }
    if($formate == 'mdy'){
        $changeDate = $dateArray[1].$symbolChange.$dateArray[0].$symbolChange.$dateArray[2];
        return $changeDate;
    }
    if($formate == 'dmy'){
        $changeDate = $dateArray[0].$symbolChange.$dateArray[1].$symbolChange.$dateArray[2];
        return $changeDate;
    }
    if($formate == 'ydm'){
        $changeDate = $dateArray[2].$symbolChange.$dateArray[0].$symbolChange.$dateArray[1];
        return $changeDate;
    }
}

if($informate == 'mdy'){
    if($formate == 'ymd'){
        $changeDate = $dateArray[2].$symbolChange.$dateArray[0].$symbolChange.$dateArray[1];
        return $changeDate;
    }
    if($formate == 'mdy'){
        $changeDate = $dateArray[0].$symbolChange.$dateArray[1].$symbolChange.$dateArray[2];
        return $changeDate;
    }
    if($formate == 'dmy'){
        $changeDate = $dateArray[1].$symbolChange.$dateArray[2].$symbolChange.$dateArray[0];
        return $changeDate;
    }
    if($formate == 'mdy'){
        $changeDate = $dateArray[0].$symbolChange.$dateArray[2].$symbolChange.$dateArray[1];
        return $changeDate;
    }
}

if($informate == 'ymd'){
    if($formate == 'ymd'){
        $changeDate = $dateArray[0].$symbolChange.$dateArray[1].$symbolChange.$dateArray[2];
        return $changeDate;
    }
    if($formate == 'mdy'){
        $changeDate = $dateArray[1].$symbolChange.$dateArray[2].$symbolChange.$dateArray[0];
        return $changeDate;
    }
    if($formate == 'dmy'){
        $changeDate = $dateArray[2].$symbolChange.$dateArray[1].$symbolChange.$dateArray[0];
        return $changeDate;
    }
    if($formate == 'ydm'){
        $changeDate = $dateArray[2].$symbolChange.$dateArray[0].$symbolChange.$dateArray[1];
        return $changeDate;
    }
}
}

function dateSub2($days){
    $year  = date('Y');  
    $month = date('m');
    $date  = date('d');
    $time = date('Y-m-d', mktime(0, 0, 0, $month, $date-$days, $year));
    return $time;
}

function page_permission_sel($page_id,$admin_role,$pcode){
	

	
    $sql= "SELECT count(access_id) as aggegrate from tbl_admin_access where page_id = '$page_id'";// exit;
//    $row = DB::table('tbl_website_page')->select('page_id')->where('page_name', 'like', '$page_name%')->where('deleteflag', '=', 'active')->first(); 
    $row = DB::select(DB::raw($sql));
    $row = isset($row[0]) ? $row[0] : $row;
	$rs_num	 = isset($row->aggegrate) ? $row->aggegrate : '';	//exit;	  
	  
    //$rs_num = DB::table('tbl_admin_access')->select('access_id')
      //      ->where('page_id', '=', $page_id)->count();
//            ->where('role_id', '=', $admin_role)->count();
 //           ->where('admin_id', '=', $pcode)
//            ->where('deleteflag', '=', 'active')->count(); 

//echo $rs_num; exit;
    return $rs_num;
}

function indiv_permission_selg($page_id,$admin_role,$pcode,$perm_name){

/*	echo "<br>".$page_id;
	echo "<br>".$admin_role;
	echo "<br>".$pcode;
	echo "<br>".$perm_name;*/
      
    $rs_num = DB::table('tbl_admin_access_in_module')->select('page_id')
            ->where('page_id', '=', $page_id)
            ->where('admin_role_id', '=', $admin_role)
            ->where('admin_id', '=', $pcode)
            ->where('assign_perm', '=', $perm_name)->count(); 
    return $rs_num;
}


function indiv_permission_sel($page_id,$admin_role,$pcode,$perm_name)
{
$sql_page_check_individual	= "select count(page_id) as ctr from tbl_admin_access_in_module where page_id='".$page_id."' and admin_role_id='".$admin_role."' and admin_id='".$pcode."' and assign_perm='".$perm_name."'";
//$rs_page_check_individual=mysqli_query($GLOBALS["___mysqli_ston"],  $sql_page_check_individual);	
//$rs_num=mysqli_num_rows($rs_page_check_individual);	
$row 						= DB::select(DB::raw($sql_page_check_individual));
$row						= $row[0];
$rs_num	 					= isset($row->ctr) ? $row->ctr : '';	//exit;
return $rs_num;
}



function website_page_id($page_name){  
     $sql= "SELECT page_id from tbl_website_page where page_name = '$page_name' and deleteflag='active'"; //exit;
//    $row = DB::table('tbl_website_page')->select('page_id')->where('page_name', 'like', '$page_name%')->where('deleteflag', '=', 'active')->first(); 
    
    $row = DB::select(DB::raw($sql));
	$row=$row[0];
//	print_r($row); exit;
	"pg_id:=".$page_id	 = isset($row->page_id) ? $row->page_id : '';	//exit;
//	dd($page_id);exit;
    return $page_id;
}

function get_tes_id($acc_manager,$financial_year){

/*echo $sql="SELECT * from tbl_tes_manager where account_manager= '".$acc_manager."' and  financial_year= '".$financial_year."'";
exit;*/
    $row = DB::table('tbl_tes_manager')->select('ID')->where('account_manager', '=', $acc_manager)->where('financial_year', '=', $financial_year)->where('deleteflag', '=', 'active')->where('status', '=', 'approved')->first(); 
	

/*	
	dd($row);
	die();
	exit; */
    $tes_id	 = isset($row->ID) ? $row->ID : '';
    return $tes_id;
}

function get_all_tes_id_current_year($financial_year){

    //$sql = "SELECT GROUP_CONCAT(ID) as TES_IDS FROM `tbl_tes_manager` where status='approved' and deleteflag='active' and financial_year='$financial_year' "; 
$sql = "SELECT GROUP_CONCAT(ID) as TES_IDS FROM `tbl_tes_manager` where status='approved' and deleteflag='active' and financial_year='$financial_year'"; //exit;
    $row = DB::select(DB::raw($sql));
	$row=$row[0];
//	print_r($row);
	
    $TES_IDS = isset($row->TES_IDS) ? $row->TES_IDS : ''; //exit;
   
    return $TES_IDS;
}

function enqiry_lead_source_edited($ID){

    $row = DB::table('tbl_web_enq_edit')->select('ref_source')->where('ID', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $enq_ref_source	 = isset($row->ref_source) ? $row->ref_source : '';
    return $enq_ref_source;
}

function tes_selected_comp_by_acc_manager($tes_id,$comp_id){
   
    if(isset($comp_id)){   
        $row = DB::table('tbl_tes')->select('comp_id')->where('tes_id', '=', $tes_id)->where('comp_id', '=', $comp_id)->first(); 
        $tes_selected_comp_id_by_acc_manager = isset($row->comp_id) ? $row->comp_id : '';
        return $tes_selected_comp_id_by_acc_manager;
    }else{
        return '';
    }
}

function latest_po($cust_segment=0,$app_cat_id=0,$qtr_start_date_show='2019-04-01',$qtr_end_date_show=''){

/*if($acc_manager!='' && $acc_manager!='0'){	
	$acc_manager_search=" and o.order_by='$acc_manager'";
}
*/

if($cust_segment!='' && $cust_segment!='0'){
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
}
if($app_cat_id!='' && $app_cat_id!='0'){
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
}

$sql = "SELECT
o.orders_id, 
o.order_by, 
top.pro_id,
tble_invoice.I_date,  
tbl_delivery_challan.po_date,
tbl_delivery_challan.u_invoice_no,
tbl_delivery_challan.PO_No,
l.cust_segment, 
o.total_order_cost_new, 
o.customers_id
from tbl_order o ,
tbl_do_products tdp ,
tbl_admin a,
tbl_lead l,
tbl_order_product top, 
tbl_delivery_challan,
tble_invoice
where 
o.orders_id=tdp.OID and 
o.orders_id=top.order_id and 
o.order_by=a.id 
and l.id=o.lead_id  
and o.orders_id = tbl_delivery_challan.O_Id   
AND tble_invoice.o_id=o.orders_id 
and o.orders_status IN('Confirmed','Order Closed')
$cust_segment_search
$app_cat_id_search
AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
group by l.cust_segment 
  
order by tbl_delivery_challan.invoice_gen_date desc limit 0,1";
$row = DB::select(DB::raw($sql));
return $row;
}

function highest_value_po($cust_segment=0,$app_cat_id=0,$qtr_start_date_show='2019-04-01',$qtr_end_date_show=''){

/*
if($acc_manager!='' && $acc_manager!='0'){	
	$acc_manager_search=" and o.order_by='$acc_manager'";
}
*/
if($cust_segment!='' && $cust_segment!='0'){
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
}
if($app_cat_id!='' && $app_cat_id!='0'){
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
}

$sql = "SELECT
o.orders_id, 
o.order_by, 
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
SUM(tdp.Quantity * tdp.Price) as total_price
from tbl_order o ,
tbl_delivery_order tdo ,
tbl_do_products tdp ,
tbl_admin a,
tbl_lead l,
tbl_order_product top, 
tbl_delivery_challan,
tble_invoice

where 
o.orders_id=tdp.OID and 
o.orders_id=top.order_id and 
o.order_by=a.id 
and l.id=o.lead_id  
and  tdo.O_Id = tdp.OID
and tdo.O_Id = tbl_delivery_challan.O_Id   
AND tble_invoice.o_id=tdo.O_Id 
and o.orders_status IN('Confirmed','Order Closed')

$cust_segment_search
$app_cat_id_search

AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
GROUP by o.orders_id  
order by total_price desc ";
$row = DB::select(DB::raw($sql));
return $row;
}

function highest_value_po_by_item($cust_segment=0,$app_cat_id=0,$qtr_start_date_show='2019-04-01',$qtr_end_date_show=''){
	
if($cust_segment!='' && $cust_segment!='0'){
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
}
if($app_cat_id!='' && $app_cat_id!='0'){
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
}

$sql = "SELECT
o.orders_id, 
o.order_by, 
top.pro_id,
tdp.Description, 
tdp.Price,
tdp.Quantity,
tble_invoice.I_date,  
tbl_delivery_challan.po_date,
tbl_delivery_challan.PO_No,
l.cust_segment, 
o.customers_id
from tbl_order o ,
tbl_delivery_order tdo ,
tbl_do_products tdp ,
tbl_lead l,
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
and o.orders_status IN('Confirmed','Order Closed')
$cust_segment_search
$app_cat_id_search
AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
GROUP by o.orders_id  
order by  `tble_invoice`.`I_date`,tdp.Price desc ";	
$row = DB::select(DB::raw($sql));
return $row;
}

function customer_po($customers_id=0,$app_cat_id=0,$qtr_start_date_show='2019-04-01',$qtr_end_date_show=''){

if($customers_id!='' && $customers_id!='0'){
	$customers_id_search=" and o.customers_id='$customers_id'";
}
if($app_cat_id!='' && $app_cat_id!='0'){
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
}

$sql = "SELECT
o.orders_id, 
o.order_by,
top.pro_id,
l.cust_segment, 
tdp.Price,
tdp.Quantity,
tble_invoice.I_date,  
tbl_delivery_challan.po_date,
tbl_delivery_challan.u_invoice_no,
o.customers_id
from tbl_order o ,
tbl_delivery_order tdo ,
tbl_do_products tdp ,
tbl_lead l,
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

and o.orders_status IN('Confirmed','Order Closed')

$customers_id_search
$app_cat_id_search

group by l.cust_segment 
limit 0,1";	

$row = DB::select(DB::raw($sql));
return $row;
}

function po_by_customer_segment($cust_segment=0,$app_cat_id=0,$qtr_start_date_show='2019-04-01',$qtr_end_date_show=''){

if($cust_segment!='' && $cust_segment!='0'){
	$cust_segment_search=" and l.cust_segment='$cust_segment'";
}
if($app_cat_id!='' && $app_cat_id!='0'){
	$app_cat_id_search=" and top.pro_id='$app_cat_id'";
}

$sql = "SELECT
o.orders_id, 
o.order_by, 
top.pro_id,
tble_invoice.I_date,  
tbl_delivery_challan.po_date,
l.cust_segment, 
o.customers_id,
tdo.D_Order_Date
from tbl_order o ,
tbl_delivery_order tdo ,
tbl_do_products tdp ,
tbl_lead l,
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
and o.orders_status IN('Confirmed','Order Closed')

$cust_segment_search
$app_cat_id_search
AND ( tbl_delivery_challan.invoice_gen_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 
group by l.cust_segment 

order by tbl_delivery_challan.invoice_gen_date desc";	
$row = DB::select(DB::raw($sql));
return $row;
}

function moneyFormatIndia($num){

    $explrestunits = "" ;
    $num = preg_replace('/,+/', '', $num);
    $words = explode(".", $num);
    $des = "00";
    if(count($words)<=2){
        $num=$words[0];
        if(count($words)>=2){$des=$words[1];}
        if(strlen($des)<2){$des="$des";}else{$des=substr($des,0,2);}
    }
    if(strlen($num)>3){
        $lastthree = substr($num, strlen($num)-3, strlen($num));
        $restunits = substr($num, 0, strlen($num)-3); 
        $restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; 
        $expunit = str_split($restunits, 2);
        for($i=0; $i<sizeof($expunit); $i++){            
            if($i==0){
                $explrestunits .= (int)$expunit[$i].","; 
            }else{
                $explrestunits .= $expunit[$i].",";
            }
        }
        $thecash = $explrestunits.$lastthree;
    } else {
        $thecash = $num;
    }
    return "$thecash.$des";     
}

function product_name_generated_with_quantity($ID){ 

    $sql = "select pro_name,pro_quantity from tbl_order_product where order_id = $ID";    
    $rs = DB::select(DB::raw($sql));
    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";
}


function product_name_generated_with_quantity_json_tbl_order_product($ID){ 

    $sql_dis = "select 
	tbl_order_product.pro_discount_amount as discount
	from tbl_order_product
	where tbl_order_product.order_id= '$ID'";   
    $rs_dis = DB::select(DB::raw($sql_dis));


    $sql_dis_prowise = "select 
	discount_amount
	from prowise_discount
	where orderid= '$ID'";   
		
    $rs_dis_prowise = DB::select(DB::raw($sql_dis_prowise));

  "dis:".	$discount= isset($rs_dis[0]->discount) ? $rs_dis[0]->discount : '0';
  "dis_prowise:".$prowise_discount_amount= isset($rs_dis_prowise[0]->discount_amount) ? $rs_dis_prowise[0]->discount_amount : '0'; //exit;

if($prowise_discount_amount!=0 && $prowise_discount_amount!='')
{
    $sql = "select 
	tbl_order_product.order_pros_id,
	tbl_order_product.pro_id,
	tbl_order_product.pro_model as part_no,
	tbl_order_product.proidentry,
	tbl_order_product.hsn_code,
	tbl_order_product.pro_name as product_name,	
	tbl_order_product.pro_price as unit_price,	
	tbl_order_product.pro_quantity as qty,
	tbl_order_product.GST_percentage,	
	tbl_order_product.freight_amount,	
	tbl_order_product.Pro_tax as add_igst_value,	
	tbl_order_product.pro_discount_amount as discount,
	tp.pro_max_discount,
	tp.upc_code,
	tpe.pro_desc_entry as product_description,
    prowise_discount.discount_percent,
  //  prowise_discount.discount_amount,
(tbl_order_product.pro_price * (prowise_discount.discount_percent / 100)) as discount_amount,
    prowise_discount.orderid,
	( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount) as product_discounted_price,
	(( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount)) * (tbl_order_product.GST_percentage/100) as pro_tax_gst,

	(( tbl_order_product.pro_price - prowise_discount.discount_amount) *  tbl_order_product.pro_quantity )  as sub_total_without_gst_edit,			
	
	((tbl_order_product.pro_price - prowise_discount.discount_amount) * tbl_order_product.pro_quantity) * (tbl_order_product.GST_percentage /100) as pro_tax_gst_edit,
(( tbl_order_product.pro_price -prowise_discount.discount_amount  ) * (tbl_order_product.pro_quantity )) +  ((( tbl_order_product.pro_price -  prowise_discount.discount_amount) * (tbl_order_product.pro_quantity )) * (tbl_order_product.GST_percentage /100))  as sub_total_with_gst_edit,	
			
	((tbl_order_product.pro_price - prowise_discount.discount_amount) * tbl_order_product.pro_quantity) * (tbl_order_product.GST_percentage /100) as Pro_tax_edit,
( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount) +  ((( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount)) * (tbl_order_product.GST_percentage/100))  as sub_total	
	from tbl_order_product
	LEFT JOIN tbl_products tp on tp.pro_id=tbl_order_product.pro_id
	LEFT JOIN tbl_products_entry tpe on tpe.pro_id_entry=tbl_order_product.proidentry
	LEFT JOIN prowise_discount on prowise_discount.proid = tbl_order_product.pro_id
	where tbl_order_product.order_id= '$ID'
	and prowise_discount.orderid='$ID'";   
}
else
{
    $sql = "select 
	tbl_order_product.order_pros_id,
	tbl_order_product.pro_id,
	tbl_order_product.pro_model as part_no,
	tbl_order_product.proidentry,
	tbl_order_product.hsn_code,
	tbl_order_product.pro_name as product_name,	
	tbl_order_product.pro_price as unit_price,	
	tbl_order_product.pro_quantity as qty,
	tbl_order_product.GST_percentage ,	
	tbl_order_product.freight_amount,	
	tbl_order_product.Pro_tax as add_igst_value,	
	// tbl_order_product.pro_discount_amount as discount_amount,
(tbl_order_product.pro_price * (prowise_discount.discount_percent / 100)) as discount_amount,
	tp.pro_max_discount,
	tp.upc_code,
	tpe.pro_desc_entry as product_description,
	( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount) as product_discounted_price,
(( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount)) * (tbl_order_product.GST_percentage/100) as pro_tax_gst,	
		(( tbl_order_product.pro_price - tbl_order_product.pro_discount_amount) *  tbl_order_product.pro_quantity )  as sub_total_without_gst_edit,		
			((tbl_order_product.pro_price - tbl_order_product.pro_discount_amount) * tbl_order_product.pro_quantity) * (tbl_order_product.GST_percentage /100) as Pro_tax_edit,	
(( tbl_order_product.pro_price - tbl_order_product.pro_discount_amount  ) * (tbl_order_product.pro_quantity )) +  ((( tbl_order_product.pro_price -  tbl_order_product.pro_discount_amount) * (tbl_order_product.pro_quantity )) * (tbl_order_product.GST_percentage /100))  as sub_total_with_gst_edit,	
( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount) +  ((( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount)) * (tbl_order_product.GST_percentage/100))  as sub_total	
	from tbl_order_product
	LEFT JOIN tbl_products tp on tp.pro_id=tbl_order_product.pro_id
	LEFT JOIN tbl_products_entry tpe on tpe.pro_id_entry=tbl_order_product.proidentry

	where tbl_order_product.order_id= '$ID'	";   	
}
    $rs = DB::select(DB::raw($sql));
	
	

  // echo $sql; exit;
	
	
	
/*    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";*/
	return $rs;
}




//added on 27-jan-2026 by rumit to get data as per offerslisting api
function  product_name_generated_with_quantity_json_tbl_order_product_listing($ID){ 

    $sql_dis = "select 
	tbl_order_product.pro_discount_amount as discount
	from tbl_order_product
	where tbl_order_product.order_id= '$ID'";   
		
    $rs_dis = DB::select(DB::raw($sql_dis));


    $sql_dis_prowise = "select 
	discount_amount
	from prowise_discount
	where orderid= '$ID'";   
		
    $rs_dis_prowise = DB::select(DB::raw($sql_dis_prowise));


"dis:".	$discount= isset($rs_dis[0]->discount) ? $rs_dis[0]->discount : '0';
"dis_prowise:".$prowise_discount_amount= isset($rs_dis_prowise[0]->discount_amount) ? $rs_dis_prowise[0]->discount_amount : '0'; //exit;
;

if($prowise_discount_amount!=0 && $prowise_discount_amount!='')
{
      $sql = "select 
	tbl_order_product.order_pros_id,
	tbl_order_product.pro_id,
	tbl_order_product.pro_model,
	tbl_order_product.proidentry,
	tbl_order_product.hsn_code,
	tbl_order_product.pro_name ,	
	tbl_order_product.pro_price,	
	tbl_order_product.pro_quantity ,
	tbl_order_product.GST_percentage ,	
	tbl_order_product.freight_amount,	
	tbl_order_product.service_period,
	tbl_order_product.Pro_tax as Pro_tax1,				
	tbl_order_product.coupon_id as discount_percentage,
	tp.pro_max_discount,
	tbl_order_product.pro_discount_amount as pro_discount_amount1,
    (prowise_discount.discount_amount)  as pro_discount_amount,	
    (prowise_discount.discount_amount/tbl_order_product.pro_quantity)  as pro_discount_amount_per_item,	
	(prowise_discount.discount_amount * tbl_order_product.pro_quantity)  as pro_discount_amount_total,	
	tp.upc_code,
	tpe.pro_desc_entry as product_description,
    prowise_discount.discount_percent  as discount_percentage,
    prowise_discount.orderid,
	
	((tbl_order_product.pro_price - prowise_discount.discount_amount) * tbl_order_product.pro_quantity) * (tbl_order_product.GST_percentage /100) as Pro_tax_edit,
	((tbl_order_product.pro_price - prowise_discount.discount_amount ) * tbl_order_product.pro_quantity )  as sub_total_without_gst,	
		((tbl_order_product.pro_price - prowise_discount.discount_amount) * tbl_order_product.pro_quantity) * (tbl_order_product.GST_percentage /100) as pro_tax_gst_edit,	
(( tbl_order_product.pro_price -prowise_discount.discount_amount  ) * (tbl_order_product.pro_quantity )) +  ((( tbl_order_product.pro_price -  prowise_discount.discount_amount) * (tbl_order_product.pro_quantity )) * (tbl_order_product.GST_percentage /100))  as sub_total_with_gst_edit,	


	((tbl_order_product.pro_price - prowise_discount.discount_amount) * tbl_order_product.pro_quantity) * (tbl_order_product.GST_percentage /100) as Pro_tax_edit,
	
 (( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount)) * (tbl_order_product.GST_percentage /100) as Pro_tax,
(( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount)) +  ((( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount)) * (tbl_order_product.GST_percentage /100))  as sub_total,	
( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (prowise_discount.discount_amount)  as sub_total_without_gst	
	from tbl_order_product
	LEFT JOIN tbl_products tp on tp.pro_id=tbl_order_product.pro_id
	LEFT JOIN tbl_products_entry tpe on tpe.pro_id_entry=tbl_order_product.proidentry
	LEFT JOIN prowise_discount on prowise_discount.proid = tbl_order_product.pro_id
	where tbl_order_product.order_id= '$ID'
	and prowise_discount.orderid='$ID'";   
}
else
{
      $sql = "select 
	tbl_order_product.order_pros_id,
	tbl_order_product.pro_id,
	tbl_order_product.pro_model ,
	tbl_order_product.proidentry,
	tbl_order_product.hsn_code,
	tbl_order_product.pro_name ,	
	tbl_order_product.pro_price,	
	tbl_order_product.pro_quantity ,
	tbl_order_product.GST_percentage ,	
	tbl_order_product.freight_amount,
	tbl_order_product.service_period,		
	tbl_order_product.Pro_tax as Pro_tax1,		
	tbl_order_product.coupon_id as discount_percentage,	
	tp.pro_max_discount,
	(tbl_order_product.pro_discount_amount) as pro_discount_amount,
    (tbl_order_product.pro_discount_amount / tbl_order_product.pro_quantity)  as pro_discount_amount_per_item,	
		(tbl_order_product.pro_discount_amount * tbl_order_product.pro_quantity)  as pro_discount_amount_total,	
	tp.upc_code,
	tpe.pro_desc_entry as product_description,
	
	((tbl_order_product.pro_price - tbl_order_product.pro_discount_amount) * tbl_order_product.pro_quantity) * (tbl_order_product.GST_percentage /100) as Pro_tax_edit,	
	(( tbl_order_product.pro_price - tbl_order_product.pro_discount_amount) *  tbl_order_product.pro_quantity )  as sub_total_without_gst_edit,		

	
(( tbl_order_product.pro_price - tbl_order_product.pro_discount_amount  ) * (tbl_order_product.pro_quantity )) +  ((( tbl_order_product.pro_price -  tbl_order_product.pro_discount_amount) * (tbl_order_product.pro_quantity )) * (tbl_order_product.GST_percentage /100))  as sub_total_with_gst_edit,		
			
	
	( ( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount)) * (tbl_order_product.GST_percentage/100) as Pro_tax,
(( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount)) +  (( ( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount)) * (tbl_order_product.GST_percentage/100))  as sub_total,
( tbl_order_product.pro_price *  tbl_order_product.pro_quantity ) - (tbl_order_product.pro_discount_amount)  as sub_total_without_gst		
	from tbl_order_product
	LEFT JOIN tbl_products tp on tp.pro_id=tbl_order_product.pro_id
	LEFT JOIN tbl_products_entry tpe on tpe.pro_id_entry=tbl_order_product.proidentry
	where tbl_order_product.order_id= '$ID'	";   	
}
    $rs = DB::select(DB::raw($sql));
	
	//echo "TAX	:<br>".$rs["0"]->Pro_tax;
	
	
	//print_r($rs);
	

// echo $sql;  exit;
	
	
	
/*    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";*/
	return $rs;
}



function grand_total_offer($ID){ 



      $sql_dis = "select 
	tbl_order_product.pro_discount_amount as discount
	from tbl_order_product
	where tbl_order_product.order_id= '$ID'";   
		
    $rs_dis = DB::select(DB::raw($sql_dis));


      $sql_dis_prowise = "select 
	discount_amount
	from prowise_discount
	where orderid= '$ID'";   
		
    $rs_dis_prowise = DB::select(DB::raw($sql_dis_prowise));


"dis:".	$discount= isset($rs_dis[0]->discount) ? $rs_dis[0]->discount : '0';
  "dis_prowise:".$prowise_discount_amount= isset($rs_dis_prowise[0]->discount_amount) ? $rs_dis_prowise[0]->discount_amount : '0'
;

if($prowise_discount_amount!=0 && $prowise_discount_amount!='')
{
   $sql_total ="SELECT 
    o.orders_id, 
    o.freight_amount,  
    (o.freight_amount * 0.18) AS freight_gst_amount,  
    SUM((tbl_order_product.pro_price * tbl_order_product.pro_quantity) 
        - (tbl_order_product.pro_discount_amount) 
        + (tbl_order_product.Pro_tax)
    ) 
    + o.freight_amount 
    + (o.freight_amount * 0.18) AS grand_total
FROM tbl_order_product
INNER JOIN tbl_order o ON o.orders_id = tbl_order_product.order_id
LEFT JOIN tbl_products tp ON tp.pro_id = tbl_order_product.pro_id
LEFT JOIN tbl_products_entry tpe ON tpe.pro_id_entry = tbl_order_product.proidentry
LEFT JOIN prowise_discount ON prowise_discount.proid = tbl_order_product.pro_id
WHERE tbl_order_product.order_id = '$ID'
AND prowise_discount.orderid = '$ID'";		//  exit;
	
}
else
{
    
	
 $sql_total ="SELECT 
    o.orders_id, 
    o.freight_amount,  
    (o.freight_amount * 0.18) AS freight_gst_amount,  
    SUM((tbl_order_product.pro_price * tbl_order_product.pro_quantity) 
        - (tbl_order_product.pro_discount_amount) 
        + (tbl_order_product.Pro_tax)
    ) 
    + o.freight_amount 
    + (o.freight_amount * 0.18) AS grand_total
FROM tbl_order_product
INNER JOIN tbl_order o ON o.orders_id = tbl_order_product.order_id
LEFT JOIN tbl_products tp ON tp.pro_id = tbl_order_product.pro_id
LEFT JOIN tbl_products_entry tpe ON tpe.pro_id_entry = tbl_order_product.proidentry
WHERE tbl_order_product.order_id = '$ID';
";	
	
}
  //  $rs = DB::select(DB::raw($sql));
 $rs_total = DB::select(DB::raw($sql_total));
	
// echo $sql_total; exit;
	

	
/*    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";*/
	return $rs_total;
}



function lead_products_listing_json($ID){ 

      $sql = "select 
	tbl_lead_product.lead_pros_id,
	tbl_lead_product.pro_id,
	tbl_lead_product.pro_category,	
	tbl_lead_product.upc_code,	
	tbl_lead_product.proidentry,	
	tbl_lead_product.pro_tax,			
	tbl_lead_product.hsn_code,				
	tbl_lead_product.customers_id,					
	tbl_lead_product.price_list,						
	(tbl_lead_product.pro_price * tbl_lead_product.pro_quantity) as estimated_value,							
	tbl_lead_product.pro_name,			
	tbl_lead_product.pro_price,	
	tbl_lead_product.pro_quantity,
	tbl_lead_product.service_period,
    tbl_application.application_name
	from tbl_lead_product
LEFT JOIN tbl_application ON tbl_application.application_id=tbl_lead_product.pro_category  
	
	
where tbl_lead_product.lead_id= $ID";   

    $rs = DB::select(DB::raw($sql));
/*    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";*/
	return $rs;
}



function lead_services_listing_json($ID){ 

      $sql = "select 
	tbl_lead_product.lead_pros_id,
	tbl_lead_product.pro_id,
	tbl_lead_product.pro_category,	
	tbl_lead_product.upc_code,	
	tbl_lead_product.proidentry,	
	tbl_lead_product.pro_tax,			
	tbl_lead_product.hsn_code,				
	tbl_lead_product.customers_id,					
	tbl_lead_product.price_list,						
	(tbl_lead_product.pro_price * tbl_lead_product.pro_quantity) as estimated_value,							
	tbl_lead_product.pro_name,			
	tbl_lead_product.pro_price,	
	tbl_lead_product.pro_quantity,
	tbl_lead_product.service_period,
    tbl_application_service.application_service_name as application_name 
	from tbl_lead_product
LEFT JOIN tbl_application_service ON tbl_application_service.application_service_id=tbl_lead_product.pro_category  
	
	
where tbl_lead_product.lead_id= $ID";   

    $rs = DB::select(DB::raw($sql));
/*    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";*/
	return $rs;
}


function lead_products_estimated_value_json($ID){ 

    $sql = "select 
	SUM(tbl_lead_product.pro_price * tbl_lead_product.pro_quantity) as total_estimated_value
	from  tbl_lead_product							
where tbl_lead_product.lead_id= $ID";   

    $rs = DB::select(DB::raw($sql));
/*    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";*/
	return $rs;
}


function product_name_generated_with_quantity_json($ID){ 

/*    echo  $sql = "select 
	tbl_order_product.pro_id,
  	tbl_do_products.ItemCode,	
	tbl_order_product.pro_name,
	tbl_do_products.description,
	tbl_do_products.price,
  	tbl_do_products.Quantity,
  	tbl_do_products.service_period,
  	tbl_do_products.is_service 
  	from tbl_order_product
INNER JOIN tbl_do_products ON tbl_do_products.OID=tbl_order_product.order_id
where tbl_do_products.OID = $ID";    */



  $sql = "SELECT 
    tbl_order_product.pro_id,
    tbl_do_products.ItemCode,
    tbl_order_product.pro_name as pro_name,
    tbl_do_products.price,
    tbl_do_products.Quantity,
    tbl_do_products.service_period,
    tbl_do_products.is_service
FROM tbl_order_product
INNER JOIN tbl_do_products ON tbl_do_products.OID = tbl_order_product.order_id
WHERE tbl_do_products.OID = $ID
GROUP BY tbl_order_product.pro_id";


/*$sql = "select 
	tbl_do_products.pro_id,
  	tbl_do_products.ItemCode,	
	tbl_do_products.description as pro_name,
	tbl_do_products.price,
  	tbl_do_products.Quantity,
  	tbl_do_products.service_period,
  	tbl_do_products.is_service 
  	from  tbl_do_products 
where tbl_do_products.OID = $ID";  */  

    $rs = DB::select(DB::raw($sql));
/*    $i=0;
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<li>".$pro_name = $row->pro_name." <b>(".$row->pro_quantity.")</b> "."</li>";
        }
    }
    echo "</ol>";*/
	return $rs;
}



function do_products_list_json($ID){ 
 $sql = "SELECT 
    tbl_order_product.pro_id,
    tbl_do_products.ID,
	tbl_do_products.pro_id as do_pro_id,
	tbl_do_products.hsn_code,
    tbl_do_products.pro_name as pro_name,
    tbl_do_products.Description as customer_product_name,	
    tbl_do_products.price,
	tbl_do_products.ItemCode as pro_model,
    tbl_do_products.Quantity as quantity,
    tbl_do_products.service_period,
	tbl_do_products.per_item_tax_rate as Pro_tax,
	tbl_do_products.is_service,
    tbl_do_products.S_Inst	as special_instructions
FROM tbl_order_product
INNER JOIN tbl_do_products ON tbl_do_products.OID = tbl_order_product.order_id
WHERE tbl_do_products.OID = '$ID'";
    $rs = DB::select(DB::raw($sql));  
	
  $num_rows	= count($rs); 	   //exit;
	return $rs;
}

function company_name($ID){

    $rowcust_name = DB::table('tbl_comp')->select('comp_name')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first();     
    $name	  = isset($rowcust_name->comp_name) ? $rowcust_name->comp_name : '';
    return ucwords($name);
}

function date_format_india($date){
    if($date!=''){
        $date_formate=date("d-M-Y", strtotime($date));
    }else{
        $date_formate="N/A";
    }
    return $date_formate;
}

function pageLocation($filename){
    echo "<SCRIPT LANGUAGE='JavaScript'>window.location='$filename'</SCRIPT>"; 
}

function get_enq_id($id){
    
    $row = DB::table('tbl_lead')->select('enq_id')->where('id', '=', $id)->where('deleteflag', '=', 'active')->first();
    $enq_id	  = isset($row->enq_id) ? $row->enq_id : '';
    return $enq_id;
}

function get_account_manager_by_role(){

    $AdminLoginID_SET = Auth::user()->id;
    $acc_manager_lead = $AdminLoginID_SET;
    $admin_team_id = admin_team($AdminLoginID_SET);
    $admin_role_id = admin_role_id($AdminLoginID_SET);

    $admin_sub_team_lead = @admin_sub_team_lead($admin_team_id); //added on 2may 2017
    $admin_sub_team_lead2 = @admin_sub_team_lead2($admin_team_id); //added on 2may 2017
    $admin_team_lead = @admin_team_lead($AdminLoginID_SET);
    $tm1 = '';

    if($admin_role_id=='0' or $admin_role_id=='5'){    
        $query           = "deleteflag = 'active' order by admin_fname";
    }else if($admin_role_id=='9' ){
            if($admin_team_lead==$AdminLoginID_SET){
                $query           = "deleteflag = 'active'  and admin_team_lead='$admin_team_lead'  order by admin_fname";
            }else if($admin_sub_team_lead==$AdminLoginID_SET){
                $query           = "deleteflag = 'active'  order by admin_fname"; //and id IN($tm1,$admin_sub_team_lead)
            }else if(($acc_manager_lead!=$admin_team_lead)&&($acc_manager_lead!=$admin_sub_team_lead)){
                $query           = "deleteflag = 'active' order by admin_fname"; //and id IN($tm1)
            }else{
                $query           = "deleteflag = 'active' and id =".$AdminLoginID_SET." order by admin_fname";
            }
    }else if($admin_role_id=='17' ){
            $query           = "deleteflag = 'active'  and admin_team='$admin_team_id' order by admin_fname";
    }else if($admin_role_id=='18' ){
            $query           = "deleteflag = 'active'  order by admin_fname";
    }else if($admin_role_id!='17' ){
            $query           = "deleteflag = 'active'  and admin_team='$admin_team_id' and id =".$AdminLoginID_SET." order by admin_fname";
    }else{
            $query           = "deleteflag = 'active' and id =".$AdminLoginID_SET." order by order by admin_fname";
    }
     
    $sql = "select id, admin_fname, admin_lname, CONCAT(admin_fname,' ',admin_lname) as full_name from tbl_admin where ".$query;    
    $rs_role = DB::select(DB::raw($sql));
    return $rs_role;
}



function service_application_id($IdValue){

	if(is_numeric($IdValue)){

        $rowApplication_pro_id = DB::table('tbl_index_s2')->select('service_id')->where('match_service_id_s2', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
		$service_appliaction_id	  = $rowApplication_pro_id->service_id;
	}else{
		$service_appliaction_id 	= $IdValue;
	}
	return $service_appliaction_id;
}


function pro_application_id($IdValue){

	if(is_numeric($IdValue)){

        $rsApplication_pro_id = DB::table('tbl_index_g2')->select('pro_id')->where('match_pro_id_g2', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
		$product_appliaction_id	  = $rsApplication_pro_id->pro_id;
	}else{
		$product_appliaction_id 	= $IdValue;
	}
	return $product_appliaction_id;
}

function ApplicationTax($IdValue){

	if(is_numeric($IdValue)){

        $rowApplication = DB::table('tbl_application')->select('tax_class_id')->where('application_id', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
	$tax_class_id	  =  isset($rowApplication->tax_class_id) ? $rowApplication->tax_class_id : '0';
	}else{
		$tax_class_id 	= $IdValue;
	}
	return $tax_class_id;
}

function ApplicationTaxService($IdValue){

	if(is_numeric($IdValue)){

        $rowApplication = DB::table('tbl_application_service')->select('tax_class_id')->where('application_service_id', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
		$tax_class_id	  =  isset($rowApplication->tax_class_id) ? $rowApplication->tax_class_id : '0';
	}else{
		$tax_class_id 	= $IdValue;
	}
	return $tax_class_id;
}

function product_entry_desc($ID){

        $row = DB::table('tbl_products_entry')->select('pro_desc_entry')->where('pro_id_entry', '=', $ID)->where('deleteflag', '=', 'active')->first();
		$pro_desc_entry	  = isset($row->pro_desc_entry) ? $row->pro_desc_entry : '';
		return $pro_desc_entry;
}

function product_entry_hsn_code($ID){

    $row = DB::table('tbl_products_entry')->select('hsn_code')->where('pro_id_entry', '=', $ID)->first();
    $hsn_code	  = $row->hsn_code;
    return  $hsn_code;
}

function product_max_discount($pid){

    $row = DB::table('tbl_products')->select('pro_max_discount')->where('pro_id', '=', $pid)->where('deleteflag', '=', 'active')->first();
    $pro_max_discount	  = isset($row->pro_max_discount) ? $row->pro_max_discount : '';
    return $pro_max_discount;
}

function service_max_discount($pid){

    $row = DB::table('tbl_services')->select('service_max_discount')->where('service_id', '=', $pid)->where('status', '=', 'active')->first();
    $service_max_discount	  = isset($row->service_max_discount) ? $row->service_max_discount : '';
    return $service_max_discount;
}

//// Customer Information

function QuickOfferCustomerInfo(){

    $shoppingCart = session('quickShoppingCart');
    
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = session('comp_id');  
        
        if(isset($custID)){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }
        
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            echo "<tr>";
            echo "<td colspan='7'>";
            echo "<h6 class='mb-2'><b>".ucfirst($rowInfo->fname)." ".ucfirst($rowInfo->lname)."</b></h6>";
            echo "<h6 class='mb-1'>E-Mail : $rowInfo->email</h6>";
            echo "<h6 class='mb-1'>Contact No. : $rowInfo->telephone</h6>";
            echo "<h6 class='mb-1'>Mobile. : $rowInfo->mobile_no</h6>";
            echo "</td>";
            echo "</tr>";	
        }
    }
}

function QuickOfferBillingInfo(){

    $shoppingCart = session('quickShoppingCart');
    $billingAddress = "";
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = session('comp_id');  
        
        if(isset($custID)){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address  from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            $country_name	   = CountryName($rowInfo->country);
            $state_name		   = StateName($rowInfo->state);
            $billingAddress  = $rowInfo->address."<br>".CityName($rowInfo->city)."<br>".$state_name."<br>".$country_name;	
        }
        echo $billingAddress;
    }
}

function QuickOfferShippingInfo(){

    $shoppingCart = session('quickShoppingCart');
    $shippingAddress = "";
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = session('comp_id');  
        if(isset($custID)){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        

        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            $country_name	   = CountryName($rowInfo->country);
            $state_name		   = StateName($rowInfo->state);
            $shippingAddress  = $rowInfo->address."<br>".CityName($rowInfo->city)."<br>".$state_name."<br>".$country_name;	
        }
        echo $shippingAddress;
    }
}


function CustomerInfo(){

    $shoppingCart = session('shoppingCart');
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = $shoppingCart[0]['cartInfo']['comp_id'];  
        $comp_person_id	 = $shoppingCart[0]['cartInfo']["comp_person_id"];
        if($comp_person_id=='0' || $comp_person_id=='1'){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }else{
            $sqlInfo = "select fname, lname, email, telephone, mobile_no from tbl_comp_person where id ='$comp_person_id' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            echo "<tr>";
            echo "<td colspan='7'>";
            echo "<h6 class='mb-2'><b>".ucfirst($rowInfo->fname)." ".ucfirst($rowInfo->lname)."</b></h6>";
            echo "<h6 class='mb-1'>E-Mail : $rowInfo->email</h6>";
            echo "<h6 class='mb-1'>Contact No. : $rowInfo->telephone</h6>";
            echo "<h6 class='mb-1'>Mobile. : $rowInfo->mobile_no</h6>";
            echo "</td>";
            echo "</tr>";	
        }
    }
}

function billingInfo(){

    $shoppingCart = session('shoppingCart');
    $billingAddress = "";
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = $shoppingCart[0]['cartInfo']['comp_id'];  
        $comp_person_id	 = $shoppingCart[0]['cartInfo']["comp_person_id"];
        if($comp_person_id=='0' || $comp_person_id=='1'){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }else{
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp_person where id ='$comp_person_id' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            $country_name	   = CountryName($rowInfo->country);
            $state_name		   = StateName($rowInfo->state);
            $billingAddress  = $rowInfo->address."<br>".CityName($rowInfo->city)."<br>".$state_name."<br>".$country_name;	
        }
        echo $billingAddress;
    }
}

function ServiceCustomerInfo(){

    $shoppingCart = session('serviceShoppingCart');
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = $shoppingCart[0]['cartInfo']['comp_id'];  
        $comp_person_id	 = $shoppingCart[0]['cartInfo']["comp_person_id"];
        if($comp_person_id=='0' || $comp_person_id=='1'){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }else{
            $sqlInfo = "select fname, lname, email, telephone, mobile_no from tbl_comp_person where id ='$comp_person_id' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            echo "<tr>";
            echo "<td colspan='7'>";
            echo "<h6 class='mb-2'><b>".ucfirst($rowInfo->fname)." ".ucfirst($rowInfo->lname)."</b></h6>";
            echo "<h6 class='mb-1'>E-Mail : $rowInfo->email</h6>";
            echo "<h6 class='mb-1'>Contact No. : $rowInfo->telephone</h6>";
            echo "<h6 class='mb-1'>Mobile. : $rowInfo->mobile_no</h6>";
            echo "</td>";
            echo "</tr>";	
        }
    }
}

function ServiceBillingInfo(){

    $shoppingCart = session('serviceShoppingCart');
    $billingAddress = "";
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = $shoppingCart[0]['cartInfo']['comp_id'];  
        $comp_person_id	 = $shoppingCart[0]['cartInfo']["comp_person_id"];
        if($comp_person_id=='0' || $comp_person_id=='1'){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }else{
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp_person where id ='$comp_person_id' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            $country_name	   = CountryName($rowInfo->country);
            $state_name		   = StateName($rowInfo->state);
            $billingAddress  = $rowInfo->address."<br>".CityName($rowInfo->city)."<br>".$state_name."<br>".$country_name;	
        }
        echo $billingAddress;
    }
}

function CountryName($IdValue){
	if(is_numeric($IdValue)){		
		$rowCountry = DB::table('tbl_country')->select('country_name')->where('country_id', '=', $IdValue)->where('deleteflag', '=', 'active')->first();		
		$country	= isset($rowCountry->country_name) ? $rowCountry->country_name : '';
	}else{
		$country 	= $IdValue;
	}
	return ucfirst($country);
}

function CompPersonName($id){
	$name = "";
    if(is_numeric($id)){		
		$rowCountry = DB::table('tbl_comp_person')->select('situ','fname','lname')->where('id', '=', $id)->where('deleteflag', '=', 'active')->first();		
		$name	= $rowCountry->situ. " ".$rowCountry->fname. " ".$rowCountry->lname;
	}
	return $name;
}

function shippingInfo(){

    $shoppingCart = session('shoppingCart');
    $shippingAddress = "";
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = $shoppingCart[0]['cartInfo']['comp_id'];  
        $comp_person_id	 = $shoppingCart[0]['cartInfo']["comp_person_id"];
        if($comp_person_id=='0' || $comp_person_id=='1'){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }else{
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp_person where id ='$comp_person_id' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            $country_name	   = CountryName($rowInfo->country);
            $state_name		   = StateName($rowInfo->state);
            $shippingAddress  = $rowInfo->address."<br>".CityName($rowInfo->city)."<br>".$state_name."<br>".$country_name;	
        }
        echo $shippingAddress;
    }
}

function serviceShippingInfo(){

    $shoppingCart = session('serviceShoppingCart');
    $shippingAddress = "";
    if(isset($shoppingCart[0]['cartInfo'])){
        $custID	 = $shoppingCart[0]['cartInfo']['comp_id'];  
        $comp_person_id	 = $shoppingCart[0]['cartInfo']["comp_person_id"];
        if($comp_person_id=='0' || $comp_person_id=='1'){
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp where id ='$custID' and  deleteflag ='active'";
        }else{
            $sqlInfo = "select fname, lname, email, telephone, mobile_no, country, state, city, address from tbl_comp_person where id ='$comp_person_id' and  deleteflag ='active'";
        }
        $rsInfo = DB::select(DB::raw($sqlInfo));        
        
        if(!empty($rsInfo)){
            $rowInfo = $rsInfo[0];	
            $country_name	   = CountryName($rowInfo->country);
            $state_name		   = StateName($rowInfo->state);
            $shippingAddress  = $rowInfo->address."<br>".CityName($rowInfo->city)."<br>".$state_name."<br>".$country_name;	
        }
        echo $shippingAddress;
    }
}

function fetchGeneral_config($valueType){

	// to get meta content ------------- meta_content
	// to get meta description --------- meta_desc
	// to get contact email ------------ contact
	// to get website title ------------ webtitle
	// to get the logo ----------------- StoreLogo
	// to get the display title -------- displayTitle
	// To Get Tax Location ------------- TaxLocation
	//store name ----------------------- store
	//admin email ---------------------- admin
	//order email ---------------------- order
	//customer support eamil ----------- support
	//general contact ------------------ info
	
    $row_mail = DB::table('tbl_general_configuraction')->select('*')->where('deleteflag', '=', 'active')->first();
    
	if(!empty($row_mail)){		
		if($valueType == "webtitle"){
			return $row_mail->website_title;
		}else if($valueType == "StoreLogo"){
			return $row_mail->store_logo;
		}

		if($valueType == "stock"){
			return $row_mail->stock_manager;
		}else if($valueType == "displayTitle"){
			return $row_mail->display_title;
		}else if($valueType == "store"){
			return $row_mail->store_name;
		}else if($valueType == "admin"){
			return $row_mail->admin_email;
		}else if($valueType == "order"){
			return $row_mail->admin_email;
		}else if($valueType == "contact"){
			return $row_mail->contact_email;
		}else if($valueType == "support"){
			return $row_mail->customer_support__email;
		}else if($valueType == "info"){
			return $row_mail->gen_contact_email;
		}else if($valueType == "meta_content"){
			return $row_mail->meta_content;
		}else if($valueType == "meta_desc"){
			return $row_mail->meta_desc;
		}else{
			return "error";
		}
	}else{
		return "error";
	}	
}

function get_ID_from_lead_id($lead_id){ 

    $row = DB::table('tbl_web_enq_edit')->select('ID')->where('lead_id', '=', $lead_id)->where('deleteflag', '=', 'active')->first();       
    $ID	  =$row->ID;
    return $ID;
}




function lead_id_from_enq_edit_table($enq_id){ 
    $row 			= DB::table('tbl_web_enq_edit')->select('lead_id')->where('ID', '=', $enq_id)->first();  
    $lead_id	  = isset($row->lead_id) ? $row->lead_id : '';
	
    return $lead_id;
}


function salutation_name($salutation_id){ 
    $row 			= DB::table('tbl_salutation')->select('salutation_name')->where('salutation_id', '=', $salutation_id)->first();  
    $salutation_name	  = isset($row->salutation_name) ? $row->salutation_name : '';
	
    return $salutation_name;
}



function order_id_from_enq_edit_table($enq_id){ 
    $row 			= DB::table('tbl_web_enq_edit')->select('order_id')->where('ID', '=', $enq_id)->first();  
    $order_id	  = isset($row->order_id) ? $row->order_id : '';
	
    return $order_id;
}



function company_name_check($comp_name){ 
    $row 			= DB::table('tbl_comp')->select('id','comp_name')->where('comp_name', '=', $comp_name)->first();  
	
	//$num_rows	= count($row); 	  
    $comp_id	  = isset($row->id) ? $row->id : '0';
	
    return $comp_id;
}



function supply_delivery_terms_name($id){

    $row = DB::table('tbl_supply_order_delivery_terms_master')->select('supply_order_delivery_terms_name')->where('supply_order_delivery_terms_id', '=', $id)->where('deleteflag', '=', 'active')->where('supply_order_delivery_terms_status', '=', 'active')->first(); 
    $supply_delivery_terms_name	  = isset($row->supply_order_delivery_terms_name) ? $row->supply_order_delivery_terms_name : '';

    return $supply_delivery_terms_name;
}

function supply_payment_terms_name($ref_source){

    $row = DB::table('tbl_supply_order_payment_terms_master')->select('supply_order_payment_terms_name')->where('supply_order_payment_terms_id', '=', $ref_source)->where('deleteflag', '=', 'active')->where('supply_order_payment_terms_status', '=', 'active')->first(); 
    $supply_payment_terms_name	  = isset($row->supply_order_payment_terms_name) ? $row->supply_order_payment_terms_name : '';
    return isset($supply_payment_terms_name) ? $supply_payment_terms_name : '';
}



function offer_validity_name($offer_validity_no){

    $row = DB::table('tbl_offer_validity_master')->select('offer_validity_name')->where('offer_validity_no', '=', $offer_validity_no)->where('deleteflag', '=', 'active')->first(); 
    $offer_validity_name	  = isset($row->offer_validity_name) ? $row->offer_validity_name : '';
    return isset($offer_validity_name) ? $offer_validity_name : '';
}

function warranty_name($warranty_id){

    if($warranty_id!='0'){
        $row = DB::table('tbl_warranty_master')->select('warranty_name')->where('warranty_id', '=', $warranty_id)->where('deleteflag', '=', 'active')->where('warranty_status', '=', 'active')->first(); 
        $warranty_name	  = isset($row->warranty_name) ? $row->warranty_name : '';
    }else{
        $warranty_name	  = "Nil";
    }
    return $warranty_name;
}




function designation_comp_name($ID)
{
	
$sqldesignation_name = "select designation_name from tbl_designation_comp where designation_id = '$ID' and deleteflag = 'active'";
    $rowdesignation_name = DB::select(DB::raw($sqldesignation_name)); 
    $designation_name	  = isset($rowdesignation_name[0]->designation_name) ? $rowdesignation_name[0]->designation_name : '';
$designation_name;

return ucfirst($designation_name);
}	


function calibration_name($calibration_id){

    if($calibration_id!='0'){
        $row = DB::table('tbl_calibration_master')->select('calibration_name')->where('calibration_id', '=', $calibration_id)->where('deleteflag', '=', 'active')->where('calibration_status', '=', 'active')->first();         
        $calibration_name	  = isset($row->calibration_name) ? $row->calibration_name : '';
    }else{
        $calibration_name	  = "Nil";
    }
    return $calibration_name;
}

function team_abbrv($ID){

    $row = DB::table('tbl_team')->select('team_abbrv')->where('team_id', '=', $ID)->where('deleteflag', '=', 'active')->where('team_status', '=', 'active')->first(); 
    $team_abbrv	  = isset($row->team_abbrv) ? $row->team_abbrv : '';
    return $team_abbrv;
}

function admin_abrv($ID){

    $row = DB::table('tbl_admin')->select('admin_abrv')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first();
    if(isset($row->admin_abrv)){
        $admin_abrv	  = $row->admin_abrv;
    }else{
        $admin_abrv	  = "";
    }
    return $admin_abrv;
}

function lead_ref_source($leadid){

    $row = DB::table('tbl_lead')->select('ref_source')->where('id', '=', $leadid)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();
    $ref_source	  = isset($row->ref_source) ? $row->ref_source : "";
    return $ref_source;
}

function pro_code_offer($ID){

    $row = DB::table('tbl_products')->select('pro_code_offer')->where('pro_id', '=', $ID)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();    
    if(isset($row->pro_code_offer)){
        $pro_code_offer	  = $row->pro_code_offer;
    }else{
        $pro_code_offer	  = "";
    }
    return $pro_code_offer;
}


function pro_code_offer_all($ID){

    $row = DB::table('tbl_order_product')->select('pro_id')->where('order_id', '=', $ID)->where('deleteflag', '=', 'active')->get();    
    $pro_code_offer = '';
    foreach($row as $val){
        $pro_code_offer = pro_code_offer($val->pro_id);        
        if($pro_code_offer != '0' || $pro_code_offer != ''){
            $pro_code_offer_all = $pro_code_offer;
        }
    }    
    return $pro_code_offer;
}


function prod_ids_concat($orderid){

    $sql="SELECT GROUP_CONCAT( pro_id ) AS pro_ids FROM `tbl_order_product` WHERE order_id =$orderid";
    $row = DB::select(DB::raw($sql)); 
    $prod_ids_concat	  = isset($row->pro_ids) ? $row->pro_ids : '';
    return $prod_ids_concat;
}

function pro_app_cat_id($prod_ids_concat=''){

    $app_cat_id = '';
    if(!empty($prod_ids_concat)){
        $sql = "SELECT GROUP_CONCAT( app_cat_id ) AS app_cat_ids FROM `tbl_products_entry` WHERE `pro_id` IN ($prod_ids_concat)";
        $row = DB::select(DB::raw($sql)); 
        if(isset($row->app_cat_ids)){
            $app_cat_id	  = implode(',', array_unique(explode(',', $row->app_cat_ids)));
        }
    }
    return $app_cat_id;
}

function cat_abrv_app_cat_ids($lead_app_cat_id_pro_ids_concat=''){

    $cat_abrv = '';
    if(!empty($lead_app_cat_id_pro_ids_concat)){
        $sql = "select GROUP_CONCAT( cat_abrv ) AS cat_abrvs  from tbl_application where application_id IN($lead_app_cat_id_pro_ids_concat) ";
        $row = DB::select(DB::raw($sql));     
        $cat_abrv	  = isset($row->cat_abrvs) ? str_replace( ',', '', $row->cat_abrvs ) : '';
    }
    return $cat_abrv;
}

function offer_revised_count($ID){

    $sql = "SELECT count( order_id ) as offer_revised_count FROM `tbl_offer_revised` WHERE order_id = '$ID' and deleteflag = 'active' and offer_revised_status='active'";
    $row = DB::select(DB::raw($sql)); 
    if(empty($row->offer_revised_count)){
        $offer_revised_count	  = "";
    }else{
        $offer_revised_count	  = "R".$row->offer_revised_count;
    }
    return $offer_revised_count;
}

function order_gen_info_new($orderId){

    $sql = "SELECT orders_id,offercode, follow_up_date, order_type, order_by, date_ordered, orders_status, orders_date_finished FROM `tbl_order` WHERE MD5(orders_id) = '$orderId' and deleteflag = 'active' ";
    $row = DB::select(DB::raw($sql)); 
    $row = $row[0];
    
    echo "<table class='table table-bordered'><tbody>";
    if(!empty($row)){
        $D_Order_Date = get_delivery_order_date($row->orders_id);
        $Dispatch = DO_dispatch_date($row->orders_id);
        $Dispatch1 = '';
        
        $PO_PATH = Get_DO_PO_path($row->orders_id);
        $PO_PATH = str_replace("uploadscrm","uploads",$PO_PATH);          

        $date 	  	= date('Y-m-d');
                        if($Dispatch=='Immediately') { $Dispatch1=0; }
						if($Dispatch=='1 Day') {$Dispatch1=1; }
						if($Dispatch=='1 Week') {$Dispatch1=7; }
						if($Dispatch=='2 Week') {$Dispatch1=14; }
						if($Dispatch=='1 Month') {$Dispatch1=30; }
						if($Dispatch=='2 Months') {$Dispatch1=60; }
                        echo "<tr>
                        <td class='ws-25'><b>Delivery Order No</b></td>
                        <td class='ws-25'>$row->orders_id</td>
                        <td class='ws-25'><b>Do Date</b></td>
                        <td class='ws-25'>". date_format_india($row->date_ordered) ."</td>
                      </tr>
                      <tr>
                        <td><b>UID</b></td>
                        <td class='ws-25'>".$row->offercode.'-'.$row->orders_id."</td>

                        <td><b>Dispatch Required By</b></td>
                        <td>".$delivery_required_by=dateSub_exact_date($D_Order_Date,$Dispatch1)."</td>
                      </tr>
                      <tr>
                        <td><b>PO No</b></td>
                        <td>".$po_number			= PO_no($row->orders_id)."</td>
                        <td><b>View PO</b></td>
                        <td><a href='".$PO_PATH."' target='_blank'><img src='/images/menu-icon/viewoffer.png' title='View Offer' alt='View Offer'></a></td>
                      </tr>	
                      <tr>
                        <td><b>PO Date</b></td>
                        <td>".$po_date			= date_format_india(PO_date_delivery_order($row->orders_id))."</td>
                        <td><b>Account Manager</b></td>
                        <td>".admin_name($row->order_by)."</td>
                      </tr>";

                echo "<tr class='text'> <th  valign='top'>Order Status </th><td valign='top'>$row->orders_status</th>";
                echo "<th  valign='top'>Ordered Date</th><td valign='top'>". date_format_india($row->date_ordered) ."</th> </tr>";
    }else{
        echo "<tr class='text'><td colspan='2' class='redstar'> &nbsp; No record present in database</td></tr>";
    }
        echo "</tbody></table>";
}

function DO_dispatch_date($orderid){

    $row = DB::table('tbl_delivery_order')->select('Dispatch')->where('O_Id', '=', $orderid)->first();     
    $dispatch_by	  = isset($row->Dispatch) ? $row->Dispatch : '';
    return $dispatch_by;
}

function getTaskType($tasktype_ids){

    $sql = "SELECT tasktype_id, tasktype_name, task_icon, tasktype_abbrv, tasktype_description FROM `tbl_tasktype_master` WHERE tasktype_id IN ($tasktype_ids)  and deleteflag = 'active' and tasktype_status='active'";
    $row = DB::select(DB::raw($sql)); 
    return $row;
}

function dateSub_exact_date($date,$days){

    $dreturn = '';
    if(!empty($date)){
        $date = @explode('-',$date);
        $year  = @$date[0];  
        $month = @$date[1];
        $date  = @$date[2];
        $time = @date('m/d/Y', @mktime(0, 0, 0, $date+$days, $month, $year));
        $dreturn = @date('d/m/Y', strtotime($date. ' + '. $days.' day'));        
    }
    return $dreturn;
}

function PO_no($orderid){

    $row = DB::table('tbl_delivery_order')->select('PO_NO')->where('O_Id', '=', $orderid)->first(); 
    $PO_NO	  = isset($row->PO_NO) ? $row->PO_NO : '';
    return $PO_NO;
}

function Get_DO_PO_path($ID) {

    $row = DB::table('tbl_delivery_order')->select('PO_path')->where('O_Id', '=', $ID)->first(); 
    $PO_path	  = isset($row->PO_path) ? $row->PO_path : '';
    return $PO_path;
}

function PO_date_delivery_order($orderid){

    $row = DB::table('tbl_delivery_order')->select('PO_Date')->where('O_Id', '=', $orderid)->first(); 
    $PO_Date	  = isset($row->PO_Date) ? $row->PO_Date : '';
    return $PO_Date;
}

function order_billing_address($orderid){

        echo "<table class='table table-bordered'><tbody>";
        $sql = "SELECT orders_id,customers_contact_no,customers_id,billing_name, billing_company, billing_street_address, billing_city, billing_zip_code, billing_state, billing_country_name, billing_telephone_no, billing_fax_no FROM `tbl_order` WHERE MD5(orders_id) = '$orderid' and deleteflag = 'active' ";
        $row = DB::select(DB::raw($sql)); 
        $row = $row[0];
    
        if(!empty($row)){            
            
            $customers_id					= $row->customers_id;
            $company_name					= company_name($row->customers_id);
            $customer_GST_no				= cutomer_GSTno($row->orders_id);

            echo "<tr><td><strong>Company Name</strong></td><td class='ws-49'> $company_name </td></tr>";
            echo "<tr><td><strong>Company GST</strong></td><td>$customer_GST_no </td></tr>";
            echo "<tr><td><strong>Buyer/ Customer Name</strong></td><td>$row->billing_name </td></tr>";
            if($row->billing_telephone_no!=''){
                echo "<tr><th><strong>Telephone No.</strong></th><td>$row->billing_telephone_no</td></tr>";
            }
            echo "<tr> <th><strong>Contact No.</strong></th><td>$row->customers_contact_no</td></tr>";
            echo "<tr><td><strong>Address</strong></td><td>".nl2br($row->billing_street_address)."</td></tr>";
            echo "<tr><td><strong>Country</strong></td><td>".CountryName($row->billing_country_name)."</td>";
            echo "<tr class='text'><th class='pad' valign='top'>State/Province</th><td valign='top'>".StateName($row->billing_state).' '.",  ".StateGSTcode($row->billing_state)."</td></tr>";
            echo "<tr><td><strong>City</strong></td><td>".CityName($row->billing_city)."</td></tr>";
            echo "<tr><td><strong>Zip/Postal Code</strong></td><td>$row->billing_zip_code</td></tr>";

        }else{
            echo "<tr><td colspan='2' class='redstar'> &nbsp; No record present in database</td></tr>";
        }
        echo "</tbody></table>";
}

function cutomer_GSTno($orderid){

    $row = DB::table('tbl_delivery_order')->select('Buyer_CST')->where('O_Id', '=', $orderid)->first(); 
    $Buyer_CST	  = isset($row->Buyer_CST) ? $row->Buyer_CST : '';
    return $Buyer_CST;
}

function StateGSTcode($STvalue){

    if(is_numeric($STvalue)){        
        $row = DB::table('tbl_zones')->select('state_code')->where('zone_id', '=', $STvalue)->where('deleteflag', '=', 'active')->first();
    }else{
        $row = DB::table('tbl_zones')->select('state_code')->where('zone_name', '=', $STvalue)->where('deleteflag', '=', 'active')->first();
    }    
    $state_code	  = isset($row->state_code) ? $row->state_code : '';
    return ucfirst($state_code);
}

function order_shipping_address($orderid){

    echo "<table class='table table-bordered'><tbody>";
    $sql = "SELECT orders_id,shipping_company, shipping_name, shipping_street_address, shipping_city, shipping_zip_code, shipping_state, shipping_country_name, shipping_telephone_no, shipping_fax_no FROM `tbl_order` WHERE MD5(orders_id) = '$orderid' and deleteflag = 'active' ";
    $row = DB::select(DB::raw($sql)); 
    $row = $row[0];
   
    if(!empty($row)){

        $consignee_GSTno		= consignee_GSTno($row->orders_id);
        $company_name			= consignee_company_name($row->orders_id);
        if($company_name==''){
            $company_name			= $row->shipping_company;
        }
        echo "<tr><td><strong>Company Name</strong></td><td class='ws-49'>".$company_name." </td></tr>";
        echo "<tr><td><strong>Company GST</strong></td><td >".$consignee_GSTno."</td></tr>";
        echo "<tr><td><strong>Consignee Name</strong></td><td >".$row->shipping_name."</td></tr>";
        echo "<tr><td><strong>Address</strong></td><td valign='top'>".nl2br($row->shipping_street_address)."</td></tr>";
        echo "<tr><td><strong>Country</strong></td><td>".CountryName($row->shipping_country_name)."</td></tr>";
        echo "<tr><td><strong>State/Province</strong></td><td>".StateName($row->shipping_state)."Code: ".StateGSTcode($row->shipping_state)."</td></tr>";
        echo "<tr><td><strong>City</strong></td><td>".$row->shipping_city."</td></tr>";
        echo "<tr><td><strong>Zip/Postal Code</strong></td><td>".$row->shipping_zip_code."</td></tr>";
        echo "<tr><td><strong>Telephone No.</strong></td><td>".$row->shipping_telephone_no."</td></tr>";
        if(isset($row->billing_fax_no)){
            echo "<tr ><td >Fax No.</td><td >: &nbsp;</td><td>".$row->shipping_fax_no."</td></tr>";
        }
    }

    echo "<tbody></table>";
}

function consignee_GSTno($orderid){

    $row = DB::table('tbl_delivery_order')->select('Con_CST')->where('O_Id', '=', $orderid)->first(); 
    $Con_CST	  = isset($row->Con_CST) ? $row->Con_CST : '';
    return $Con_CST;
}

function consignee_company_name($orderid){

    $row = DB::table('tbl_delivery_order')->select('Con_Com_name')->where('O_Id', '=', $orderid)->first();
    $consignee = isset($row->Con_Com_name) ? $row->Con_Com_name : '';
    return  $consignee;
}

function DO_special_instructions($orderid){

    $row = DB::table('tbl_delivery_order')->select('Special_Ins')->where('O_Id', '=', $orderid)->first();
    $Special_Ins	  = isset($row->Special_Ins) ? $row->Special_Ins : '';
    return $Special_Ins;
}

function product_name_generated($ID){ 

    $rs = DB::table('tbl_order_product')->select('pro_name')->where('order_id', '=', $ID)->get();    
    $i=0;
    foreach($rs as $row){
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<b>(".$i.")</b> ".$pro_name = $row->pro_name."<br/>";
        }
    }
}

function order_products($ID){ 

    $rs = DB::table('tbl_order_product')->select('pro_name')->where('order_id', '=', $ID)->get();    
    $i=0;
    $parray = [];
    foreach($rs as $row){
       
        if($row->pro_name!='' && $row->pro_name!='0' ){
            $parray[] = $row->pro_name;
        }
    }
    return $parray;
}

function performa_invoice_id($id){ 

    $row = DB::table('tbl_performa_invoice')->select('pi_id','O_Id')->where('O_Id', '=', $id)->where('deleteflag', '=', 'active')->first();     
    $pi_id	  = isset($row->pi_id) ? $row->pi_id : '0';
    return $pi_id;
}


function delivery_order_id($orderid){ 

    $row = DB::table('tbl_delivery_order')->select('DO_ID','O_Id')->where('O_Id', '=', $orderid)->where('DO_Status', '=', 'active')->first();     
    $DO_ID	  = isset($row->DO_ID) ? $row->DO_ID : '0';
    return $DO_ID;
}



function performa_invoice_status($pi_id){

    $row = DB::table('tbl_performa_invoice')->select('pi_status')->where('pi_id', '=', $pi_id)->where('save_send', '=', 'yes')->where('deleteflag', '=', 'active')->first(); 
    $pi_status	  = isset($row->pi_status) ? $row->pi_status : '';
    return $pi_status;
}

function service_price($id){
    
    $row = DB::table('tbl_services_entry')->select('service_price_entry')->where('service_id', '=', $id)->where('status', '=', 'active')->where('deleteflag', '=', 'active')->where('service_id', '=', $id)->where('price_list', '=', 'pvt')->first();
    $service_price_entry	  = isset($row->service_price_entry) ? $row->service_price_entry : '';
    return $service_price_entry;
}

function get_offer_calibration($order_id){

    $row = DB::table('tbl_order')->select('offer_calibration')->where('orders_id', '=', $order_id)->where('deleteflag', '=', 'active')->first(); 
    $offer_calibration	  = $row->offer_calibration;
    return $offer_calibration;
}

function getCurrentTask($offer_id){ 

    $sql = "SELECT * FROM `events` WHERE `offer_id`='".$offer_id."' AND deleteflag='active' AND status='Pending' AND `start_event` >= CURDATE() order by start_event,id ASC limit 0,1 ";
    $row = DB::select(DB::raw($sql));
    $row = isset($row[0]) ? $row[0] : $row;
    
    return $row;
}


function getTaskList($offer_id){ 

    $sql = "SELECT * FROM `events` WHERE `lead_type`='".$offer_id."' AND deleteflag='active' AND status='Pending' AND `start_event` >= CURDATE() order by start_event,id ASC  "; //exit;
    $row = DB::select(DB::raw($sql));
   // $row = isset($row[0]) ? $row[0] : $row;
    
    return $row;
}

function get_evttxt_icon($evttxt){

    $row = DB::table('tbl_tasktype_master')->select('task_icon','tasktype_name')->where('tasktype_abbrv', '=', $evttxt)->where('deleteflag', '=', 'active')->first();     
    return $row;
}

function getOverdueTask($offer_id){ 

    $sql = "SELECT * FROM `events` WHERE `offer_id`='".$offer_id."' AND deleteflag='active' AND status='Pending' AND `start_event` < CURDATE() order by start_event,id ASC ";
    $row = DB::select(DB::raw($sql));
        
    return count($row);
}








function sanitize_from_word( $content ){
    
    $replace = array(
        "‘" => "'",
        "’" => "'",
        "”" => '"',
        "“" => '"',
        "–" => "-",
        "—" => "-",
        "…" => "&#8230;"
    );
    foreach($replace as $k => $v){
        $content = str_replace($k, $v, $content);
    }
    $content = preg_replace('/[^\x20-\x7E]*/','', $content);
    return $content;
}

function ModeName($STvalue){

    if(is_numeric($STvalue)){
        $rowMode = DB::table('tbl_mode_master')->select('mode_name')->where('mode_id', '=', $STvalue)->where('deleteflag', '=', 'active')->first();         
        $Mode	  = isset($rowMode->mode_name) ? $rowMode->mode_name : '';
    }else{
        $Mode = $STvalue;
    }
    return ucfirst($Mode);
}

function product_entry_desc_bymodel($model_no){

    $row = DB::table('tbl_products_entry')->select('pro_id')->where('model_no', '=', $model_no)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();
    $pro_id	  = isset($row->pro_id) ? $row->pro_id : '';
    return $pro_id;
}

function ProHsn_code_by_modal($IdValue){  
    
    $rowApplication = DB::table('tbl_products_entry')->select('hsn_code')->where('model_no','LIKE',"%{$IdValue}%")->where('hsn_code', '!=', '0')->where('deleteflag', '=', 'active')->first(); 
    $hsn_code	  = isset($rowApplication->hsn_code) ? $rowApplication->hsn_code : '';
    return $hsn_code;
}

function fileUpload($destnation, $filename, $codeupname="case"){ 

    if($_FILES[$filename]['name'] != ""){
            $unique_id_query = strtoupper(substr(md5(uniqid(rand(), true)), 0 ,6));
            $unique_add      = $unique_id_query;
            $unique_name     = $destnation.$codeupname.$unique_add;
        if($_FILES[$filename]["error"] > 0){
                return -1;	
        }else{
                $uploadedfile = $_FILES[$filename]['tmp_name'];
                $destination1 = $unique_name.$_FILES[$filename]['name']; 
                $path		  = "../".$destination1;
                $Result 	  = move_uploaded_file($uploadedfile, $path);
                if(!$Result){
                        return -1;
                }else{
                        return $destination1; 
                }
        }
    }else{
             return -1; 
    }
}

function ProHsn_code_by_modalNo($IdValue){  
    
    $rowApplication = DB::table('tbl_products_entry')->select('hsn_code')->where('model_no','=',$IdValue)->where('hsn_code', '!=', '0')->where('deleteflag', '=', 'active')->first(); 
    $hsn_code	  = isset($rowApplication->hsn_code) ? $rowApplication->hsn_code : '';
    return $hsn_code;
}

function serviceHsn_code_by_modalNo($IdValue){  
    
    $rowApplication = DB::table('tbl_services_entry')->select('hsn_code')->where('model_no','=',$IdValue)->where('hsn_code', '!=', '0')->where('deleteflag', '=', 'active')->first(); 
    $hsn_code	  = isset($rowApplication->hsn_code) ? $rowApplication->hsn_code : '';
    return $hsn_code;
}

function PerformaItemsInfo_invoice1($pcode,$order_master,$order_details){
    
        $sql_dis_row_currency = DB::table('tbl_order')->where('orders_id', '=', $pcode)->first();

        $Offer_Currency = $sql_dis_row_currency->Price_value;
        $Offer_Currency = str_replace("backoffice","crm",$Offer_Currency);
        //$symbol		= currencySymbol(1);
        $Offer_Currency = getOfferCurrency($Offer_Currency, $order_master->offer_currency);
       // $currency1 	= $symbol[0];
       // $curValue 	= $symbol[1];
        $totalCost  = 0;
        $Price_type = $sql_dis_row_currency->Price_type;
        
        echo "<table width='100%' border='0' cellpadding='5' cellspacing='0' class='tblBorder_invoice_right   tblBorder_invoice_left'>";       
        echo "<tr class='head'>";
        echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right' width='2%' nowrap='nowrap'><strong>S.No</strong></td>";
        echo "<td width='50%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right'><strong>Product Name</strong></td>";
        echo "<td width='10%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right'><strong>HSN Code</strong></td>";
        echo "<td width='5%' class='tblBorder_invoice_bottom tblBorder_invoice_right' align='center'><strong>Qty</strong></td><td width='10%' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' align='center'><strong>Rate</strong></td>";
        
        //$sql_dis_row = DB::table('prowise_discount')->select('show_discount')->where('orderid', '=', $pcode)->first();
        
        if($sql_dis_row_currency->show_discount == "Yes") {
            echo "<td width='10%' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' align='center' style='display:none'><strong>Discount</strong></td>";            
        }        
        echo "<td width='10%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right' align='center' style='display:none'><strong>Add IGST Value (%)</strong></td>";        
        echo "<td nowrap='nowrap' width='10%' class='tblBorder_invoice_bottom ' nowrap='nowrap' align='center'><strong>Amount</strong></td></tr>";        
       
        $sql_dis_row = DB::table('tbl_order_product')->where('order_id', '=', $pcode)->orderby('order_pros_id','asc')->get();
        $h=0;        
        
        //echo "<pre>";
        //print_r($sql_dis_row);
       // exit;   
       $price_total = 0;   
        foreach($sql_dis_row as $rowOrderPro){
           
            $h++;
            echo "<tr class='text'>";
            echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right'>".$h."</td>";
            echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right' valign='top'><strong>".$rowOrderPro->pro_name."</strong><br />";
            
            $OrderProID	 = $rowOrderPro->order_pros_id;
            $proID	 	 = $rowOrderPro->pro_id;
            $groupID 	 = $rowOrderPro->group_id;
           
            $rsAttr = DB::table('tbl_group')->where('group_id', '=', $groupID)->first();
            
            $i  	 = -1;
            $k  	 = 0;
            $cb 	 = 0;
            $tx		 = 0;
            $ta		 = 0;
            if($cb != 0){
            }
            if($tx!=0){
            }
            if($ta != 0){
            }
            
            if($order_master->offer_type=='product'){
                $hsn_code = isset($rowOrderPro->pro_model) ? ProHsn_code_by_modalNo($rowOrderPro->pro_model) : '';
            }
            if($order_master->offer_type=='service'){
                $hsn_code = isset($rowOrderPro->pro_model) ? ServiceHsn_code_by_modalNo($rowOrderPro->pro_model) : '';
            }
            if($hsn_code==''){
                $hsn_code="N/A";
            }
            
            if($hsn_code == ''){
                $hsn_code = "N/A";
            }

            echo "</td>";
            echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right'>$hsn_code</td>";
            echo "<td valign='top' align='center' class='tblBorder_invoice_bottom tblBorder_invoice_right'>$rowOrderPro->pro_quantity</td>";
            
            $ManufacturerID = $rowOrderPro->manufacturers_id;
            $orPrice = $rowOrderPro->pro_price;
           
            $price_total = ($rowOrderPro->pro_price - $rowOrderPro->pro_discount_amount);
            $ProTotalPrice = ($price_total * $rowOrderPro->pro_quantity);

            //$ProTotalPrice = ($rowOrderPro->pro_price * $rowOrderPro->pro_quantity) - $rowOrderPro->pro_discount_amount; 

            echo "<td valign='top' align='center' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap'> ";
            echo $Offer_Currency." ";
            echo $per_product_price = number_format($ProTotalPrice,2);
            echo "</td>";
            echo "<td valign='top' align='center' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'> ";
            echo $Offer_Currency." ";
           // $ProTotalPrice =  $rowOrderPro->sub_total;  
            echo "<td valign='top' align='center' class='tblBorder_invoice_bottom ' nowrap='nowrap'>";
            echo $Offer_Currency." "; 
            echo number_format($ProTotalPrice,2);
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
}

/*performa invoice edit function*/

function OrderItemsInfo_invoice1_edit_performa($pcode,$order_master,$order_details){        
        
         $Offer_Currency = $order_master->Price_value;
         $Offer_Currency = str_replace("backoffice","crm",$Offer_Currency);
        
        //$symbol		= currencySymbol(1);
       // $currency1 	= $symbol[0];
        //$curValue 	= $symbol[1];

        $Offer_Currency = getOfferCurrency($Offer_Currency, $order_master->offer_currency);
        $totalCost  = 0;
        

        echo "<table width='100%' border='0' cellpadding='1' cellspacing='0' class='tblBorder_invoice_right1   tblBorder_invoice_left1'>";
        echo "<tr class='head'>";
        echo "<th class='pad tblBorder_invoice_bottom tblBorder_invoice_right' width='2%' >S.No</td>";
        echo "<th width='10%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right' >Product Name</th>";
        echo "<th width='5%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right' >HSN Code</th>";
        echo "<th width='5%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right' >Part No.</th>";

        echo "<th width='5%' class='tblBorder_invoice_bottom tblBorder_invoice_right'>Qty</th>
        <th width='10%' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap'>Unit Price</th>
        <th width='10%' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap'>Rate</th>";
        
        $sql_dis_row = DB::table('prowise_discount')->select('show_discount')->where('orderid', '=', $pcode)->first(); 
        
        if($order_master->show_discount=="Yes") {
            echo "<th width='10%' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'>Discount(-)</th>";
            $dis_td=1;
        }else{
        }
        echo "<th width='10%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right' style='display:none'>Add IGST Value(%)</th>";
        echo "<th nowrap='nowrap' width='10%' class='tblBorder_invoice_bottom '>Sub Total</th></tr>";
        
        $h=0;
        //echo "<pre>";
       // print_r($order_details);
       // exit;
        $hsn_code = '';
        $price_total = 0;
        foreach($order_details as $rowOrderPro){
           
            $h++;
            $discounted_price = 0;
            echo "<tr class='text'>";
            
            echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right'>".$h."</td>";
            echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right' valign='top'><strong>".$rowOrderPro->pro_name."</strong><br />";
            $OrderProID	 = $rowOrderPro->order_pros_id;
            $proID	 	 = $rowOrderPro->pro_id;
            $groupID 	 = $rowOrderPro->group_id;
            $pro_max_discount_allowed = product_discount($proID);
           
            //$hsn_code = $rowOrderPro->hsn_code;
            if($order_master->offer_type=='product'){
                $hsn_code = isset($rowOrderPro->pro_model) ? ProHsn_code_by_modalNo($rowOrderPro->pro_model) : '';
            }
            if($order_master->offer_type=='service'){
                $hsn_code = isset($rowOrderPro->pro_model) ? ServiceHsn_code_by_modalNo($rowOrderPro->pro_model) : '';
            }
            if($hsn_code==''){
                $hsn_code="N/A";
            }

            echo "</td>";
            echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right'> $hsn_code </td>";
            echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right'> $rowOrderPro->pro_model </td>";

            echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right'>";
            echo '<input type="text" name="pro_qty[]" value="'. $rowOrderPro->pro_quantity.'" size="2" class="form-control">';

            $orPrice = $rowOrderPro->pro_price;
            echo "<td valign='top' align='center' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap'> ";
            echo $Offer_Currency." ";
            $per_product_price = number_format($orPrice,2);
            echo  $per_product_price;
            echo '<input type="hidden" name="per_product_price[]" value="'. $orPrice.'"  class="form-control">';
            echo '<input type="hidden" name="pro_max_discount_allowed[]" value="'. $pro_max_discount_allowed.'" size="4" class="form-control">';
            echo "</td>";

            $sql_dis_row = DB::table('prowise_discount')->select('show_discount')->where('orderid', '=', $pcode)->where('proid', '=', $proID)->first(); 
            
            if(isset($rowOrderPro->show_discount) == "Yes") {
            echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'>";
            echo $Offer_Currency." "; 
            echo isset($rowOrderPro->discount_amount) ? number_format($rowOrderPro->discount_amount,2) : '';
            echo "<br />";
           
            //$discounted_price = 0;
            if(isset($rowOrderPro->discount_percent)){
                $discounted_price = $orPrice * $rowOrderPro->discount_percent/100;
            } 			

            echo '<input type="hidden" name="pro_price1[]" value="'. $rowOrderPro->pro_price.'">';
            echo '<input type="hidden" name="pro_id[]" value="'. $rowOrderPro->pro_id.'" >';
            $discount_percent = 0;
            if(isset($rowOrderPro->discount_percent)){
                $discount_percent = $rowOrderPro->discount_percent;
            }
            echo '<input type="hidden" name="discount_percent[]" value="'. $discount_percent .'" size="4" class="form-control"><br>'.$discount_percent.'%';
            echo "</td>";
            }
            else
            {
                if(isset($rowOrderPro->discount_percent) && ($rowOrderPro->discount_percent > 0)){
                    $discounted_price = $orPrice * $rowOrderPro->discount_percent/100;
                }

            echo '<input type="hidden" name="pro_price2[]" value="'. $rowOrderPro->pro_price.'" >';
            echo '<input type="hidden" name="pro_id[]" value="'. $rowOrderPro->pro_id.'" >';
            
            }
            echo '<input type="hidden" name="order_pros_id[]" value="'. $rowOrderPro->order_pros_id.'" >';
            echo "<td valign='top' align='center' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap'> ";
            echo $Offer_Currency." ";
            
            
            if($rowOrderPro->pro_final_price > 0){
                $per_product_price = $rowOrderPro->pro_final_price;
            }else{
                $per_product_price = ($rowOrderPro->pro_price - $rowOrderPro->pro_discount_amount);
            }
          
            $per_product_price = number_format($per_product_price,2, '.', '');
            
           //echo "<br />pro_price".$rowOrderPro->pro_price;
          // echo "<br >pro_discount_amount=".$rowOrderPro->pro_discount_amount;
          // exit;
          //echo $rowOrderPro->pro_price;exit;
         
           // $per_product_price = ($rowOrderPro->pro_price - $rowOrderPro->pro_discount_amount);
            
            echo '<input type="text" name="pro_price[]" value="'. $per_product_price.'" size="10" class="form-control" style="display:inline-block;text-align: right;" >';
            echo '<input type="hidden" name="pro_max_discount_allowed[]" value="'. $pro_max_discount_allowed.'" size="4" class="form-control">';
            echo "</td>";

            echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'> ";
            echo $Offer_Currency." ";

            $per_product_GST_percentage=str_replace("%","",$rowOrderPro->GST_percentage);

            echo $discounted_price_tax_amt=($orPrice-$discounted_price)*$per_product_GST_percentage/100;			
            echo "<br>(".$per_product_GST_percentage=$rowOrderPro->GST_percentage;
            echo ")</td>";
            
            // $sub_total = $rowOrderPro->sub_total * $rowOrderPro->pro_quantity;
           // echo "<pre>";
           // print_r($rowOrderPro);
           // exit;

            //echo $rowOrderPro->pro_discount_amount;

            $price_total = ($rowOrderPro->pro_price - $rowOrderPro->pro_discount_amount);
            $sub_total = ($price_total * $rowOrderPro->pro_quantity);

            //$sub_total = ($rowOrderPro->pro_price * $rowOrderPro->pro_quantity) - $rowOrderPro->pro_discount_amount;

            echo "<td valign='top' class='tblBorder_invoice_bottom ' nowrap='nowrap'>";
            echo $Offer_Currency." "; 
            echo number_format($sub_total,2);
            echo "</td>";

            echo "</tr>";

            }
        echo "</table>";
}

function proforma_invoice_total_edit($pcode,$rowOrderTotal,$rsOrderPro){
     
    
    //$symbol		= currencySymbol(1);
    //$currency1 	= $symbol[0];
    //$curValue 	= $symbol[1];

    if(!empty($rowOrderTotal)){    
    
    $Price_type = $rowOrderTotal->Price_type;        
    $h=0;
    $subtotal1=0;
    $totalTax = 0;
    $totalCost = 0;
    $total_gst = 0;

    $freight_amount = "0";//$rowOrderTotal->freight_amount; 
    
    $price_total = 0;
    foreach($rsOrderPro as $rowOrderPro){
    $h++;
    $OrderProID	 = $rowOrderPro->order_pros_id;
    $proID	 	 = $rowOrderPro->pro_id;
    $groupID 	 = $rowOrderPro->group_id;
   
    //$totalCost += ($rowOrderPro->pro_price * $rowOrderPro->pro_quantity) - $rowOrderPro->pro_discount_amount;
    
    $price_total = ($rowOrderPro->pro_price - $rowOrderPro->pro_discount_amount);
    $sub_total = ($price_total * $rowOrderPro->pro_quantity);
    $totalCost +=$sub_total;

    if($Price_type!='Export_USD')
    {
        $freight_amount_with_gst = $rowOrderPro->freight_amount/1.18;
        $freight_gst_amount = $rowOrderPro->freight_amount-$freight_amount_with_gst;
        $total_gst  = ($totalCost * 18) / 100; 
    }else{
        $freight_amount_with_gst = $rowOrderPro->freight_amount;
        $freight_gst_amount = 0;
        $total_gst  = 0; 
    }
    
    //$totalCost += ($rowOrderPro->sub_total * $rowOrderPro->pro_quantity);

    
    }

    if($Price_type!='Export_USD'){
    $GST_tax_amt				= GST_tax_amount_on_offer($pcode);			
    }else{
    $GST_tax_amt			= 0;			
    }
    $TotalOrder	   				= $rowOrderTotal->total_order_cost;
    $ship		   				= $rowOrderTotal->shipping_method_cost;
    $shippingValue				= $ship;
    $taxValue					= $rowOrderTotal->tax_cost;
    $tax_included				= $rowOrderTotal->tax_included;
    $tax_perc					= $rowOrderTotal->taxes_perc;
    $discount_perc				= $rowOrderTotal->discount_perc;
    $discount_per_amt			= $rowOrderTotal->discount_per_amt;
    $show_discount				=$rowOrderTotal->show_discount;
    
    }
    echo "<table width='50%' border='0' cellpadding='5' cellspacing='0' >";
    echo "<tr class='text'>";
    echo "<td width='45%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left ' nowrap align='right'><strong>Sub Total :</strong> </td>";
    echo "<td width='30%'  align='right' class='tblBorder_invoice_bottom ' nowrap='nowrap'> &nbsp; &nbsp;";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' ';
    echo number_format($totalCost-$totalTax,2);
    echo "</td>";
    echo "</tr>";

   
    echo "<tr class='text'>";
    echo "<td class='pad tblBorder_invoice_right tblBorder_invoice_bottom tblBorder_invoice_left' nowrap align='right'><strong>Freight Value :</strong></td>";
    echo "<td align='right'  class='tblBorder_invoice_bottom' nowrap='nowrap' >&nbsp; &nbsp; ";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    //$total_freight_amt_show = $freight_amount-$freight_gst_amount;   
    echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' '; 
    ?>
    <input type="text" name='freight_included' id='freight_included' value="<?php echo number_format($freight_amount,2, '.', '');?>" class='form-control' required dir="rtl" style="width:70%; display:inline; padding:0; margin:0; height:auto" />
    <?php
    echo "</td>";
    echo "</tr>";
    
    $freight_per_amount = 0;
    if($Price_type!='Export_USD'){
        $freight_per_amount = ($rowOrderPro->freight_amount * 18 ) /100;
        $total_gst = $total_gst + $freight_per_amount;
        echo "<tr class='text'>";       
        echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><strong>Add IGST :</strong></td>";       
        echo "<td align='right'  class='tblBorder_invoice_bottom'> &nbsp; &nbsp; ";
        echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' ';
        echo number_format($total_gst,2, '.', '');
        echo "</td>";
        echo "</tr>";
    }

    echo "<tr class='text'>";
    echo "<td class='pad tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><h4>Grand Total :</h4></td>";
    echo "<td align='right'  nowrap='nowrap'><h4> &nbsp; &nbsp; ";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' ';
    echo number_format($totalCost + $freight_amount + $total_gst,2);
    echo "</h4></td>";
    echo "</tr>";
    echo "</table>";
}


//whatsapp api by creating function 19-dec2019
function whatsapp_msg($phoneno,$message)
{
/**************whatsapp starts*/
//echo "whatsapp msg ::".$whatsaap_enq_ids=implode("\r\n",$emailary[$acc_manager]);
$data = [
    'phone' => $phoneno, // Receivers phone
    'body' => $message, // Message
];
$json = json_encode($data); // Encode data to JSON
// URL for request POST /message
$url =        'https://api.1msg.io/85376/sendMessage?token=86wkjz6mc9kyttpp'; //added on 18-dec-2019
// Make a POST request
$options = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-type: application/json',
        'content' => $json
   ]
]);
// Send a request
$result = file_get_contents($url, false, $options);
//print_r($result);
/****************************************************/
}


function proforma_invoice_total($pcode,$rowOrderTotal,$rsOrderPro){
     
    
    $symbol		= currencySymbol(1);
    $currency1 	= $symbol[0];
    $curValue 	= $symbol[1];

    if(!empty($rowOrderTotal)){
    
    $Price_type = $rowOrderTotal->Price_type;        
    $h=0;
    $subtotal1=0;
    $totalTax = 0;
    $totalCost = 0;
    $total_gst = 0;

    $freight_amount = "0";//$rowOrderTotal->freight_amount; 
    
    $price_total = 0;
    foreach($rsOrderPro as $rowOrderPro){
    $h++;
    $OrderProID	 = $rowOrderPro->order_pros_id;
    $proID	 	 = $rowOrderPro->pro_id;
    $groupID 	 = $rowOrderPro->group_id;
 
    //$tcost = ($rowOrderPro->pro_price * $rowOrderPro->pro_quantity) - $rowOrderPro->pro_discount_amount; 
    //$totalCost += $tcost; 

    $price_total = ($rowOrderPro->pro_price - $rowOrderPro->pro_discount_amount);
    $sub_total = ($price_total * $rowOrderPro->pro_quantity);
    $totalCost +=$sub_total;
    

    if($Price_type!='Export_USD')
    {
        $freight_amount_with_gst = $rowOrderPro->freight_amount/1.18;
        $freight_gst_amount = $rowOrderPro->freight_amount-$freight_amount_with_gst;
        $total_gst  = ($totalCost * 18) / 100; 
    }else{
        $freight_amount_with_gst = $rowOrderPro->freight_amount;
        $freight_gst_amount = 0;
        $total_gst  = 0; 
    }
    
    
    }

    if($Price_type!='Export_USD'){
    $GST_tax_amt				= GST_tax_amount_on_offer($pcode);			
    }else{
    $GST_tax_amt			= 0;			
    }
    $TotalOrder	   				= $rowOrderTotal->total_order_cost;
    $ship		   				= $rowOrderTotal->shipping_method_cost;
    $shippingValue				= $ship;
    $taxValue					= $rowOrderTotal->tax_cost;
    $tax_included				= $rowOrderTotal->tax_included;
    $tax_perc					= $rowOrderTotal->taxes_perc;
    $discount_perc				= $rowOrderTotal->discount_perc;
    $discount_per_amt			= $rowOrderTotal->discount_per_amt;
    $show_discount				=$rowOrderTotal->show_discount;
    
    }
    echo "<table width='50%' border='0' cellpadding='5' cellspacing='0' >";
    echo "<tr class='text'>";
    echo "<td width='45%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left ' nowrap align='right'><strong>Sub Total :</strong> </td>";
    echo "<td width='30%'  align='right' class='tblBorder_invoice_bottom ' nowrap='nowrap'> &nbsp; &nbsp;";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    
    echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' ';

    echo number_format($totalCost-$totalTax,2);
    echo "</td>";
    echo "</tr>";

   
    echo "<tr class='text'>";
    echo "<td class='pad tblBorder_invoice_right tblBorder_invoice_bottom tblBorder_invoice_left' nowrap align='right'><strong>Freight Value :</strong></td>";
    echo "<td align='right'  class='tblBorder_invoice_bottom' nowrap='nowrap' >&nbsp; &nbsp; ";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    //$total_freight_amt_show = $freight_amount-$freight_gst_amount;

    echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' ';
    ?>
    <?php echo number_format($freight_amount,2, '.', '');?>
    <?php
    echo "</td>";
    echo "</tr>";
   
    $freight_per_amount = 0;
    if($Price_type != 'Export_USD'){
        $freight_per_amount = ($rowOrderPro->freight_amount * 18 ) /100;
        $total_gst = $total_gst + $freight_per_amount;
        echo "<tr class='text'>";       
        echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><strong>Add IGST :</strong></td>";       
        echo "<td align='right'  class='tblBorder_invoice_bottom'  > &nbsp; &nbsp; ";
        echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' ';
        echo number_format($total_gst,2, '.', '');    
        echo "</td>";
        echo "</tr>";
    }

    echo "<tr class='text'>";
    echo "<td class='pad tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><h4>Grand Total :</h4></td>";
    echo "<td align='right'  nowrap='nowrap' ><h4> &nbsp; &nbsp; ";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    echo getOfferCurrency($rowOrderTotal->Price_value, $rowOrderTotal->offer_currency).' ';
    echo number_format($totalCost + $freight_amount + $total_gst,2);
    echo "</h4></td>";
    echo "</tr>";
    echo "</table>";
}
/*PI ends*/


function product_discount($id){   

    $row = DB::table('tbl_products')->select('pro_max_discount')->where('pro_id', '=', $id)->where('deleteflag', '=', 'active')->first();     
    $product_discount = isset($row->pro_max_discount) ? $row->pro_max_discount : '';
    return $product_discount;
}

function product_discount_service($id){   

    $row = DB::table('tbl_services')->select('service_max_discount')->where('service_id', '=', $id)->where('deleteflag', '=', 'active')->first();     
    $product_discount = isset($row->service_max_discount) ? $row->service_max_discount : '';
    return $product_discount;
}

function getOfferCurrency($default_currency,$offer_currency_id){   

    $currency_symbol = isset($default_currency) ? $default_currency : 'pvt';
    if($offer_currency_id > 0){
        $row = DB::table('tbl_currencies')->select('currency_symbol')->where('currency_id', '=', $offer_currency_id)->where('deleteflag', '=', 'active')->first();     
        $currency_symbol = isset($row->currency_symbol) ? $row->currency_symbol : '';
        $currency_symbol = html_entity_decode($currency_symbol);
    }
    return $currency_symbol;
}



function GST_tax_amount_on_offer($orderid){

    $sqlOrderTotal_GST = "SELECT sum( Pro_tax ) AS GST_amount FROM `tbl_order_product` WHERE `order_id` =$orderid GROUP BY order_id ";
    $rowOrderTotal_GST = DB::select(DB::raw($sqlOrderTotal_GST));
    $rowOrderTotal_GST = $rowOrderTotal_GST[0];
    if(!empty($rowOrderTotal_GST)){
        $gst_amt = $rowOrderTotal_GST->GST_amount;
    }
    return $gst_amt;
}

function get_lead_id_from_offer($id){ 
    
    $row = DB::table('tbl_order')->select('lead_id')->where('orders_id', '=', $id)->where('deleteflag', '=', 'active')->first();
    $lead_id	  = isset($row->lead_id) ? $row->lead_id : '';
    return $lead_id;
}

function lead_cust_segment($leadid){

    $row = DB::table('tbl_lead')->select('cust_segment')->where('id', '=', $leadid)->first();    
    $cust_segment	  = $row->cust_segment;
    return $cust_segment;
}

function offer_price_type($ID){

    $row = DB::table('tbl_order')->select('Price_type')->where('orders_id', '=', $ID)->first();
    $Price_type	  = $row->Price_type;
    return $Price_type;
}

function quantity_slab($proid){

    $row_qty_slab_pro_id = DB::table('tbl_products')->select('qty_slab')->where('pro_id', '=', $proid)->where('deleteflag', '=', 'active')->first();
    $quantity_slab	  = isset($row_qty_slab_pro_id->qty_slab) ? $row_qty_slab_pro_id->qty_slab : '';
	return $quantity_slab;
}

function quantity_slab_max_discount($proid,$pro_quantity){

    $sql_max_dis_data_qty_wise = DB::table('tbl_pro_qty_max_discount_percentage')->select('max_discount_percent')->where('min_qty', '<=', $pro_quantity)->where('max_qty', '>=', $pro_quantity)->where('proid', '=', $proid)->first();   
	$max_dis_last_qty_wise = isset($sql_max_dis_data_qty_wise->max_discount_percent) ? $sql_max_dis_data_qty_wise->max_discount_percent : '';	
	return $max_dis_last_qty_wise;
}



function quantity_slab_max_discount_table($proid){

    $sql_max_dis_data_qty_wise = DB::table('tbl_pro_qty_max_discount_percentage')->select('min_qty','max_qty','max_discount_percent')->where('proid', '=', $proid)->get();   

	return $sql_max_dis_data_qty_wise;
}


function quantity_slab_max_discount_table_service($proid){

    $sql_max_dis_data_qty_wise = DB::table('tbl_service_qty_max_discount_percentage')->select('min_qty','max_qty','max_discount_percent')->where('serviceid', '=', $proid)->get();   

	return $sql_max_dis_data_qty_wise;
}



function quantity_slab_service($proid){

    $row_qty_slab_pro_id = DB::table('tbl_services')->select('qty_slab')->where('service_id', '=', $proid)->where('deleteflag', '=', 'active')->first();
    $quantity_slab	  = isset($row_qty_slab_pro_id->qty_slab) ? $row_qty_slab_pro_id->qty_slab : '';
	return $quantity_slab;
}

function quantity_slab_max_discount_service($proid,$pro_quantity){

    $sql_max_dis_data_qty_wise = DB::table('tbl_service_qty_max_discount_percentage')->select('max_discount_percent')->where('min_qty', '<=', $pro_quantity)->where('max_qty', '>=', $pro_quantity)->where('serviceid', '=', $proid)->first();   
	$max_dis_last_qty_wise = isset($sql_max_dis_data_qty_wise->max_discount_percent) ? $sql_max_dis_data_qty_wise->max_discount_percent : '';	
	return $max_dis_last_qty_wise;
}

function get_alphanumeric_id_enq_lead_id($id){
	
    if($id!='' && $id!='0'){
        $row = DB::table('tbl_order')->select('orders_id','offercode')->where('lead_id', '=', $id)->where('deleteflag', '=', 'active')->first();
        $offercode	  = isset($row->offercode) ? $row->offercode.'-'.$row->orders_id : 'N/A';
    }else{
        $offercode	  = "N/A";		
	}
    return $offercode;
}

function get_offer_date_enq_lead_id($id){   

    if($id!='' && $id!='0'){
        $row = DB::table('tbl_order')->select('date_ordered')->where('lead_id', '=', $id)->where('deleteflag', '=', 'active')->first();        
        $date_ordered	  = isset($row->date_ordered) ? $row->date_ordered : "N/A";
    }else{
        $date_ordered	  = "N/A";		
	}
    return $date_ordered;
}

function account_manager_phone_gg($ID){

    if($ID!='' && $ID!='0'){
        $row = DB::table('tbl_admin')->select('admin_telephone')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
        $admin_telephone	  = isset($row->admin_telephone) ? $row->admin_telephone : "N/A"; 
    }else{
        $admin_telephone	  = "N/A";		
	}
    return $admin_telephone;
}


function account_manager_phone($ID){

    if($ID!='' && $ID!='0'){
        $row = DB::table('tbl_admin')->select('admin_telephone')->where('id', '=', $ID)->where('admin_status', '=', 'active')->first(); 
        $admin_telephone	  = isset($row->admin_telephone) ? $row->admin_telephone : "N/A"; 
    }else{
        $admin_telephone	  = "N/A";		
	}
    return $admin_telephone;
}



function get_eid_add_date($id){

    $row = DB::table('tbl_web_enq')->select('Enq_Date')->where('id', '=', $id)->where('deleteflag', '=', 'active')->first();     
    $ID	  = $row->Enq_Date;
    return $ID;
}



function getLogsInquiryData($id){

    $row = DB::table('tbl_web_enq_edit')->where('ID', '=', $id)->where('deleteflag', '=', 'active')->first();  
    return $row;
}

function getLogsScheduleData($offer_id, $evttxt){

    $row = DB::table('events')->where('offer_id', '=', $offer_id)->where('evttxt', '=', $evttxt)->where('deleteflag', '=', 'active')->get();  
    return $row;
}

function getTaskTypeRes($tasktype){

    $sql = "SELECT tasktype_id, tasktype_name, task_icon, tasktype_abbrv, tasktype_description FROM `tbl_tasktype_master` WHERE tasktype_abbrv = '".$tasktype."'  and deleteflag = 'active' and tasktype_status='active'";
    $row = DB::select(DB::raw($sql)); 
    return $row[0];
}

function dateSub($days){

    $year  = date('Y');  
    $month = date('m');
    $date  = date('d');
    $time = date('Y-m-d', @mktime(0, 0, 0, @$month, $date-$days, $year));
    return $time;
}

function get_invoice_date_by_order_id($id){  
   
    $row = DB::table('tble_invoice')->select('I_date')->where('o_id', '=', $id)->first();    
    $I_date	  = isset($row->I_date) ? date("Y-m-d", strtotime($row->I_date)) : '';
    return $I_date;
}

function get_invoice_id_by_order_id($orderid){  
    
    $row = DB::table('tbl_tax_invoice')->select('invoice_id')->where('o_id', '=', $orderid)->first();      
    $invoice_id	  = isset($row->invoice_id) ? $row->invoice_id : '';
    return $invoice_id;
}

function no_of_products($ID){ 

    $sql = "select count(pro_name) as no_of_products from tbl_order_product where order_id = $ID";    
    $row = DB::select(DB::raw($sql)); 

    $no_of_products	 = isset($row->no_of_products) ? $row->no_of_products : 0;
    return $no_of_products;
}

function product_name_generated_do_manager($ID){   

    $sql = "select pro_name from tbl_order_product where order_id = $ID";
    $rs = DB::select(DB::raw($sql)); 

    $i=0;
    echo "<p id='myDIV$ID' class='twolinetext'>";
    foreach($rs as $row) {
        $i++;
        if($row->pro_name!='' && $row->pro_name!='0' ){
            echo "<b>(".$i.")</b> ".$pro_name = $row->pro_name."<br/>";
        }
    }
    echo "</p>";
}

function serial_no_generated($ID){   

        $sql = "select barcode from tbl_order_product where order_id = $ID";        
        $rs = DB::select(DB::raw($sql));
        $b=0;
        foreach($rs as $row) {
            $b++;
            if($row->barcode!='' && $row->barcode!='0' ){
                echo "<b>(".$b.")</b> ".$barcode = $row->barcode."<br/>";
            }	
        }

}

function delivery_offer_warranty($pcode){
    
    $sql_warranty = "select delivery_offer_warranty from tbl_delivery_order where  O_Id = '$pcode' ";        
    $rs_warranty = DB::select(DB::raw($sql_warranty));
    $rs_warranty = $rs_warranty[0];
    
	if(!empty($rs_warranty))
	{
		$delivery_offer_warranty		= $rs_warranty->delivery_offer_warranty;
	//$Consignee		= $row_warranty->Consignee;

	}
return $delivery_offer_warranty;	
}

function customer_pro_desc_title($model_no,$order_id){

    $row = DB::table('tbl_do_products')->select('Description')->where('ItemCode', '=', $model_no)->where('OID', '=', $order_id)->first();     
    $customer_pro_desc_title	  = isset($row->Description) ? $row->Description : '';
    return $customer_pro_desc_title;
}

function product_part($id){   

    $row = DB::table('tbl_products_entry')->select('model_no')->where('pro_id', '=', $id)->first(); 
    $pro_title	  = isset($row->model_no) ? $row->model_no : '';
    return $pro_title;
}

function get_certificate_id($pcode,$pro_id){

        $sql_certificate 	= "select certificate_id from tbl_warranty_certificate_generated where  o_id = '$pcode' and pro_id like '%$pro_id%' ";
        $row = DB::select(DB::raw($sql_certificate));
        $certificate_id= '';
        if(!empty($row)){
            $certificate_id		= isset($row->certificate_id) ? $row->certificate_id : '';	
        }
        return $certificate_id;	        
}

function case_duration_as_per_segment($cust_segment){

    $sql = "SELECT ROUND(AVG(DATEDIFF(tti.invoice_generated_date,o.date_ordered))) as days, 
    l.id, o.customers_id, 
    l.cust_segment
    from tbl_invoice_products tip 
    INNER JOIN tbl_tax_invoice tti ON tip.tax_invoice_id=tti.invoice_id 
    INNER JOIN tbl_order o ON tti.o_id=o.orders_id 
    INNER JOIN tbl_lead l ON l.id=o.lead_id 
    where l.cust_segment='$cust_segment'
    GROUP by l.cust_segment";   
    $row = DB::select(DB::raw($sql));

    $case_duration_days_in_this_segment = isset($row->days) ? $row->days : '';
    return $case_duration_days_in_this_segment;
}


function case_duration_of_this_customer($customer_id){

    $sql = "SELECT ROUND(AVG(DATEDIFF(tti.invoice_generated_date,o.date_ordered))) as days, 
    l.id, o.customers_id, 
    l.cust_segment
    from tbl_invoice_products tip 
    INNER JOIN tbl_tax_invoice tti ON tip.tax_invoice_id=tti.invoice_id 
    INNER JOIN tbl_order o ON tti.o_id=o.orders_id 
    INNER JOIN tbl_lead l ON l.id=o.lead_id 
    where o.customers_id='$customer_id'
    GROUP by o.customers_id";
    $row = DB::select(DB::raw($sql));    

    if(!empty($row)){
        $case_duration_days_in_this_segment = isset($row->days) ? $row->days : '';
    }else{
        $case_duration_days_in_this_segment = "15";
    }
    return $case_duration_days_in_this_segment;
}

function task_type_icon($STvalue){

    $row = DB::table('tbl_tasktype_master')->select('task_icon')->where('tasktype_abbrv', '=', $STvalue)->where('deleteflag', '=', 'active')->first();     
    $task_icon	  = isset($row->task_icon) ? $row->task_icon : '';
    return $task_icon;
}

function Get_CUSDUEDATE($ID) {
    
    $row = DB::table('tbl_delivery_order')->select('PO_Due_Date')->where('O_Id', '=', $ID)->first();
    $PO_Due_Date	  = isset($row->PO_Due_Date) ? $row->PO_Due_Date : '';
    return $PO_Due_Date;
}

function buyer_mobile($orderid){

    $row = DB::table('tbl_delivery_order')->select('Buyer_Mobile')->where('O_Id', '=', $orderid)->first();    
    $Buyer_Mobile	  = isset($row->Buyer_Mobile) ? $row->Buyer_Mobile : '';
    return $Buyer_Mobile;
}

function buyer_email($orderid){

    $row = DB::table('tbl_delivery_order')->select('Buyer_Email')->where('O_Id', '=', $orderid)->first();     
    $Buyer_Email	  = isset($row->Buyer_Email) ? $row->Buyer_Email : '';
    return $Buyer_Email;
}

function consignee_mobile($orderid){

    $row = DB::table('tbl_delivery_order')->select('Con_Mobile')->where('O_Id', '=', $orderid)->first();      
    $Con_Mobile	  = isset($row->Con_Mobile) ? $row->Con_Mobile : '';
    if($Con_Mobile=='')
    {
        $Con_Mobile="N/a";
    }
    return $Con_Mobile;
}

function customer_notification($orderid){

    $row = DB::table('tbl_delivery_challan_comment')->select('customer_notification')->where('order_id', '=', $orderid)->first();     
    $customer_notification = isset($row->customer_notification) ? $row->customer_notification : '';
    return  $customer_notification;
}

function get_offercode($id){   

    $row = DB::table('tbl_order')->select('offercode')->where('orders_id', '=', $id)->where('deleteflag', '=', 'active')->first();      
    $offercode	  = isset($row->offercode) ? $row->offercode : '';
    return $offercode;
}

function buyer_address_challan($orderid){

    $row = DB::table('tbl_delivery_order')->where('O_Id', '=', $orderid)->first();  
   
    if(!empty($row)){   
        $Buyer= $row->Cus_Com_Name.' 
        '.$row->Buyer.'
        '.StateName($row->buyer_state).'
        '.CityName($row->buyer_city).'
        '.CountryName($row->buyer_country).'
        '.$row->buyer_pincode.'
        Name: '.$row->Buyer_Name.'
        Mobile: '.$row->Buyer_Mobile.'
        Email: '.$row->Buyer_Email.'
        GST No: '.$row->Buyer_CST;
        return  ($Buyer);
    }else{
        return "";
    }
}

function consignee_address_challan($orderid){

    $row = DB::table('tbl_delivery_order')->where('O_Id', '=', $orderid)->first(); 
    
    if(!empty($row)){ 
        $consignee = $row->Con_Com_Name.' 
        '.$row->Consignee.'
        '.StateName($row->con_state).'
        '.CityName($row->con_city).'
        '.CountryName($row->con_country).'
        '.$row->con_pincode.'
        Name: '.$row->Con_Name.'
        Mobile: '.$row->Con_Mobile.'
        Email: '.$row->Con_Email.'
        GST No: '.$row->Con_CST;
        return  ($consignee);
    }else{
        return "";
    }
}

function couriercoName($STvalue){

    if(is_numeric($STvalue)){

        $row = DB::table('tbl_courier_master')->select('courier_name')->where('courier_id', '=', $STvalue)->where('deleteflag', '=', 'active')->first(); 
        $courier	  = isset($row->courier_name) ? $row->courier_name : '';
    }else{
        $courier = $STvalue;
    }
    return ucfirst($courier);
}

function Invoice_items_info($pcode){

$symbol		= currencySymbol(1);
$currency1 	= $symbol[0];
$curValue 	= $symbol[1];
$totalCost  = 0;
echo "<div class='row'> 
		<div class='col-md-12'>
<div class='table-responsive'>
<table class='table tax-table table-bordered'>";
//echo "<tr class='pagehead'><td colspan='11' class='pad'>Item(s) Information </td></tr>";
echo "<thead><tr >";
echo "<td >SR.No.</td>";
//echo "<td width='15%' '>Item Code</td>";
echo "<td>Product Description </td>";
//echo "<td width='15%' >Product Serial No. </td>";
echo "<td>Qty.</td>";
echo "<td>UOM</td>";
echo "<td class='tblBorder_invoice_bottom'>Remarks</td>
</tr>	</thead>
	<tbody>";
//		echo "<tr><td height='1px' colspan='11' class='tblBorder_invoice_bottom'>hf</td></tr>"; //blank line

$sqlOrderPro = "select * from tbl_order_product where order_id ='$pcode' order by order_pros_id desc";
$rsOrderPro = DB::select(DB::raw($sqlOrderPro)); 
$h=0;
foreach($rsOrderPro as $rowOrderPro){

$h++;
echo "<tr >";
echo "<td>".$h."</td>";
//echo "<td valign='top' >";
$OrderProID	 = $rowOrderPro->order_pros_id;
$proID	 	 = $rowOrderPro->pro_id;
$groupID 	 = $rowOrderPro->group_id;
 
echo "<td valign='top' >".customer_pro_desc_title($rowOrderPro->pro_model,$pcode).'<br>';
echo '<b>Item Type:</b>'.product_type_class_name(product_type_class_id($proID)).'<br>
<b>Item Code:</b>'.pro_text_ordered($proID,$pcode).'<br>
<b>Product S. No.: </b>'.$rowOrderPro->barcode;
"</td>";
//echo "<td valign='top'>".$rowOrderPro->barcode." </td>";
echo "<td valign='top'> $rowOrderPro->pro_quantity </td>";
echo "<td valign='top'> No.</td>";
echo "<td valign='top'> No Remark</td>";
echo "</tr>";
echo "<tr><td></td><td colspan='4'>";
$bom_items = product_BOM_items($proID);
array_pop($bom_items);// removes last element of an array - because last array element is empty - added by Rumit on 23-Jun-2021
$bom_ctr=count($bom_items);
if($bom_ctr>0){
echo "<table class='table tax-table table-bordered'>";
/*echo "<tr>
<td ><strong >Item Type</strong></td>
<td ><strong>UPC </strong></td>
<td ><strong>Item Name</strong></td>
<td ><strong>Qty</strong></td>
<td ><strong>UOM</strong></td>
<td ><strong>Remarks</strong></td>";

echo "</tr>";*/
for($i=0; $i< $bom_ctr; $i++ ){
echo"<tr>";
/*if($this->product_type_class_id($bom_items[$i]->pro_id)>0)
{*/
echo"<td class='ws-25' >";
echo product_type_class_name(product_type_class_id($bom_items[$i]->pro_id))."</td>";	
echo "<td class='ws-25'>".$bom_pro_id=pro_upc_code($bom_items[$i]->pro_id)."</td>";
echo "<td >".$bom_pro_id=product_name($bom_items[$i]->pro_id)."</td>";
echo "<td >".$bom_pro_id=$bom_items[$i]->tbl_qty."</td>";
echo "<td >No</td>";
echo "<td >No Remark</td>";
//}
echo "</tr>";
}
echo "</table>";
}
echo "</td></tr>";
//			echo "<tr bgcolor='#F6F6F6'><td colspan='11' height='2'></td></tr>";
}
echo "</tbody></table></div>
		</div>
	</div>";
}

function product_type_class_name($product_type_class_id){

    $sqlState = "select product_type_class_name from tbl_product_type_class_master where product_type_class_id = '$product_type_class_id' and deleteflag = 'active'";
    $rowState = DB::select(DB::raw($sqlState));
   
    $rowState = isset($rowState[0]) ? $rowState[0] : '';
    $product_type_class_name	  = isset($rowState->product_type_class_name) ? $rowState->product_type_class_name : '';
    return $product_type_class_name;
}

function product_type_class_id($id){   

    $sql = "select product_type_class_id from tbl_products where pro_id = '$id' ";
    $row = DB::select(DB::raw($sql));
    $row = $row[0];      
    $product_type_class_id	  = isset($row->product_type_class_id) ? $row->product_type_class_id : '';
    return $product_type_class_id;
}

function pro_text_ordered($IdValue, $Order_id){

    $sqlCountry = "select pro_text,pro_model from tbl_order_product where pro_id = '$IdValue' and order_id='$Order_id' and deleteflag = 'active' order by order_pros_id desc";
    $rowCountry = DB::select(DB::raw($sqlCountry));
    $rowCountry = isset($rowCountry[0]) ? $rowCountry[0] : ''; 
    $pro_model = isset($rowCountry->pro_model) ? $rowCountry->pro_model : '';

    $pro_text	= " (".$pro_model.") ";
    if(trim(strlen($pro_text))>0){
        return $pro_text;
    }else{
        echo "";
    }
}

function product_BOM_items($pro_id){

$sql	= "select pro_id,p_pro_id,tbl_qty from tbl_group_products_entry where p_pro_id='$pro_id' order by id DESC ";
$rows_sub = DB::select(DB::raw($sql));

/*
			while($rows_sub[] = mysqli_fetch_object($result));
			{            
//			$Prodcut_Price=$rows_sub->Prodcut_Price;
			}
	//	}
    */
		return $rows_sub;

}

function consignee_contact_person($orderid){

    $row = DB::table('tbl_delivery_order')->select('Con_Name')->where('O_Id', '=', $orderid)->first(); 
    $Con_Name	  = isset($row->Con_Name) ? $row->Con_Name : '';
    if($Con_Name==''){
        $Con_Name="N/a";
    }
    return $Con_Name;
}

function buyer_contact_person($orderid){

    $row = DB::table('tbl_delivery_order')->select('Buyer_Name')->where('O_Id', '=', $orderid)->first();
    $buyer_contact_person	  = $row->Buyer_Name;
    if($buyer_contact_person==''){
        $buyer_contact_person="N/a";
    }
    return $buyer_contact_person;
}

///////////////////////////////////  Start warranty certificate //////////////////////////////////////////////

function OrderItemsInfo_warranty_certificate($pcode,$proidgc=0,$cert_id=0){

    $symbol		= currencySymbol(1);
    $currency1 	= $symbol[0];
    $curValue 	= $symbol[1];
    $totalCost  = 0;
    echo "<table width='100%' border='0' cellpadding='5' cellspacing='0' class='table-bordered'  >";
    echo "<tr>";
    echo "<th width='15%' align='center' style='text-align:center' >SR.No.</th>";
    echo "<th width='55%'>Product Description </th>";
    echo "<th width='15%'>Qty.</th>";
    echo "<th width='15%'>UOM</th>";
    echo "</tr>";
    //		echo "<tr><td height='1px' colspan='11' class='tblBorder_invoice_bottom'>hf</td></tr>"; //blank line
    //$sqlOrderPro = "select * from tbl_order_product where order_id ='$pcode' order by order_pros_id desc";

    if($proidgc!='0' && $proidgc!='')
    {
        //$proidgc1=explode(",",$proidgc);
        //$proidgc2=implode(",",$proidgc1);
        
        $search=" and g.pro_id IN ($proidgc) "; 
    }

    if($cert_id!='0' && $cert_id!='')
    {
        //$proidgc1=explode(",",$proidgc);
        //$proidgc2=implode(",",$proidgc1);
        
        $search_cert=" and g.cert_gen_id = '$cert_id' "; 
    }
    $sqlOrderPro = "SELECT o.order_id,o.order_pros_id,o.pro_id,o.pro_model,o.pro_name,o.pro_quantity,o.barcode,g.certificate_id,g.i_id,g.o_id,g.pro_id,g.generated_date,g.status FROM tbl_order_product o INNER JOIN tbl_warranty_certificate_generated_items g 
    ON o.order_id=g.o_id 
    and o.pro_id=g.pro_id 
    and o.order_id = '$pcode'     
    order by o.order_pros_id desc";
    
    $rsOrderPro = DB::select(DB::raw($sqlOrderPro));
    $h=0;
    foreach($rsOrderPro as $rowOrderPro){

        $h++;
        echo "<tr class='text'>";
        echo "<td align='center' >".$h."</td>";
        //echo "<td valign='top' >";
        $OrderProID	 = $rowOrderPro->order_pros_id;
        $proID	 	 = $rowOrderPro->pro_id;

        echo "<td valign='top'  style='word-break:break-word'>".customer_pro_desc_title($rowOrderPro->pro_model,$pcode).'<br>
        <b>Item Code</b>: '.pro_text_ordered($proID,$pcode).'<br><b>Product S. No.</b>: '.$rowOrderPro->barcode." </td>";
        echo "<td valign='top'> $rowOrderPro->pro_quantity </td>";
        echo "<td valign='top'> No.</td>";
        echo "</tr>";
    }
    echo "</table>";
}

function consignee_address_by_do($orderid){

    $row = DB::table('tbl_delivery_order')->select('Consignee')->where('O_Id', '=', $orderid)->first();
    $Consignee	  = isset($row->Consignee) ? $row->Consignee : '';
    return $Consignee;
}

/////////////////////////////////////////// End warranty certificate ////////////////////////////////////////

function get_gst_sale_type_tax_name($id){

    $row = DB::table('tbl_gst_sale_type_master')->select('gst_sale_type_name')->where('gst_sale_type_id', '=', $id)->first();
    $gst_sale_type_name	  = isset($row->gst_sale_type_name) ? $row->gst_sale_type_name : '';
    return $gst_sale_type_name;
}

function get_IRN_no($orderid){   

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('irn')->where('o_id', '=', $orderid)->orderby('gst_irn_response_id','DESC')->first();
    $irn = isset($rowadmin_accs->irn) ? $rowadmin_accs->irn : '';	
    return $irn;
}

function get_IRN_ack_no($orderid){  

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('ackno')->where('o_id', '=', $orderid)->orderby('gst_irn_response_id','DESC')->first();   
    $ackno = isset($rowadmin_accs->ackno) ? $rowadmin_accs->ackno : '';	
    return $ackno;
}

function get_IRN_ack_date($orderid){  

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('ackdt')->where('o_id', '=', $orderid)->orderby('gst_irn_response_id','DESC')->first(); 
    $ackdt = isset($rowadmin_accs->ackdt) ? $rowadmin_accs->ackdt : '';	
    return $ackdt;
}

function get_IRN_qrcodeurl($orderid){  

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('qrcodeurl')->where('o_id', '=', $orderid)->orderby('gst_irn_response_id','DESC')->first();    
    $qrcodeurl = isset($rowadmin_accs->qrcodeurl) ? $rowadmin_accs->qrcodeurl : '';	
    return $qrcodeurl;
}

function OrderItemsInfo_invoice1_view_tax_invoice_pro_by_invoice($invoice_id,$pcode){

$invoice_ids = $invoice_id;
$invoice_currency_name		= currency_name(get_invoice_currency_by_order_id($pcode));
$invoice_currency		= get_invoice_currency_by_order_id($pcode);

$sql_dis_row_currency = DB::table('tbl_order')->select('Price_value','offer_type')->where('orders_id', '=', $pcode)->first();  
$offer_type = isset($sql_dis_row_currency->offer_type) ? $sql_dis_row_currency->offer_type : ''; //product/service
$Offer_Currency = isset($sql_dis_row_currency->Price_value) ? $sql_dis_row_currency->Price_value : '';
$Offer_Currency = str_replace("backoffice","crm",$Offer_Currency);
$symbol		= currencySymbol($invoice_currency);
//print_r($symbol);
$currency1 	= @$symbol[0];
$curValue 	= @$symbol[1];

$Offer_Currency = @$symbol[0];
$totalCost  = 0;
echo "<table width='100%' cellpadding='5' cellspacing='0'>";

echo "<thead><tr >";
echo "<th style='border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;'>S.No</td>";
echo "<th class='ws-25 text-center' align='center' style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;'>Description of Goods</th>";
echo "<th class='text-center' style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;'>HSN/SAC.</th>";

echo"<th style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;'>Quantity</th>";
echo"<th class='text-center' style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;'>Rate</th>";
echo"<th class='text-center' style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;'>Per</th>";

$sql_dis_row = DB::table('prowise_discount')->where('orderid', '=', $pcode)->select('show_discount')->first(); 
$show_discount = isset($sql_dis_row->show_discount) ? $sql_dis_row->show_discount : '';
if($show_discount=="Yes") {
    echo "<th style='display:none' >Discount(-)</th>";
    $dis_td=1;
}
else
{
}
echo "<th style='display:none'>Add IGST Value(%)</th>";

echo "<th class='text-end' style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;'>Sub Total</th></tr>
	</thead>
	<tbody>";

if($invoice_ids=='' || $invoice_ids=='0'){
    $sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc ";
}else{
    $sqlOrderPro = "select * from tbl_invoice_products where tax_invoice_id ='$invoice_ids' order by tax_pro_id asc ";
}

$rsOrderPro = DB::select(DB::raw($sqlOrderPro));

$h=0;
foreach($rsOrderPro as $rowOrderPro){

$h++;

if($invoice_ids=='' || $invoice_ids=='0')
{
$ItemCode	 = $rowOrderPro->ItemCode;
$pro_ordered_qty = $rowOrderPro->Quantity;
$Price		= $rowOrderPro->Price;
$pro_description		= $rowOrderPro->Description;
$per_item_tax_rate		= isset($rowOrderPro->per_item_tax_rate) ? $rowOrderPro->per_item_tax_rate : '';	
}
else
{
	$ItemCode	 = $rowOrderPro->model_no;
	$pro_ordered_qty = $rowOrderPro->quantity;
	$Price=$rowOrderPro->price;
	$pro_description		= $rowOrderPro->pro_description;
	$per_item_tax_rate		= $rowOrderPro->per_item_tax_rate;	
}

$proID	 	 = pro_id_by_itemcode($ItemCode,$pcode);
$groupID 	 = isset($rowOrderPro->group_id) ? $rowOrderPro->group_id : '';

$pro_ware_house_stock = product_stock($proID);


if($pro_ware_house_stock >= $pro_ordered_qty)
{
	$stock_ava= "<i class='bi bi-check-lg check-co'></i>";
}
else
{
	$stock_ava= "<i class='text-danger bi bi-x'></i> ";
}

"<br>SLAB: ".$qty_slab = quantity_slab($proID);
"<br>Max dixxxx".$max_dis_last = product_discount($proID);
"<br>QTY wise:ss".$max_dis_last_qty_wise = quantity_slab_max_discount($proID,$pro_ordered_qty);

if($qty_slab=='Yes')
{

if($max_dis_last_qty_wise == '0' && $max_dis_last_qty_wise=='')
{
		$pro_max_discount_allowed = $max_dis_last;
}
else
{
		$pro_max_discount_allowed = $max_dis_last_qty_wise;
}
}
else if($qty_slab=='No')
{
	$pro_max_discount_allowed = $max_dis_last;
}
else
{
		$pro_max_discount_allowed = "0";
}

echo "<tr >";
if($offer_type=='service')
{

	$rental_period = get_rental_period($invoice_id);
	$rental_start_date = $rental_period[0];
	$rental_end_date = $rental_period[1];
	
	if($rental_start_date=='' || $rental_start_date=='0000-00-00')
	{
        $rental_start_date = PO_date_delivery_order($pcode);
        $date = date_create(PO_date_delivery_order($pcode));
        date_add($date,date_interval_create_from_date_string("31 Days"));
        $rental_end_date = date_format($date,"Y-m-d");	

	$rental_period="";
	}
	else
	{
	$rental_period="";	
	}
}
?>
<?php
$product_serial_nos = serial_no_generated_by_pro_id_and_order_id($pcode,$proID);
if($product_serial_nos!='')
{
		$product_serial_nos = "<br/><b>Product S. No.: </b>".$product_serial_nos;
}

else
{
	$product_serial_nos=" ";
}
$rental_period = isset($rental_period) ? $rental_period : "";

echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;'>".$h."</td>";
echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;word-break: break-all;' valign='top'><b>".stripcslashes(str_replace("\\", "", $pro_description)).'</b><br/>';

echo '<b>Item Code:</b>'.pro_text_ordered($proID,$pcode).$product_serial_nos.
	'<br>'.$rental_period;
"</td>";

if($offer_type=='service')
{
$hsn_code=ServiceHsn_code($proID);
}
else{
$hsn_code=ProHsn_code($proID);
}

if($hsn_code=='')
{
$hsn_code="N/A";
}

echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' valign='top'> $hsn_code </td>";

echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' valign='top'>";
echo '<input type="hidden" name="pro_model[]" value="'. $ItemCode.'"  class="form-control"><input type="hidden" name="pro_qty[]" value="'. $pro_ordered_qty.'" size="2" class="form-control">' . $pro_ordered_qty. '</td>';

$orPrice = $Price;
$dis_td = isset($dis_td) ? $dis_td : "";
$per_product_price = isset($per_product_price) ? $per_product_price : "";

$sql_dis_row = DB::table('prowise_discount')->where('orderid', '=', $pcode)->where('proid', '=', $proID)->first(); 
$discount_percent =  isset($sql_dis_row->discount_percent) ? $sql_dis_row->discount_percent : "";
$discount_amount =  isset($sql_dis_row->discount_amount) ? $sql_dis_row->discount_amount : 0;

if($show_discount=="Yes" || $dis_td) {
echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'>";
echo $Offer_Currency." "; 
echo number_format($discount_amount);
echo "<br />";
$discounted_price = $orPrice * (float)$discount_percent / 100;			

echo '<input type="hidden" name="pro_price1[]" value="'. $per_product_price.'" >';
echo '<input type="hidden" name="pro_id[]" value="'. $proID.'" >';
echo '<input type="hidden" name="discount_percent[]" value="'. $discount_percent.'" size="4" class="form-control"><br>'.$discount_percent.'%';
echo "</td>";
}
else
{
	$discounted_price = $orPrice * (float)$discount_percent / 100;	
echo '<input type="hidden" name="pro_price2[]" value="'. $per_product_price.'" >';
echo '<input type="hidden" name="pro_id[]" value="'. $proID.'" >';
}

echo "<td valign='top' style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' align='center'> ";
echo $Offer_Currency." ";

echo $per_product_price=$orPrice;

echo "</td>";
echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' valign='top' align='center'>No's</td>";
echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'> ";
echo $Offer_Currency." ";
			
echo "".$discounted_price_tax_amt=($orPrice) * (float)$per_item_tax_rate;		
echo "<br>(".$per_product_GST_percentage= $per_item_tax_rate;
echo ")</td>";

$SumProPrice 	= ($orPrice+$discounted_price_tax_amt)* $pro_ordered_qty;
$totalTax 		= $discounted_price_tax_amt * $pro_ordered_qty;

$ProTotalPrice = ($orPrice)* $pro_ordered_qty;
$totalCost     = $totalCost + $ProTotalPrice ;
echo "<td style='border-bottom:1px solid #0f0f0f; ' valign='top' align='right'>";
echo $Offer_Currency." "; 
echo number_format($ProTotalPrice,2);
echo "</td>";

echo "</tr>";
		
}

echo "<tbody></table>";
}

function currency_name($currency_id){   

    $rowApplication = DB::table('tbl_currencies')->select('currency_title')->where('currency_id', '=', $currency_id)->where('deleteflag', '=', 'active')->first();
    $currency_title	  = isset($rowApplication->currency_title) ? $rowApplication->currency_title : '';
    return $currency_title;
}

function get_invoice_currency_by_order_id($orderid){  

    $rowApplication = DB::table('tbl_tax_invoice')->select('invoice_currency')->where('o_id', '=', $orderid)->first();    
    $invoice_currency	  = isset($rowApplication->invoice_currency) ? $rowApplication->invoice_currency : '';
    return $invoice_currency;
}

function pro_id_by_itemcode($model_no,$order_id){

    $row = DB::table('tbl_order_product')->select('pro_id')->where('pro_model', '=', $model_no)->where('order_id', '=', $order_id)->first();     
    $pro_id	  = isset($row->pro_id) ? $row->pro_id : '';
    return $pro_id;
}

function serial_no_generated_by_pro_id_and_order_id($ID,$pro_id){   

    $sql = "select barcode from tbl_order_product where order_id = '$ID' and pro_id='$pro_id'";
    $rs = DB::select(DB::raw($sql));
    
    //$comp_name	  = $row->comp_name;
    $b=0;
    $barcode = "";
    foreach($rs as $row) {
        $b++;
            //	if($row->barcode!='' && $row->barcode!='0' )
        if($row->barcode!='' && $row->barcode!='0' &&  $row->barcode!='ACL SKIP')//added on 05-june-2023 to remove serial no line in INvoice
        {
            //echo "<br>".$barcode = $row->barcode;
            $barcode = isset($row->barcode) ? $row->barcode.", " : "";
        }	
    }
    return $barcode;
}

function ProHsn_code($IdValue){  

    $rowApplication = DB::table('tbl_products_entry')->select('hsn_code')->where('pro_id', '=', $IdValue)->where('deleteflag', '=', 'active')->first();  
    $hsn_code	  = isset($rowApplication->hsn_code) ? $rowApplication->hsn_code : "";
    return $hsn_code;
}

function tax_invoice_total_view_pro_by_invoice($invoice_id,$pcode,$buyerstatecode=0,$sellerstatecode=0){ 

    $invoice_ids=$invoice_id;
    $invoice_currency		= currency_name(get_invoice_currency_by_order_id($pcode));
    $invoice_currency_name		= currency_name(get_invoice_currency_by_order_id($pcode));
    $invoice_currency		= get_invoice_currency_by_order_id($pcode);
    $symbol					= currencySymbol($invoice_currency);
    $currency1 				= @$symbol[0];
    $curValue 				= @$symbol[1];

    $Offer_Currency = @$symbol[0];//str_replace("backoffice","crm",$Offer_Currency);



    $GST_sale_type			= get_gst_sale_type($pcode);
    $GST_sale_type_tax		= get_gst_sale_type_tax($GST_sale_type);

    $rowOrderTotal = DB::table('tbl_order')->where('orders_id', '=', $pcode)->first(); 

    $h=0;
    $subtotal1=0;
    $totalTax = 0;
    $totalqty = 0;
    $totalCost = 0;
    $show_discount = '';
    $freight_amount = 0;
    $freight_gst_amount = 0;
    $tax_included = '';
    $GST_tax_amt = 0;

    if(!empty($rowOrderTotal))
    {
    //$sqlOrderPro 			= "select * from tbl_do_products where OID ='$pcode' order by ID asc";

    if($invoice_ids=='' || $invoice_ids=='0')
    {
    $sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc ";
    }
    else
    {
    $sqlOrderPro = "select * from tbl_invoice_products where tax_invoice_id ='$invoice_ids' order by tax_pro_id asc ";
    }

    $rsOrderPro = DB::select(DB::raw($sqlOrderPro));

    

    foreach($rsOrderPro as $rowOrderPro)
    {
    $h++;

    if($invoice_ids=='' || $invoice_ids=='0')
    {
    $ItemCode	 = $rowOrderPro->ItemCode;
    $pro_ordered_qty=$rowOrderPro->Quantity;
    $Price		=$rowOrderPro->Price;
    $pro_description		=$rowOrderPro->Description;
    $per_item_tax_rate		= isset($rowOrderPro->per_item_tax_rate) ? $rowOrderPro->per_item_tax_rate : "";	
    }
    else
    {
        $ItemCode	 		= $rowOrderPro->model_no;
        $pro_ordered_qty	=$rowOrderPro->quantity;
        $Price				=$rowOrderPro->price;
        $pro_description	=$rowOrderPro->pro_description;
        $per_item_tax_rate	=$rowOrderPro->per_item_tax_rate;	
    }

    //echo "	<br>per_item_tax_rate".	$per_item_tax_rate;	

    //$ItemCode	 			= $rowOrderPro->ItemCode;
    $proID	 	 			= pro_id_by_itemcode($ItemCode,$pcode);//$rowOrderPro->pro_id;
    $groupID 	 			= isset($rowOrderPro->group_id) ? $rowOrderPro->group_id : "";

    $pro_ware_house_stock	= product_stock($proID);
    //$pro_ordered_qty		= $rowOrderPro->Quantity;

    //$OrderProID	 		= $rowOrderPro->order_pros_id;
    //$proID	 	 		= $rowOrderPro->pro_id;
    //$groupID 	 			= $rowOrderPro->group_id;
    $orPrice 	 			= $Price;
    //number_format($orPrice,2);
    
    $sql_dis_row = DB::table('prowise_discount')->where('orderid', '=', $pcode)->first();
    $discount_amount = isset($sql_dis_row->discount_amount) ? $sql_dis_row->discount_amount : 0;
    $discount_percent = isset($sql_dis_row->discount_percent) ? $sql_dis_row->discount_percent : 0;

    $subtotal1				= ($orPrice * $pro_ordered_qty) - $discount_amount;
    $subtotal1_dis			= $discount_amount;
    $discount_percent		= $discount_percent;
    $discounted_price		= $orPrice * $discount_percent / 100;			//discount amt per unit
    $pro_hsn_code			= ProHsn_code($proID);
    /*if($GST_sale_type=='1')
    {
    "GST tax<br>".$per_product_GST_percentage	= $this->GST_percentage_by_product($pcode,$proID);//$rowOrderPro->GST_percentage;//product gst %
    }
    else
    {*/
    
    $per_product_GST_percentage	= $per_item_tax_rate;//$GST_sale_type_tax;//$this->GST_percentage_by_product($pcode,$proID);//$rowOrderPro->GST_percentage;//product gst %
    //}
    //$discounted_price_tax_amt	=($orPrice-$discounted_price)* $per_product_GST_percentage/100;	
    

    $discounted_price_tax_amt	= (float)($orPrice * (float)$per_product_GST_percentage) / 100;			
   
    //$SumProPrice 				= ($orPrice-$discounted_price+$discounted_price_tax_amt)* $rowOrderPro->Quantity;//$proPrice * $rowOrderPro->pro_quantity;
   
    $SumProPrice 				= ($orPrice+$discounted_price_tax_amt)* $pro_ordered_qty;//$proPrice * $rowOrderPro->pro_quantity;
    $totalTax				   +=$discounted_price_tax_amt * $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;
    $totalqty				   += $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;//total no of items in order
    //echo "Freight amt: ".$this->freight_amount_by_order_id($pcode);
    //$ProTotalPrice 				= ($discounted_price_tax_amt+$orPrice-$discounted_price)* $pro_ordered_qty;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit
    $ProTotalPrice 				= ($discounted_price_tax_amt+$orPrice)* $pro_ordered_qty;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit
    $freight_amount 			= freight_amount_by_order_id($pcode);//$rowOrderPro->freight_amount;// * $rowOrderPro->pro_quantity;
    $freight_tax_rate = ((float)$GST_sale_type_tax + 100)/100;  
    $freight_amount_with_gst = $freight_amount/$freight_tax_rate;// * $rowOrderPro->pro_quantity;
    $freight_gst_amount = $freight_amount-$freight_amount_with_gst;// * $rowOrderPro->pro_quantity;
    $totalCost     				= $totalCost + $ProTotalPrice;

    }
    $GST_tax_amt				= GST_tax_amount_on_offer($pcode);//$rowOrderPro->GST_percentage;				
    $TotalOrder	   				= $rowOrderTotal->total_order_cost;
    $ship		   				= $rowOrderTotal->shipping_method_cost;
    $shippingValue				= $ship;
    $taxValue					= $rowOrderTotal->tax_cost;
    $tax_included				= $rowOrderTotal->tax_included;
    $tax_perc					= $rowOrderTotal->taxes_perc;
    $discount_perc				= $rowOrderTotal->discount_perc;
    $discount_per_amt			= $rowOrderTotal->discount_per_amt;
    $show_discount				=$rowOrderTotal->show_discount;
    $subtotal_after_discount    = $TotalOrder - $subtotal1_dis;
    if($tax_included=='Excluded')
    {
    @$subtotal_tax       		= $subtotal_after_discount * (float)$tax_perc / 100;
    }
    else
    {
    $subtotal_tax       		= $subtotal_after_discount;
    }
    if($tax_included=='Excluded')
    {
    $GrandTotalOrder			= $subtotal_after_discount + $subtotal_tax;
    }
    else
    {
    $GrandTotalOrder			= $subtotal_after_discount + 0;
    }
    //$subtotal_final		= $subtotal_tax;
    $couponDiscount 			= $rowOrderTotal->coupon_discount;
    }

    echo "<table width='100%' class='table tax-table table-bordered' >";
    /*echo "<tr class='pagehead'>";
    echo "<td colspan='2' class='pad' nowrap  align='left'>Order Total </td>";
    echo "</tr>";*/
    echo "<tr ><td  class='ws-49'></td>";
    echo "<td  class='text-end' colspan='6'><strong>Sub Total :</strong> </td>";
    echo "<td  align='right' >&nbsp; &nbsp;";
    echo $Offer_Currency;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";

    echo number_format($totalCost-$totalTax,2);
    echo "</td>";
    echo "</tr>";
    //echo $show_discount;
    if($show_discount=="Yes") {
        echo "<tr  style='display:none'>";
        echo "<td><strong>Discount :</strong></td>";
        echo "<td > &nbsp; &nbsp;";
        echo $Offer_Currency;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
        echo number_format($subtotal1_dis,2);
        echo "</td>";
        echo "</tr>";
    }

    echo "<tr ><td></td>";
    echo "<td class='text-end' colspan='6'><b>Freight: </b></td>";
    echo "<td align='right' colspan='2'> ";
    echo $Offer_Currency;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";

    $total_freight_amt_show= $freight_amount-$freight_gst_amount;
    ?>
    <!--changed dropdown from text field on 24-5-2019 as disscssed with Mr. Pankaj & sandeep-->
    <?php echo number_format($total_freight_amt_show);?>
    <?php
    echo "</td>";
    echo "</tr>";
    $Grand_subtotal1=$subtotal1;
    //echo "dddddd".$freight_gst_amount;
    $Total_GST_amount=($totalTax+$freight_gst_amount);
    //echo "totalTax===".$totalTax;
    $CGST=$Total_GST_amount/2;
    $SGST=$Total_GST_amount/2;
    if($tax_included=='Excluded' || $GST_tax_amt>0)
    {
    //	echo "</tr>";
    if($buyerstatecode==$sellerstatecode)
    {

    if($GST_tax_amt>0)
    {

    echo "<tr><td></td>";
    echo "<td class='text-end'  colspan='6'><strong>CGST :</strong></td>";
    echo "<td class='text-end'>".$Offer_Currency.' '.number_format($CGST,2)."</td>";
    echo "</tr>";
    echo "<tr><td></td>";
    echo "<td class='text-end'  colspan='6'><strong>SGST :</strong></td>";
    echo "<td class='text-end'>".$Offer_Currency.' '.number_format($SGST,2)."</td>";
    echo "</tr>";
    }

    }

    else{
    echo "<tr><td></td>";
    //echo "tatxtxtxtt-=-=-=-=".$tax_perc;
    if($GST_tax_amt>0)
    {
    echo "<td class='text-end'  colspan='6'><strong>Output tax IGST :</strong></td>";
    }
    else
    {
    echo "<td colspan='6'><strong>Add VAT/CST@ $tax_perc% :</strong></td>";
    }

    echo "<td class='text-end' > &nbsp; &nbsp; ";
    if($totalTax>0)
    {
    echo $Offer_Currency." ";
    echo number_format($totalTax+$freight_gst_amount,2);//total gst value rumit 
    }
    else
    {
    echo $Offer_Currency." ";
    //$subtotal_tax1=$totalTax;
    echo $totalTax;
    }//echo $subtotal1;
    }
    $Grand_subtotal1=$totalCost;//$subtotal1+$subtotal_tax1+$totalTax;
    echo "</td>";
    echo "</tr>";

    }
    /*	
    echo "<tr class='text'>";
    echo "<td class='pad' nowrap align='right'>Shipping &amp; Handling </td>";
    echo "<td  align='left'>: &nbsp; &nbsp; $currency1 ";
    printf(" %.2f ",$ship);
    echo "</td>";
    echo "</tr>";
    */
    echo "<tr class='text'><td></td>";
    echo "<td  class='text-end' colspan='6'><h4>Grand Total :</h4></td>";
    echo "<td class='text-end'><h4> &nbsp; &nbsp; ";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    echo $Offer_Currency;
    //	printf(" %.2f",$TotalOrder);
    echo $Grand_subtotal=number_format($totalCost+$freight_amount,2);
    echo "</h4></td>";
    echo "</tr>";
    echo "<tr><td colspan='5'>";
    $total=($totalTax+$freight_gst_amount);
    $grand_total_amount_in_words = ucwords(amountInWords($totalCost+$freight_amount));
    $tax_amount_in_words = ucwords(amountInWords($total));
    //echo"<br>".ucwords($this->getIndianCurrency($total));
    //echo"<br>".ucwords($this->convert_number_to_words($total));
    //$obj    = new toWords($total);
    //    echo"<br>". ucwords(substr($obj->words,0,-2));
    echo "</td></tr>";	
    echo '<tr class="trbgcolor"><td colspan="2"></td>
        <td class="text-end" ><b>Total</b></td>
        <td></td>
        <td><b>'.$totalqty.' No\'s</b></td>
        <td class="text-end" colspan="3"><b>'.number_format($totalCost+$freight_amount,2).'</b></td>
        </tr>';
    echo '<tr>
        <td colspan="8">Amount Chargeable (In words) <br> <b> '.currency_name($invoice_currency).' '.$grand_total_amount_in_words.'</b> 
    <span class="float-end">E. & O.E</span>
        </td>	
    </tr>';

    ?>
    <?php if($total > 0){?>
    <tr>
    <td colspan="4" align="center"><b>HSN/SAC </b></td>
    <td><b>Taxable Value4</b></td>
    <td colspan="2" class="text-center"><b>Integrated Tax</b></td>
    <td><b>Total Tax Amount</b></td>
    </tr>
    <tr>
    <td colspan="5">&nbsp;</td>
    <td align="center">Rate</td>
    <td align="center">Amount</td>
    <td>&nbsp;</td>
    </tr>
    <?php
    //echo $sqlOrderPro = "select * from tbl_order_product where order_id ='$pcode' order by hsn_code asc";

    if($invoice_ids=='' || $invoice_ids=='0')
    {
        //$sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc ";
        $sqlOrderPro = "SELECT *, sum(Quantity*Price) as sub_total_price FROM `tbl_do_products`  WHERE `OID` = '$pcode' GROUP by hsn_code";
    }
    else
    {
        $sqlOrderPro = "select *, sum(quantity*price) as sub_total_price from tbl_invoice_products where tax_invoice_id ='$invoice_ids' GROUP by hsn_code ";
    }

    $rsOrderPro = DB::select(DB::raw($sqlOrderPro));    
    $pro_ctr	 = @count($rsOrderPro);
    $h=0;
    $subtotal1=0;
    $total_taxable_value  = 0;
    $total_tax_amount = 0;
    ?>

    <?php
    /*$GST_sale_type=$this->get_gst_sale_type($pcode);
    $GST_sale_type_tax=$this->get_gst_sale_type_tax($GST_sale_type);*/
    $invoice_freight_amount			= get_freight_amount($pcode);
    $invoice_tax_freight_amount		= $invoice_freight_amount * ($GST_sale_type_tax/100);
    $total_frieght_amt				= $invoice_freight_amount+$invoice_tax_freight_amount;
    //echo "CTRRTR".$pro_ctr;
    //echo "<br>TOT qty: ".$totalqty;
    $ratio_freight_tax= round($invoice_freight_amount/$pro_ctr,2);
    foreach($rsOrderPro as $rowOrderPro)
    {
    $hsn_code				= isset($rowOrderPro->hsn_code) ? $rowOrderPro->hsn_code : '';
    $pro_id					= isset($rowOrderPro->pro_id) ? $rowOrderPro->pro_id : '';
    $item_code				= isset($rowOrderPro->pro_model) ? $rowOrderPro->pro_model : '';
    $per_item_tax_rate      = isset($rowOrderPro->per_item_tax_rate) ? $rowOrderPro->per_item_tax_rate : 0;

    $tax_per				= ($per_item_tax_rate/100);//"0.18";

    $taxable_value			= $rowOrderPro->sub_total_price+$ratio_freight_tax;//$this->price_amount_with_model_no($item_code,$pcode)+$ratio_freight_tax;
    $integrated_tax			= $taxable_value*$tax_per;
    $total_taxable_value +=$taxable_value;
    $total_tax_amount +=$integrated_tax;

    ?>
    <tr>
    <td colspan="4"> <?php echo $hsn_code;?></td>
    <td class='text-end'><?php echo number_format($taxable_value,2);?></td>
    <td><?php echo isset($rowOrderPro->per_item_tax_rate) ? $rowOrderPro->per_item_tax_rate : '';?>%</td>
    <td class='text-end'><?php echo number_format($integrated_tax,2);?></td>
    <td class='text-end'><?php echo number_format($integrated_tax,2);?></td>
    </tr>
    <?php }
    //print_r($cars);

    
    /*if($invoice_freight_amount>0){ ?>
    <!--Freight-->
    <tr>
    <td colspan="4"> 9965 <?php //echo $hsn_code;?></td>
    <td class='text-end'><?php echo number_format($invoice_freight_amount,2);?></td>
    <td><?php echo $GST_sale_type_tax;?>%</td>
    <td class='text-end'><?php echo number_format($invoice_tax_freight_amount,2);?></td>
    <td class='text-end'><?php echo number_format($invoice_tax_freight_amount,2);?></td>
    </tr>
    <?php } */?>
    <tr class="trbgcolor">
    <td colspan="4" class="text-end" ><b>Total</b></td>
    <td class='text-end'><strong><?php echo number_format($total_taxable_value,2);?></strong></td>
    <td>&nbsp;</td>
    <td class='text-end'><strong><?php echo number_format($total_tax_amount,2);//number_format($total_tax_amount+$invoice_tax_freight_amount,2);?></strong></td>
    <td class='text-end'><strong><?php echo number_format($total_tax_amount,2);?></strong></td>
    </tr>
    <tr>
        <td colspan="8">Tax Amount (In words) <br> <b> <?php echo currency_name($invoice_currency).' '.$tax_amount_in_words;?></b></td>	
    </tr>
    <?php }?>
    </table>
<?php
}

function get_gst_sale_type($order_id){

    $row = DB::table('tbl_tax_invoice')->select('gst_sale_type')->where('o_id', '=', $order_id)->first(); 
    $gst_sale_type	  = isset($row->gst_sale_type) ? $row->gst_sale_type : "";
    return $gst_sale_type;
}

function get_gst_sale_type_tax($id){

    $row = DB::table('tbl_gst_sale_type_master')->select('gst_sale_type_tax_per')->where('gst_sale_type_id', '=', $id)->first(); 
    $gst_sale_type_tax_per	  = isset($row->gst_sale_type_tax_per) ? $row->gst_sale_type_tax_per : '';
    return $gst_sale_type_tax_per;
}

function freight_amount_by_order_id($ID){

    $row = DB::table('tbl_order_product')->select('freight_amount')->where('order_id', '=', $ID)->where('deleteflag', '=', "active")->first();     
    $freight_amount		= isset($row->freight_amount) ? $row->freight_amount : "";
    return $freight_amount;
}

function amountInWords(float $number){

    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(0 => '', 1 => 'one', 2 => 'two',
        3 => 'three', 4 => 'four', 5 => 'five', 6 => 'six',
        7 => 'seven', 8 => 'eight', 9 => 'nine',
        10 => 'ten', 11 => 'eleven', 12 => 'twelve',
        13 => 'thirteen', 14 => 'fourteen', 15 => 'fifteen',
        16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty',
        40 => 'forty', 50 => 'fifty', 60 => 'sixty',
        70 => 'seventy', 80 => 'eighty', 90 => 'ninety');
    $digits = array('', 'hundred','thousand','lakh', 'crore');

    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }

    $rupees = implode('', array_reverse($str));
    $paise = '';

    if ($decimal) {
        $paise = 'and ';
        $decimal_length = strlen($decimal);

        if ($decimal_length == 2) {
            if ($decimal >= 20) {
                $dc = $decimal % 10;
                $td = $decimal - $dc;
                $ps = ($dc == 0) ? '' : '-' . $words[$dc];

                $paise .= $words[$td] . $ps;
            } else {
                $paise .= $words[$decimal];
            }
        } else {
            $paise .= $words[$decimal % 10];
        }

        $paise .= ' paise';
    }

//    return ($rupees ? $rupees . 'rupees ' : '') . $paise ;
    return ($rupees ? $rupees . '' : '') . $paise .' Only'  ;
}

function get_access_token($apiid){  

    $rowadmin_accs = DB::table('tbl_invoice_api_manager')->select('access_token')->where('api_id', '=', $apiid)->first();
    $access_token = isset($rowadmin_accs->access_token) ? $rowadmin_accs->access_token : '';	
    return $access_token;
}

function Stateid($STvalue){

    $num=is_numeric($STvalue);
    
    if($num!='1'){
        $rowState = DB::table('tbl_zones')->select('zone_id')->where('zone_name', '=', $STvalue)->where('deleteflag', '=', "active")->first();        
        $zone_id	  = isset($rowState->zone_id) ? $rowState->zone_id : '';
    }else{
        $zone_id = $STvalue;
    }
    return $zone_id;
}

function Cityid($STvalue){
    
    $num=is_numeric($STvalue);
    if($num!='1'){
        $rowcity = DB::table('all_cities')->select('city_id')->where('city_name', '=', $STvalue)->where('deleteflag', '=', "active")->first();        
        $city_id	  = isset($rowcity->city_id) ? $rowcity->city_id : '';
    }else{
        $city_id = $STvalue;
    }
    return $city_id;
}

function get_IRN_no_by_invoice_id($invoiceid){   

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('irn')->where('invoice_id', '=', $invoiceid)->orderby('gst_irn_response_id','DESC')->first(); 
    $irn = isset($rowadmin_accs->irn) ? $rowadmin_accs->irn : '';	
    return $irn;
}

function get_IRN_ack_no_by_invoice_id($invoiceid){   

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('ackno')->where('invoice_id', '=', $invoiceid)->orderby('gst_irn_response_id','DESC')->first(); 
    $ackno = isset($rowadmin_accs->ackno) ? $rowadmin_accs->ackno : '';	
    return $ackno;
}

function get_IRN_ack_date_by_invoice_id($invoiceid){   

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('ackdt')->where('invoice_id', '=', $invoiceid)->orderby('gst_irn_response_id','DESC')->first();
    $ackdt = isset($rowadmin_accs->ackdt) ? $rowadmin_accs->ackdt : '';	
    return $ackdt;
}

function get_IRN_qrcodeurl_by_invoice_id($invoiceid){   

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('qrcodeurl')->where('invoice_id', '=', $invoiceid)->orderby('gst_irn_response_id','DESC')->first();
    $qrcodeurl = isset($rowadmin_accs->qrcodeurl) ? $rowadmin_accs->qrcodeurl : '';	
    return $qrcodeurl;
}

function get_rental_period($invoice_id){

    $row = DB::table('tbl_tax_invoice')->select('rental_start_date','rental_end_date')->where('invoice_id', '=', $invoice_id)->first();
    $rental_period[0]	  = isset($row->rental_start_date) ? $row->rental_start_date : '';
    $rental_period[1]	  = isset($row->rental_end_date) ? $row->rental_end_date : '';
    return $rental_period;
}

function ServiceHsn_code($IdValue){   

    $rowApplication = DB::table('tbl_services_entry')->select('hsn_code')->where('service_id', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
    $hsn_code	  = isset($rowApplication->hsn_code) ? $rowApplication->hsn_code : '';
    return $hsn_code;
}

function get_freight_amount($order_id){

    $row = DB::table('tbl_tax_invoice')->select('freight_amount')->where('o_id', '=', $order_id)->first();
    $freight_amount	  = isset($row->freight_amount) ? $row->freight_amount : '';
    return $freight_amount;
}

function offer_invoice_type($id){

    $row = DB::table('tbl_order')->select('offer_type')->where('orders_id', '=', $id)->first();    
    $offer_invoice_type	  = isset($row->offer_type) ? $row->offer_type : "";
    return $offer_invoice_type;
}

//invoice module 
//invoice with get products and price by tbl_invoice_products
// page name: tax_invoice.php
function OrderItemsInfo_invoice1_edit_tax_invoice_pro_by_invoice($invoice_id,$pcode,$buyerstatecode=0,$sellerstatecode=0){

    $invoice_ids=$invoice_id;
           
    $invoice_ids=$invoice_id;
    $invoice_currency_name		= currency_name(get_invoice_currency_by_order_id($pcode));
    $invoice_currency		= get_invoice_currency_by_order_id($pcode);	        
    
    $sql_dis_row_currency = DB::table('tbl_order')->select('Price_value','offer_type')->where('orders_id', '=', $pcode)->first();

    $Offer_Currency = $sql_dis_row_currency->Price_value;
    $offer_type = $sql_dis_row_currency->offer_type;//product/service

    $Offer_Currency=str_replace("backoffice","crm",$Offer_Currency);
    $symbol						= currencySymbol($invoice_currency);
   
    $Offer_Currency= @$symbol[0];
    $currency1 	= @$symbol[0];
    $curValue 	= @$symbol[1];
    $totalCost  = 0;
    echo "<table class='table tax-table table-bordered'>";
    //echo "<tr class='pagehead'><td colspan='11' class='pad'>Item(s) Information </td></tr>";
    echo "<thead><tr>";
    echo "<th>S.No</td>";
    echo "<th class='ws-15' >Product Name</th>";
    echo "<th >HSN Code</th>";
    echo "<th>Part No.</th>";
    echo"<th>Qty</th>";
    echo"<th>Is Service</th>";
    echo"<th class='ws-5'>TAX</th>";
    echo"<th align='center' class='ws-5'>Available Qty</th>
    <th class='ws-10'>Unit Price </th>
    <th class='ws-5'>MAX discounted rate allowed</th>
    <th >Rate</th>";
    
    $sql_dis_row = DB::table('prowise_discount')->select('show_discount')->where('orderid', '=', $pcode)->first();
    $show_discount = isset($sql_dis_row->show_discount) ? $sql_dis_row->show_discount : '';

    if($show_discount=="Yes") {
    echo "<th style='display:none' >Discount(-)</th>";
    $dis_td=1;
    }
    else
    {
    }
    echo "<th style='display:none'>Add IGST Value(%)</th>";
    //		echo "<td width='10%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right' >GST %</td>";
    echo "<th>Sub Total</th></tr>
        </thead>
        <tbody>";

    if($invoice_ids=='' || $invoice_ids=='0')
    {
    $sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc ";
    }
    else
    {
    $sqlOrderPro = "select * from tbl_invoice_products where tax_invoice_id ='$invoice_ids' order by tax_pro_id asc ";
    }

    $rsOrderPro = DB::select(DB::raw($sqlOrderPro));
    
    if(empty($rsOrderPro))
    {
    //insertion in tbl_invoice_products from tbl_do_products	
    $SQLDOProduct2 					= "select * from tbl_do_products where OID='".$pcode."'"; 
    $SQLDOProductRS2 = DB::select(DB::raw($SQLDOProduct2));

    $offer_type = offer_invoice_type($pcode);

    $invoice_type = $offer_type;
   
    foreach($SQLDOProductRS2 as $SQLDOProductROW2) {
      
    if($invoice_type=='product')
    {
        $inv_hsn_code = ProHsn_code($SQLDOProductROW2->pro_id);
    }
    else
    {
        $inv_hsn_code = ServiceHsn_code($SQLDOProductROW2->pro_id);
    }
                                
            $data_array_invoice_products["tax_invoice_id"] 	= $invoice_ids;
            $data_array_invoice_products["model_no"] 		= addslashes($SQLDOProductROW2->ItemCode);
            $data_array_invoice_products["pro_description"]	= addslashes($SQLDOProductROW2->Description);
            $data_array_invoice_products["quantity"]		= addslashes($SQLDOProductROW2->Quantity);
            $data_array_invoice_products["price"]			= addslashes($SQLDOProductROW2->Price);
            $data_array_invoice_products["s_inst"]			= "0";//addslashes($SQLDOProductROW2->do_type);			
            $data_array_invoice_products["service_period"]	= addslashes($SQLDOProductROW2->service_period);			
            $data_array_invoice_products["order_id"] 		= $pcode;
            $data_array_invoice_products["hsn_code"] 		= $inv_hsn_code;//addslashes($SQLDOProductROW2->pro_id);
            $data_array_invoice_products["per_item_tax_rate"] 		= isset($SQLDOProductROW2->per_item_tax_rate) ? $SQLDOProductROW2->per_item_tax_rate : '';
            $data_array_invoice_products["pro_id"] 			= addslashes($SQLDOProductROW2->pro_id);
            $data_array_invoice_products["status"] 			= "active";

            $result_data_array_invoice_products = DB::table('tbl_invoice_products')->insertGetId($data_array_invoice_products);
    }
    }
    $h=0;
    foreach($rsOrderPro as $rowOrderPro)
    {
    $h++;
    //$OrderProID	 = $rowOrderPro->order_pros_id;
    if($invoice_ids=='' || $invoice_ids=='0')
    {
    $do_unq_id	 		= $rowOrderPro->ID;
    $ItemCode	 		= $rowOrderPro->ItemCode;
    $pro_ordered_qty	= $rowOrderPro->Quantity;
    $Price				= $rowOrderPro->Price;
    $pro_description	= $rowOrderPro->Description;
    $gst_sale_type_per_item = isset($rowOrderPro->per_item_tax_rate) ? $rowOrderPro->per_item_tax_rate : '';
    }
    else
    {
        $do_unq_id	 		= $rowOrderPro->tax_pro_id;
        $ItemCode	 		= $rowOrderPro->model_no;
        $pro_ordered_qty	= $rowOrderPro->quantity;
        $Price				= $rowOrderPro->price;
        $pro_description	= $rowOrderPro->pro_description;
        $gst_sale_type_per_item = $rowOrderPro->per_item_tax_rate;	
    }

        $is_service	= isset($rowOrderPro->is_service) ? $rowOrderPro->is_service : '';
    //rumit 26-06-2023
    $proID	 	 = pro_id_by_itemcode($ItemCode,$pcode);//$rowOrderPro->pro_id;
    $groupID 	 = isset($rowOrderPro->group_id) ? $rowOrderPro->group_id : '';

    $pro_ware_house_stock = product_stock($proID);


    if($pro_ware_house_stock >= $pro_ordered_qty)
    {
        $stock_ava= "<i class='bi bi-check-lg check-co'></i>";
    }
    else
    {
        $stock_ava= "<i class='text-danger bi bi-x'></i> ";
    }

    $qty_slab = quantity_slab($proID);
    $max_dis_last = product_discount($proID);
    $max_dis_last_qty_wise = quantity_slab_max_discount($proID,$pro_ordered_qty);
    
    if($qty_slab=='Yes')
    {

    /*qty wise discount added by rumit on 25-may-2020 starts*/
    if($max_dis_last_qty_wise=='0' && $max_dis_last_qty_wise=='')
    {
            $pro_max_discount_allowed=$max_dis_last;//=$sql_max_dis_data->pro_max_discount;
    }
    else
    { 
            $pro_max_discount_allowed=$max_dis_last_qty_wise;//$sql_max_dis_data_qty_wise->max_discount_percent;
    }
    }
    else if($qty_slab=='No')
    {
        $pro_max_discount_allowed=$max_dis_last;
    }
    else
    {
            $pro_max_discount_allowed="0";
    }
    $product_serial_nos = serial_no_generated_by_pro_id_and_order_id($pcode,$proID);
    if($product_serial_nos!='')
    {
        $product_serial_nos="<br/><b>Product S. No.: </b>".$product_serial_nos;
    }
    else
    {
        $product_serial_nos=" ";
    }
    $rental_period = "";
    if($offer_type=='service')
    {
        //echo "aajhbajba".$invoice_ids;
        $rental_period = get_rental_period($invoice_ids);
        $rental_start_date=$rental_period[0];
        $rental_end_date=$rental_period[1];
        
        if($rental_start_date=='' || $rental_start_date=='0000-00-00')
        {
    $rental_start_date=PO_date_delivery_order($pcode);
    $date=date_create(PO_date_delivery_order($pcode));
    date_add($date,date_interval_create_from_date_string("31 Days"));
    $rental_end_date=date_format($date,"Y-m-d");	

    /*date_add($rental_start_date,date_interval_create_from_date_string("31 Days"));
    echo date_format($rental_start_date,"Y-m-d");*/
        $rental_period="Rental charges for the period  ".date_format_india($rental_start_date)." to ".date_format_india($rental_end_date);
        }
        else
        {
        $rental_period="Rental charges for the period  ".date_format_india($rental_start_date)." to ".date_format_india($rental_end_date);		
        }	
        
        
    }

    echo "<tr >";
    echo "<td >".$h."</td>";
    //echo "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right' valign='top'><strong>".$rowOrderPro->pro_name."</strong><br />";
    echo "<td style='word-break: break-all;'>	<b>".stripcslashes(str_replace("\\", "",$pro_description)).'</b><br/>';
    //echo '		<b>Item Type:</b>'.$this->product_type_class_name($this->product_type_class_id($proID)).'echo '	<br/>
        echo'	<b>Item Code:</b>'.pro_text_ordered($proID,$pcode).''.
                $rental_period.'<br/>
                '.$product_serial_nos;//$this->serial_no_generated_by_pro_id_and_order_id($pcode,$proID);//$rowOrderPro->barcode;
    "</td>";

    //echo  "<br>QTY wise Max discount allowed: --".$max_dis_last_qty_wise=$this->quantity_slab_max_discount($proID,$pro_ordered_qty);

    //echo "<br><span class='redstar'>QBD:</span><span class='green-color'>--".$qty_slab."</span>";//$pro_max_discount_allowed=$this->product_discount($proID);
    //echo "<br><span class='redstar'>Final Allowed discunt: --".$pro_max_discount_allowed."</span>";//$pro_max_discount_allowed=$this->product_discount($proID);
    //echo "offer_type:".$offer_type;
    /*if($offer_type=='service')
    {
    $hsn_code=$this->ServiceHsn_code($proID);//$rowOrderPro->hsn_code;
    }
    else{
    $hsn_code=$this->ProHsn_code($proID);//$rowOrderPro->hsn_code;
    }*/

    $hsn_code=$rowOrderPro->hsn_code;
    if($hsn_code=='')
    {
    $hsn_code="N/A";
    }
    echo "</td>";
    echo "<td><input type='text' value='$hsn_code' name='hsn_code[]' class='form-control' /> </td>";
    echo "<td><input type='text' name='pro_model[]' value='".$ItemCode."' class='form-control'> </td>";
    echo "<td>";
    echo '<input type="hidden" name="pro_models[]" value="'.$ItemCode.'" class="form-control">
    <input type="hidden" name="do_unq_id[]" value="'.$do_unq_id.'" class="form-control">
    <input type="hidden" name="pro_qty[]" id="pro_qty_'.$h.'" value="'.$pro_ordered_qty.'" size="2" class="form-control">' . $pro_ordered_qty. '</td>';
    //echo "aaa::".$is_service;
    //echo $service_selected_N;
    echo "<td>";
    ?>

    <select name='is_service[]' id='is_service' >
    <option value='Y' <?php if($is_service=='Y'){ echo 'selected="selected"';}?>>Y</option>
    <option value='N' <?php if($is_service=='N' ){	echo  'selected="selected"';}?> >N</option>
    </select>

    <?php echo "</td>";

    echo "<td >";

    echo "tax:".$gst_sale_type_per_item; ?>

    <select name='gst_sale_type_per_item[]' id='gst_sale_type_per_item' class='form-select'  >
    <?php
    $rs_gst_sale_type = DB::table('tbl_gst_sale_type_master')->orderby('gst_sale_type_name', 'ASC')->get();

    if(!empty($rs_gst_sale_type))
    {
    foreach($rs_gst_sale_type as $row_gst_sale_type)
        {	
    ?>
    <option value="<?php echo $row_gst_sale_type->gst_sale_type_tax_per;?>" <?php if($row_gst_sale_type->gst_sale_type_tax_per == $gst_sale_type_per_item ){ echo "selected='selected'"; }?>><?php echo $row_gst_sale_type->gst_sale_type_name;?> @ <?php echo $row_gst_sale_type->gst_sale_type_tax_per;?>%</option>
        <?php
            }
        }
    ?>
    </select>

    <?php echo "</td>";
    echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right'>".$stock_ava.' '.''.$pro_ware_house_stock;
    //echo '<input type="hidden" name="pro_qty[]" value="'. $pro_ware_house_stock.'" size="2" class="form-control">'.$stock_ava.'  </td>';
    $orPrice = $Price;
    echo "<td valign='top' align='center' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap'> ";
    echo $Offer_Currency." ";
    //$per_product_price=number_format($orPrice,2);
    $pro_final_price = (float) pro_final_price($pcode,$proID);
    $per_product_price = number_format($pro_final_price,2);
    echo  "".$per_product_price;
    echo '<input type="hidden" name="per_product_price[]" value="'. $per_product_price.'"  class="form-control">';
    echo '<input type="hidden" name="pro_max_discount_allowed[]" value="'. $pro_max_discount_allowed.'" size="4" class="form-control">';
    "per_pro_discount_amt: ".$per_pro_discount_amt= str_replace(',', '',$per_product_price) * ($pro_max_discount_allowed/100);
    "MIN allowed amt".$max_pro_amt_allowed_with_discount= str_replace(',', '',$per_product_price) - ($per_pro_discount_amt);
    echo "</td>";
    //echo "<br>".$orPrice;
    //echo "<br>--".$max_pro_amt_allowed_with_discount;

    if($orPrice < $max_pro_amt_allowed_with_discount)
    {
        $min_date_allowed_class="bi bi-exclamation-triangle-fill btn-sorg3  p-3 mb-2 bg-danger text-white";
    //	$rate_allowed=" MAX Discount allowed<br>".$pro_max_discount_allowed."%  <br> Rate not allowed below this: <br> ";
        $rate_allowed="Rate not allowed below this: <br> ";
    }
    else
    {
        $min_date_allowed_class="p-3 mb-2 bg-success text-white";
        $rate_allowed="<br>";		
    }
    echo "<td class=' $min_date_allowed_class text-end'>$rate_allowed".$Offer_Currency.' '.$max_pro_amt_allowed_with_discount= str_replace(',', '',$per_product_price) - ($per_pro_discount_amt)." </td>";

    $sql_dis_row = DB::table('prowise_discount')->where('orderid', '=', $pcode)->where('proid', '=', $proID)->first();
    $dis_td = isset($dis_td) ? $dis_td : '';
    $show_discount = isset($sql_dis_row->show_discount) ? $sql_dis_row->show_discount : '';
    $discount_percent = isset($sql_dis_row->discount_percent) ? $sql_dis_row->discount_percent : 0;
    $discount_amount = isset($sql_dis_row->discount_amount) ? $sql_dis_row->discount_amount : 0;

    if($show_discount=="Yes" || $dis_td) {
    echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'>";
    echo $Offer_Currency." "; 
    echo number_format($discount_amount,2);
    echo "<br />";
    $discounted_price =$orPrice*$discount_percent/100;			
    //$sql_dis_row->discount_percent;
    echo '<input type="hidden" name="pro_price1[]" value="'. $per_product_price.'" >';
    echo '<input type="hidden" name="pro_id[]" value="'. $proID.'" >';
    echo '<input type="hidden" name="discount_percent[]" value="'. $discount_percent.'" size="4" class="form-control"><br>'.$discount_percent.'%';
    echo "</td>";
    }
    else
    {
        $discounted_price=$orPrice*$discount_percent/100;	
    echo '<input type="hidden" name="pro_price2[]" value="'. $per_product_price.'" >';
    echo '<input type="hidden" name="pro_id[]" value="'. $proID.'" >';
    }

    echo "<td valign='top' > ";
    echo $Offer_Currency." ";
    //echo "dis:--".$discounted_price;
    $per_product_price=$orPrice;
    echo '<input type="text" name="pro_price[]" id="pro_price_'.$h.'" value="'. $per_product_price.'" size="10" onChange="cal('.$h.')" class="form-control change" style="display:inline-block;text-align: right;" >';
    //echo '<input type="hidden" name="pro_max_discount_allowed[]" value="'. $pro_max_discount_allowed.'" size="4" class="form-control">';
    echo "</td>";
    echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap' style='display:none'> ";
    echo $Offer_Currency." ";
    //	echo $per_product_tax=$rowOrderPro->Pro_tax;
    echo "".$discounted_price_tax_amt = (float)($orPrice-$discounted_price) * (float)$gst_sale_type_per_item/100;			
    echo "<br>(".$per_product_GST_percentage=$gst_sale_type_per_item; 
    echo ")</td>";
    /*		echo "<td valign='top' class='tblBorder_invoice_bottom tblBorder_invoice_right' nowrap='nowrap'> ";
    echo $Offer_Currency." ";
    echo $per_product_GST_percentage=$rowOrderPro->GST_percentage;
    echo "</td>";
    */			
    //echo "DIIOISoi".$discounted_price_tax_amt;
    //echo "<br>DIIOISoi".$discounted_price;
    //$SumProPrice = ($orPrice-$discounted_price+$discounted_price_tax_amt)* $rowOrderPro->Quantity;//$proPrice * $rowOrderPro->pro_quantity; //old price with minus discount amount commnetdd on 31-jan-2023 by rumit 
    $SumProPrice = ($orPrice+$discounted_price_tax_amt)* $pro_ordered_qty;//$proPrice * $rowOrderPro->pro_quantity;
    $totalTax = $discounted_price_tax_amt * $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;
    //$ProTotalPrice = ($orPrice-$discounted_price)* $rowOrderPro->Quantity;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit//old price with minus discount amount commnetdd on 31-jan-2023 by rumit
    $ProTotalPrice = ($orPrice)* $pro_ordered_qty;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit 
    $totalCost     = $totalCost + $ProTotalPrice ;
    echo "<td valign='top' class='tblBorder_invoice_bottom ' nowrap='nowrap' >";
    echo $Offer_Currency." "; 
    echo number_format($ProTotalPrice,2);
    //echo "<input type='text' id='subtotal_".$h."' >";
    echo "</td>";
    //echo "<td><input type='submit' value='Delete' class='inputton' name='REM_NOW' onclick=\"return confirm('Are You Sure to Delete this product.\");' /><input type='idden' name='ID' value='$rowOrderPro->pro_id' /></td>";
    //echo "<input type='idden' name='ID' value='$rowOrderPro->pro_id' />";
    echo "</tr>";
    //			echo "<tr bgcolor='#F6F6F6'><td colspan='11' height='2'></td></tr>";
    }
    echo "<tr><td colspan='12'>";
    tax_invoice_total_edit_pro_by_do($invoice_ids,$pcode,$buyerstatecode,$sellerstatecode);
    echo "</td></tr>";
    echo "<tbody></table>";
}

///////////////////////TAx invoice freight edit function
function tax_invoice_total_edit_pro_by_do($invoice_ids,$pcode,$buyerstatecode,$sellerstatecode){	

$invoice_currency_name		= currency_name(get_invoice_currency_by_order_id($pcode));
$invoice_currency		= get_invoice_currency_by_order_id($pcode);	
$symbol						= currencySymbol($invoice_currency);

$currency1 	= @$symbol[0];
$Offer_Currency= @$symbol[0];
$curValue 	= @$symbol[1];
$GST_sale_type = get_gst_sale_type($pcode);
$GST_sale_type_tax = get_gst_sale_type_tax($GST_sale_type);

$rowOrderTotal = DB::table('tbl_order')->where('orders_id', '=', $pcode)->first();

if(!empty($rowOrderTotal))
{

if($invoice_ids=='' || $invoice_ids=='0')
{
$sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc ";
}
else
{
$sqlOrderPro = "select * from tbl_invoice_products where tax_invoice_id ='$invoice_ids' order by tax_pro_id asc ";
}

$rsOrderPro = DB::select(DB::raw($sqlOrderPro));

$h=0;
$totalTax = 0;
$totalqty = 0;
$totalCost = 0;

foreach($rsOrderPro as $rowOrderPro)
{
$h++;

if($invoice_ids=='' || $invoice_ids=='0')
{
$ItemCode	 = $rowOrderPro->ItemCode;
$pro_ordered_qty=$rowOrderPro->Quantity;
$Price		=$rowOrderPro->Price;
$pro_description		= $rowOrderPro->Description;
$per_item_tax_rate		= isset($rowOrderPro->per_item_tax_rate) ? $rowOrderPro->per_item_tax_rate : 0;
}
else
{
	$ItemCode	 = $rowOrderPro->model_no;
	$pro_ordered_qty=$rowOrderPro->quantity;
	$Price=$rowOrderPro->price;
	$pro_description		=$rowOrderPro->pro_description;
	$per_item_tax_rate		=$rowOrderPro->per_item_tax_rate;
}



$proID	 	 = pro_id_by_itemcode($ItemCode,$pcode);//$rowOrderPro->pro_id;
$groupID 	 = isset($rowOrderPro->group_id) ? $rowOrderPro->group_id : '';

$pro_ware_house_stock = product_stock($proID);



//$OrderProID	 = $rowOrderPro->order_pros_id;
//$proID	 	 = $rowOrderPro->pro_id;
//$groupID 	 = $rowOrderPro->group_id;
//$orPrice 	 = $rowOrderPro->Price;
$orPrice 	 = $Price;
//number_format($orPrice,2);

$sql_dis_row = DB::table('prowise_discount')->where('orderid', '=', $pcode)->where('proid', '=', $proID)->first();
$discount_amount = isset($sql_dis_row->discount_amount) ? $sql_dis_row->discount_amount : 0;
$discount_percent = isset($sql_dis_row->discount_percent) ? $sql_dis_row->discount_percent : 0;

$subtotal1					=($orPrice*$pro_ordered_qty)-$discount_amount;
$subtotal1_dis				=$discount_amount;
$discount_percent			=$discount_percent;
$discounted_price			=$orPrice*$discount_percent/100;			//discount amt per unit
$pro_hsn_code				= ProHsn_code($proID);

//if($GST_sale_type=='1')
//{
//echo "GST tax<br>".$per_product_GST_percentage	= $this->GST_percentage_by_product($pcode,$proID);//$rowOrderPro->GST_percentage;//product gst %
//}
//else
//{
 $per_product_GST_percentage	= $per_item_tax_rate;//$GST_sale_type_tax;//$this->GST_percentage_by_product($pcode,$proID);//$rowOrderPro->GST_percentage;//product gst %
//}


//"GST tax<br>".$per_product_GST_percentage	= $this->GST_percentage_by_product($pcode,$proID);//$rowOrderPro->GST_percentage;//product gst %
//$discounted_price_tax_amt	=($orPrice-$discounted_price)* $per_product_GST_percentage/100;			 //old commednted by rumit on 31-jan-2023
$discounted_price_tax_amt	=($orPrice)* $per_product_GST_percentage/100;			
//$SumProPrice 				= ($orPrice-$discounted_price+$discounted_price_tax_amt)* $rowOrderPro->Quantity;//$proPrice * $rowOrderPro->pro_quantity; //old commednted by rumit on 31-jan-2023
 $SumProPrice 				= ($orPrice+$discounted_price_tax_amt)* $pro_ordered_qty;//$proPrice * $rowOrderPro->pro_quantity; 
$totalTax				   +=$discounted_price_tax_amt * $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;

$totalqty				   += $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;//total no of items in order
//echo "Freight amt: ".$this->freight_amount_by_order_id($pcode);
//$ProTotalPrice 				= ($discounted_price_tax_amt+$orPrice-$discounted_price)* $rowOrderPro->Quantity;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit  //old commednted by rumit on 31-jan-2023
$ProTotalPrice 				= ($discounted_price_tax_amt+$orPrice)* $pro_ordered_qty;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit  //old commednted by rumit on 31-jan-2023
$GST_sale_type = get_gst_sale_type($pcode);
$GST_sale_type_tax=  $per_item_tax_rate;// $this->get_gst_sale_type_tax($GST_sale_type);

$freight_tax_rate=($GST_sale_type_tax+100)/100;
$freight_amount 			= freight_amount_by_credit_note_invoice_id($invoice_ids);//$this->freight_amount_by_order_id($pcode);//$rowOrderPro->freight_amount;// * $rowOrderPro->pro_quantity;
$freight_amount_with_gst = (float)$freight_amount / $freight_tax_rate;//$freight_amount/1.18;// * $rowOrderPro->pro_quantity;
$freight_gst_amount = (float)$freight_amount-$freight_amount_with_gst;// * $rowOrderPro->pro_quantity;
$totalCost     = $totalCost + $ProTotalPrice;
}
$GST_tax_amt				= GST_tax_amount_on_offer($pcode);//$rowOrderPro->GST_percentage;				
$TotalOrder	   				= $rowOrderTotal->total_order_cost;
$ship		   				= $rowOrderTotal->shipping_method_cost;
$shippingValue				= $ship;
$taxValue					= $rowOrderTotal->tax_cost;
$tax_included				= $rowOrderTotal->tax_included;
$tax_perc					= $rowOrderTotal->taxes_perc;
$discount_perc				= $rowOrderTotal->discount_perc;
$discount_per_amt			= $rowOrderTotal->discount_per_amt;
$show_discount				=$rowOrderTotal->show_discount;
$subtotal_after_discount    = $TotalOrder - $subtotal1_dis;
if($tax_included=='Excluded')
{
@$subtotal_tax       		= (float)$subtotal_after_discount * (float)$tax_perc/100;
}
else
{
$subtotal_tax       		= $subtotal_after_discount;
}
if($tax_included=='Excluded')
{
$GrandTotalOrder			= $subtotal_after_discount + $subtotal_tax;
}
else
{
$GrandTotalOrder			= $subtotal_after_discount + 0;
}
//$subtotal_final		= $subtotal_tax;
$couponDiscount 			= $rowOrderTotal->coupon_discount;
}


echo "<table width='100%' class='table tax-table table-bordered' >";
/*echo "<tr class='pagehead'>";
echo "<td colspan='2' class='pad' nowrap  align='left'>Order Total </td>";
echo "</tr>";*/

echo "<tr ><td  class='ws-49'></td>";

echo "<td  class='text-end' colspan='6'><strong>Sub Total :</strong> </td>";
echo "<td  align='right' >&nbsp; &nbsp;";
//echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
echo $Offer_Currency.$sub_total_amount_without_gst=number_format($totalCost-$totalTax,2);
echo"<input type='hidden' value='$sub_total_amount_without_gst' name='sub_total_amount_without_gst'>";
echo "</td>";
echo "</tr>";
//echo $show_discount;
if($show_discount=="Yes") {
echo "<tr  style='display:none'>";
echo "<td><strong>Discount :</strong></td>";
echo "<td > &nbsp; &nbsp;";
echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
echo number_format($subtotal1_dis,2);
echo "</td>";
echo "</tr>";
}


echo "<tr ><td></td>";
echo "<td class='text-end' colspan='6'><b>Freight </b></td>";
echo "<td align='right' colspan='2'>";
//echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
$total_freight_amt_show = (float)$freight_amount-$freight_gst_amount;
echo $Offer_Currency;
?>
<input type="text" name='freight_included' id='freight_included' value="<?php echo $total_freight_amt_show ;?>" class='form-control ws-49 change' required dir="rtl"  />
<?php
echo "</td>";
echo "</tr>";
$Grand_subtotal1=$subtotal1;

//echo "gst".$GST_tax_amt;

$Total_GST_amount=($totalTax+$freight_gst_amount);

$CGST=$Total_GST_amount/2;
$SGST=$Total_GST_amount/2;

echo"<input type='hidden' value='$Total_GST_amount' name='Total_GST_amount' id='Total_GST_amount'>";
//if($tax_included=='Excluded' || $GST_tax_amt>0)
if($GST_tax_amt>0)
{
	
	
	
if($buyerstatecode==$sellerstatecode)
{

if($GST_tax_amt>0)
{

echo "<tr><td></td>";
echo "<td class='text-end'  colspan='6' ><strong>CGST:</strong></td>";
echo "<td class='text-end' id='cgst'>".number_format($CGST,2)."</td>";
echo "</tr>";
echo "<tr><td></td>";
echo "<td class='text-end'  colspan='6'><strong>SGST :</strong></td>";
echo "<td class='text-end' id='sgst'>".number_format($SGST,2)."</td>";
echo "</tr>";
}



}

else{
	
//	echo "</tr>";
echo "<tr><td></td>";
//echo "sddsd:".$totalTax;
if($totalTax>0)
{
echo "<td class='text-end'  colspan='6'><strong>Output tax IGST</strong></td>";
}
/*else
{
echo "<td colspan='6'><strong>Add VAT/CST@ $tax_perc% :</strong></td>";
}*/
echo "<td class='text-end' id='sgst'> &nbsp; &nbsp; ";
if($totalTax>0)
{
//echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
echo $Offer_Currency;
echo $Total_GST_amount=number_format($totalTax+$freight_gst_amount,2);//total gst value rumit 



}
/*else
{
echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
$subtotal_tax1=$subtotal_tax;
echo "sdjfbsjd".number_format($subtotal_tax1,2);
}
*/
//echo $subtotal1;
$Grand_subtotal1=$totalCost;//$subtotal1+$subtotal_tax1+$totalTax;
echo "</td>";
echo "</tr>";
}
}
/*	
echo "<tr class='text'>";
echo "<td class='pad' nowrap align='right'>Shipping &amp; Handling </td>";
echo "<td  align='left'>: &nbsp; &nbsp; $currency1 ";
printf(" %.2f ",$ship);
echo "</td>";
echo "</tr>";
*/



echo "<tr class='text'><td></td>";
echo "<td  class='text-end' colspan='6' ><h4>Grand Total :</h4></td>";
echo "<td class='text-end' id='grand_total'><h4> &nbsp; &nbsp; ";
//echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
echo $Offer_Currency;
//		printf(" %.2f",$TotalOrder);
echo $Grand_subtotal = number_format($totalCost + (float)$freight_amount,2);

echo "</h4></td>";
echo "</tr>";

echo "<tr><td colspan='5'>";
$total=($totalTax+$freight_gst_amount);
$tax_amount_in_words = ucwords(amountInWords($total));
//echo"<br>". ucwords($this->getIndianCurrency($total));
//echo"<br>". ucwords($this->convert_number_to_words($total));
//$obj    = new toWords($total);
//    echo"<br>". ucwords(substr($obj->words,0,-2));
echo "</td></tr>";	

echo '<tr class="trbgcolor"><td colspan="2"></td>
      <td class="text-end" ><b>Total</b></td>
	  <td></td>
      <td><b>'.$totalqty.' No\'s</b></td>
      <td class="text-end" colspan="3" id="grand_total_2"><b>'.number_format($totalCost + (float)$freight_amount,2).'</b></td>
    </tr>';
echo '<tr>
	<td colspan="8">Amount Chargeable (In words) <br> <b>INR1 '.$tax_amount_in_words.'</b> 
<span class="float-end">E. & O.E</span>
	</td>	
</tr>';
?>

<?php


echo "</table>";
}

function freight_amount_by_credit_note_invoice_id($ID){

    $row = DB::table('tbl_tax_credit_note_invoice')->select('freight_amount')->where('credit_note_invoice_id', '=', $ID)->first();     
    $freight_amount		= isset($row->freight_amount) ? $row->freight_amount : '';
    return $freight_amount;
}

/////////// this function use to display the order Total amount info
//function ends

function pro_final_price($orderid,$pro_id){  

    $row = DB::table('tbl_order_product')->select('pro_final_price','pro_price')->where('order_id', '=', $orderid)->where('pro_id', '=', $pro_id)->first();  
    
    $pro_final_price = isset($row->pro_final_price) ? $row->pro_final_price : '';
    if($pro_final_price == 0) $pro_final_price = $row->pro_price;
    
    return $pro_final_price;
}

function Get_invoice_total_value($ID) {

    $rs = DB::table('tbl_invoice_products')->select('price','quantity')->where('tax_invoice_id', '=', $ID)->first();     
    foreach($rs as $row) {
        @$totalAmt	  += (@$row->quantity*$row->price);	
    }
    return $totalAmt;
}

function credit_note_generated_by_invoice_id($invoice_id){

    $row = DB::table('tbl_tax_credit_note_invoice')->select('credit_note_invoice_id','invoice_status','credit_invoice_generated_date')->where('invoice_id', '=', $invoice_id)->first();     
    return $row;
}

function get_ewaybill_by_invoice_id($invoiceid){  

    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('ewaybillpdf')->where('invoice_id', '=', $invoiceid)->orderby('gst_irn_response_id','DESC')->first(); 
    $ewaybillpdf = isset($rowadmin_accs->ewaybillpdf) ? $rowadmin_accs->ewaybillpdf : '';
    return $ewaybillpdf;
}

function get_IRN_status_by_invoice_id($invoiceid){  
    
    $rowadmin_accs = DB::table('tbl_tax_invoice_gst_irn_response')->select('response_msg_status')->where('invoice_id', '=', $invoiceid)->orderby('gst_irn_response_id','DESC')->first(); 
    $response_msg_status = isset($rowadmin_accs->response_msg_status) ? $rowadmin_accs->response_msg_status : '';	
    return $response_msg_status;
}

function OrderItemsInfo_invoice1_view_tax_invoice_pro_by_invoice_view($invoice_id,$pcode)
{
	$invoice_ids=$invoice_id;
    $invoice_currency_name		= currency_name(get_invoice_currency_by_order_id($pcode));
    $invoice_currency		= get_invoice_currency_by_order_id($pcode);
    
    $sql_dis_row_currency = DB::table('tbl_order')->select('offer_type','Price_value')->where('orders_id', '=', $pcode)->first();
    $offer_type					= $sql_dis_row_currency->offer_type;
    $Offer_Currency				= $sql_dis_row_currency->Price_value;
    $Offer_Currency				= str_replace("backoffice","crm",$Offer_Currency);
    $symbol						= currencySymbol($invoice_currency);
    
    $currency1 					= $symbol[0];
    $curValue 					= $symbol[1];
    $totalCost  				= 0;
    echo "<table width='100%' cellpadding='0' cellspacing='0' class='no-break'>";
    //echo "<tr class='pagehead'><td colspan='11' class='pad'>Item(s) Information </td></tr>";
    echo "<thead><tr >";
    echo "<th style='border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;font-family:arial; font-size:11px;'>S.No</td>";
    echo "<th align='center' style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;font-family:arial; font-size:11px;'>Description of Goods</th>";
    echo "<th style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;font-family:arial; font-size:11px;'>HSN/SAC</th>";
    //echo "<th>Part No.</th>";
    echo"<th style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;font-family:arial; text-align:center; font-size:11px;' align='right'>Quantity</th>";
    echo"<th style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;text-align:center;font-family:arial; font-size:11px;'>Rate</th>";
    echo"<th style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f; text-align:center;font-family:arial; font-size:11px;'>Per</th>";
        
    $sql_dis_row = DB::table('prowise_discount')->select('show_discount')->where('orderid', '=', $pcode)->first();
    $show_discount = isset($sql_dis_row->show_discount) ? $sql_dis_row->show_discount : 0;

    if($show_discount == "Yes") {
    echo "<th style='display:none' >Discount(-)</th>";
    $dis_td=1;
    }
    else
    {
    }
    echo "<th class='text-end' style='border-top:1px solid #0f0f0f;border-bottom:1px solid #0f0f0f;font-family:arial; font-size:11px;'>Sub Total</th></tr>
        </thead>
        <tbody>";

    if($invoice_ids=='' || $invoice_ids=='0')
    {
    $sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' and Price!='0' order by ID asc  ";
    }
    else
    {
    $sqlOrderPro = "select * from tbl_invoice_products where tax_invoice_id ='$invoice_ids' and price!='0'  order by tax_pro_id asc ";
    }

    $rsOrderPro = DB::select(DB::raw($sqlOrderPro));
    $h=0;
    $rental_period = 0;
    $per_product_price = 0;
    $discounted_price_tax_amt = 0;
    foreach($rsOrderPro as $rowOrderPro)
    {
    $h++;

    if($invoice_ids=='' || $invoice_ids=='0')
    {
    $ItemCode	 = $rowOrderPro->ItemCode;
    $pro_ordered_qty=$rowOrderPro->Quantity;
    $Price		=$rowOrderPro->Price;
    $pro_description		=$rowOrderPro->Description;
    $per_item_tax_rate		=$rowOrderPro->per_item_tax_rate;

    }
    else
    {
        $ItemCode	 = $rowOrderPro->model_no;
        $pro_ordered_qty=$rowOrderPro->quantity;
        $Price=$rowOrderPro->price;
        $pro_description		=$rowOrderPro->pro_description;
        $per_item_tax_rate		=$rowOrderPro->per_item_tax_rate;
    }



    //$ItemCode	 = $rowOrderPro->ItemCode;
    $proID	 	 = pro_id_by_itemcode($ItemCode,$pcode);//$rowOrderPro->pro_id;
    $groupID 	 = isset($rowOrderPro->group_id) ? $rowOrderPro->group_id : '';

    $pro_ware_house_stock=product_stock($proID);
    //$pro_ordered_qty=$rowOrderPro->Quantity;

    if($pro_ware_house_stock >= $pro_ordered_qty)
    {
        $stock_ava= "<i class='bi bi-check-lg check-co'></i>";
    }
    else
    {
        $stock_ava= "<i class='text-danger bi bi-x'></i> ";
    }

    $qty_slab=quantity_slab($proID);
    $max_dis_last=product_discount($proID);
    $max_dis_last_qty_wise=quantity_slab_max_discount($proID,$pro_ordered_qty);
    //pro_max_discount_allowed
    //echo   "<br>Max dixxxx".$max_dis_last;
    if($qty_slab=='Yes')
    {
    /*qty wise discount added by rumit on 25-may-2020 starts*/
    if($max_dis_last_qty_wise=='0' && $max_dis_last_qty_wise=='')
    {
            $pro_max_discount_allowed=$max_dis_last;//=$sql_max_dis_data->pro_max_discount;
    }
    else
    {
            $pro_max_discount_allowed=$max_dis_last_qty_wise;//$sql_max_dis_data_qty_wise->max_discount_percent;
    }
    }
    else if($qty_slab=='No')
    {
        $pro_max_discount_allowed=$max_dis_last;
    }
    else
    {
            $pro_max_discount_allowed="0";
    }
    echo "<tr >";
    if($offer_type=='service')
    {
    //	echo "==================================";
        $rental_period=get_rental_period($invoice_id);
        $rental_start_date=$rental_period[0];
        $rental_end_date=$rental_period[1];
        
        if($rental_start_date=='' || $rental_start_date=='0000-00-00')
        {
    $rental_start_date=PO_date_delivery_order($pcode);
    $date=date_create(PO_date_delivery_order($pcode));
    date_add($date,date_interval_create_from_date_string("31 Days"));
    $rental_end_date=date_format($date,"Y-m-d");	

    /*date_add($rental_start_date,date_interval_create_from_date_string("31 Days"));
    echo date_format($rental_start_date,"Y-m-d");*/
        $rental_period="Rental charges for the period  ".date_format_india($rental_start_date)." to ".date_format_india($rental_end_date);
        }
        else
        {
        $rental_period="Rental charges for the period  ".date_format_india($rental_start_date)." to ".date_format_india($rental_end_date);		
        }
    }

    $product_serial_nos=serial_no_generated_by_pro_id_and_order_id($pcode,$proID);
    if($product_serial_nos!='' && $product_serial_nos!='ACL SKIP,')
    {
        $product_serial_nos="<br/><b>Product S. No.: </b>".$product_serial_nos;
    }

    else
    {
        $product_serial_nos=" ";
    }


    echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;'>".$h."</td>";
    echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;word-break: break-all;' valign='top'>	<b>".stripcslashes(str_replace("\\", "", $pro_description)).'</b><br/>';
    //echo '		<b>Item Type:</b>'.$this->product_type_class_name($this->product_type_class_id($proID)).'<br/>';
    //echo '		<b>Item Code:</b>'.$this->pro_text_ordered($proID,$pcode).$product_serial_nos.
    echo '		<b>Item Code:</b>'.$ItemCode.$product_serial_nos.
                '<br>'.$rental_period;//$rowOrderPro->barcode;;//$rowOrderPro->barcode;
    "</td>";
    //$hsn_code=$this->ProHsn_code($proID);//$rowOrderPro->hsn_code;
    /*if($offer_type=='service')
    {
    $hsn_code=$this->ServiceHsn_code($proID);//$rowOrderPro->hsn_code;
    }
    else{
    $hsn_code=$this->ProHsn_code($proID);//$rowOrderPro->hsn_code;
    }
    */
    $hsn_code=$rowOrderPro->hsn_code;
    if($hsn_code=='')
    {
    $hsn_code="N/A";
    }
    $ItemCode = isset($rowOrderPro->ItemCode) ? $rowOrderPro->ItemCode : '';

    echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' valign='top'> $hsn_code </td>";
    echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f; text-align:right;' valign='top'>";
    echo '<input type="hidden" name="pro_model[]" value="'. $ItemCode .'"  class="form-control"><input type="hidden" name="pro_qty[]" value="'. $pro_ordered_qty.'" size="2" class="form-control">' . $pro_ordered_qty. '</td>';

    $orPrice = $Price;

    if($orPrice=='0')
    {
        $none_class=' style="display:none"';
    }

    $sql_dis_row = DB::table('prowise_discount')->where('orderid', '=', $pcode)->where('proid', '=', $proID)->first(); 
    $show_discount = isset($sql_dis_row->show_discount) ? $sql_dis_row->show_discount : 0;

    if($show_discount == "Yes" || isset($dis_td)) {
    echo "<td valign='top' class='' nowrap='nowrap' style='display:none'>";
    //echo $Offer_Currency." "; 
    echo number_format($sql_dis_row->discount_amount,2);
    echo "<br />";
    $discounted_price=$orPrice*$sql_dis_row->discount_percent/100;			
    //$sql_dis_row->discount_percent;
    echo '<input type="hidden" name="pro_price1[]" value="'. $per_product_price.'" >';
    echo '<input type="hidden" name="pro_id[]" value="'. $proID.'" >';
    echo '<input type="hidden" name="discount_percent[]" value="'. $sql_dis_row->discount_percent.'" size="4" class="form-control">hi<br>'.$sql_dis_row->discount_percent.'%';
    echo "</td>";
    }
    else
    {
    $discount_percent = isset($sql_dis_row->discount_percent) ? $sql_dis_row->discount_percent : 0; 

    $discounted_price=$orPrice*$discount_percent/100;	
    echo '<input type="hidden" name="pro_price2[]" value="'. $per_product_price.'" >';
    echo '<input type="hidden" name="pro_id[]" value="'. $proID.'" >';
    }

    echo "<td valign='top' style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' align='right'> ";
    echo $currency1." ";

    //echo "dis:--".$discounted_price;
    echo $per_product_price=$orPrice;
    //echo '<input type="text" name="pro_price[]" value="'. $per_product_price.'" size="10" class="form-control" style="display:inline-block;text-align: right;" >';
    //echo '<input type="hidden" name="pro_max_discount_allowed[]" value="'. $pro_max_discount_allowed.'" size="4" class="form-control">';
    echo "</td>";
    echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' valign='top' align='center'>No's</td>";
    $SumProPrice = ($orPrice+$discounted_price_tax_amt)* $pro_ordered_qty;//$proPrice * $rowOrderPro->pro_quantity;
    $totalTax = $discounted_price_tax_amt * $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;
    //$ProTotalPrice = ($orPrice-$discounted_price)* $rowOrderPro->Quantity;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit
    $ProTotalPrice = ($orPrice)* $pro_ordered_qty;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit
    $totalCost     = $totalCost + $ProTotalPrice ;
    echo "<td style='border-bottom:1px solid #0f0f0f; ' valign='top' align='right'>";
    echo $currency1." "; 
    echo number_format($ProTotalPrice,2);
    echo "</td>";
    echo "</tr>";
    }
    echo "<tbody></table>";
}



//// page name: tax_invoice_pdf.php
function tax_invoice_total_view_pro_by_invoice_view($invoice_id,$pcode,$buyerstatecode=0,$sellerstatecode=0)
{	
$invoice_ids=$invoice_id;
//echo "<br>buyerstatecode".$buyerstatecode;
//echo "<br>sellerstatecode".$sellerstatecode;
$invoice_currency_name		= currency_name(get_invoice_currency_by_order_id($pcode));
$invoice_currency		= get_invoice_currency_by_order_id($pcode);

$symbol		= currencySymbol($invoice_currency);

$GST_sale_type=get_gst_sale_type($pcode);
$GST_sale_type_tax=get_gst_sale_type_tax($GST_sale_type);

$currency1 	= $symbol[0];
$curValue 	= $symbol[1];

$rowOrderTotal = DB::table('tbl_order')->where('orders_id', '=', $pcode)->first(); 

if(!empty($rowOrderTotal))
{
//$sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc";

if($invoice_ids=='' || $invoice_ids=='0')
{
$sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc  ";
}
else
{
$sqlOrderPro = "select * from tbl_invoice_products where tax_invoice_id ='$invoice_ids' order by tax_pro_id asc ";
}

$rsOrderPro = DB::select(DB::raw($sqlOrderPro));

$total_products_count = @count($rsOrderPro);
$h=0;
$subtotal1=0;
$totalTax = 0;
$totalqty = 0;
$totalCost = 0;
foreach($rsOrderPro as $rowOrderPro)
{
$h++;

if($invoice_ids=='' || $invoice_ids=='0')
{
$ItemCode	 = $rowOrderPro->ItemCode;
$pro_ordered_qty=$rowOrderPro->Quantity;
$Price		=$rowOrderPro->Price;
$pro_description		=$rowOrderPro->Description;
$per_item_tax_rate		=$rowOrderPro->per_item_tax_rate;

}
else
{
	$ItemCode	 = $rowOrderPro->model_no;
	$pro_ordered_qty=$rowOrderPro->quantity;
	$Price=$rowOrderPro->price;
	$pro_description		=$rowOrderPro->pro_description;
	$per_item_tax_rate		=$rowOrderPro->per_item_tax_rate;
}



//$ItemCode	 = $rowOrderPro->ItemCode;
$proID	 	 = pro_id_by_itemcode($ItemCode,$pcode);//$rowOrderPro->pro_id;
$groupID 	 = isset($rowOrderPro->group_id) ? $rowOrderPro->group_id : '';
$pro_ware_house_stock = product_stock($proID);
//$pro_ordered_qty=$rowOrderPro->Quantity;

//$OrderProID	 = $rowOrderPro->order_pros_id;
//$proID	 	 = $rowOrderPro->pro_id;
//$groupID 	 = $rowOrderPro->group_id;
$orPrice 	 = $Price;
//number_format($orPrice,2);

$sql_dis_row = DB::table('prowise_discount')->select('*')->where('orderid', '=', $pcode)->where('proid', '=', $proID)->first();   
$Quantity = isset($rowOrderPro->Quantity) ? $rowOrderPro->Quantity : 1;
$discount_amount = isset($rowOrderPro->discount_amount) ? $rowOrderPro->discount_amount : 0;
$discount_percent = isset($rowOrderPro->discount_percent) ? $rowOrderPro->discount_percent : 0;

$subtotal1					= ($orPrice*$Quantity)-$discount_amount;
$subtotal1_dis				= $discount_amount;
$discount_percent			= $discount_percent;
$discounted_price			= $orPrice*$discount_percent/100;			//discount amt per unit
$pro_hsn_code				= ProHsn_code($proID);
//$per_product_GST_percentage	= $this->GST_percentage_by_product($pcode,$proID);//$rowOrderPro->GST_percentage;//product gst %


$per_product_GST_percentage	= $per_item_tax_rate;//		$GST_sale_type_tax;//$this->GST_percentage_by_product($pcode,$proID);//$rowOrderPro->GST_percentage;//product gst %

//$discounted_price_tax_amt	=($orPrice-$discounted_price)* $per_product_GST_percentage/100;			
$discounted_price_tax_amt	= ($orPrice)* $per_product_GST_percentage/100;			
//$SumProPrice 				= ($orPrice-$discounted_price+$discounted_price_tax_amt)* $rowOrderPro->Quantity;//$proPrice * $rowOrderPro->pro_quantity;
$SumProPrice 				= ($orPrice+$discounted_price_tax_amt)* $pro_ordered_qty;//$proPrice * $rowOrderPro->pro_quantity;
$totalTax				   += $discounted_price_tax_amt * $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;

$totalqty				   += $pro_ordered_qty;//$rowOrderPro->Pro_tax * $rowOrderPro->pro_quantity;//total no of items in order
//echo "Freight amt: ".$this->freight_amount_by_order_id($pcode);
//$ProTotalPrice 				= ($discounted_price_tax_amt+$orPrice-$discounted_price)* $rowOrderPro->Quantity;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit
$ProTotalPrice 				= ($discounted_price_tax_amt+$orPrice)* $pro_ordered_qty;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit


$ProTotalPrice 				= ($discounted_price_tax_amt+$orPrice) * $pro_ordered_qty;//($rowOrderPro->pro_final_price) * $rowOrderPro->pro_quantity ;//remove tax changed by rumit
$freight_amount 			= freight_amount_by_order_id($pcode);//$rowOrderPro->freight_amount;// * $rowOrderPro->pro_quantity;
"GST_sale_type_tax=".$GST_sale_type_tax;
"tax as per selction GST tax type==>".$freight_tax_rate=($GST_sale_type_tax+100)/100;
"<br>freight without GST value:".$freight_amount_with_gst = $freight_amount/$freight_tax_rate;// * $rowOrderPro->pro_quantity;
"<br>freight GST value:".$freight_gst_amount = $freight_amount-$freight_amount_with_gst;// * $rowOrderPro->pro_quantity;

/*$freight_amount 			= $this->freight_amount_by_order_id($pcode);//$rowOrderPro->freight_amount;// * $rowOrderPro->pro_quantity;
"freight without GST value:".$freight_amount_with_gst = $freight_amount/1.18;// * $rowOrderPro->pro_quantity;
"<br>freight GST value:".$freight_gst_amount = $freight_amount-$freight_amount_with_gst;// * $rowOrderPro->pro_quantity;
*/
$totalCost     = $totalCost + $ProTotalPrice;

}
//if($freight_tax_rate)
if($GST_sale_type_tax=='0')
{
$GST_tax_amt				= "0";//$this->GST_tax_amount_on_offer($pcode);//$rowOrderPro->GST_percentage;				
}
else
{
	$GST_tax_amt				= GST_tax_amount_on_offer($pcode);//$rowOrderPro->GST_percentage;				
}
$TotalOrder	   				= $rowOrderTotal->total_order_cost;
$ship		   				= $rowOrderTotal->shipping_method_cost;
$shippingValue				= $ship;
$taxValue					= $rowOrderTotal->tax_cost;
$tax_included				= $rowOrderTotal->tax_included;
$tax_perc					= $rowOrderTotal->taxes_perc;
$discount_perc				= $rowOrderTotal->discount_perc;
$discount_per_amt			= $rowOrderTotal->discount_per_amt;
$show_discount				= $rowOrderTotal->show_discount;
$subtotal_after_discount    = $TotalOrder - $subtotal1_dis;
if($tax_included=='Excluded')
{
@$subtotal_tax       		= $subtotal_after_discount*(float)$tax_perc/100;
}
else
{
$subtotal_tax       		= $subtotal_after_discount;
}
if($tax_included=='Excluded')
{
$GrandTotalOrder			= $subtotal_after_discount + $subtotal_tax;
}
else
{
$GrandTotalOrder			= $subtotal_after_discount + 0;
}
//$subtotal_final		= $subtotal_tax;
$couponDiscount 			= $rowOrderTotal->coupon_discount;
}

echo "<table width='100%' cellpadding='0' cellspacing='0' class='break-before-auto'>";
/*echo "<tr class='pagehead'>";
echo "<td colspan='2' class='pad' nowrap  align='left'>Order Total </td>";
echo "</tr>";*/
echo "<tr ><td  class='ws-49' style='border-bottom:1px solid #0f0f0f;'></td>";
echo "<td  align='right' colspan='6' style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;'><strong>Sub Total:</strong> </td>";
echo "<td  align='right' style='border-bottom:1px solid #0f0f0f;'>&nbsp; &nbsp;";
echo $currency1." ";
echo number_format($totalCost-$totalTax,2);
echo "</td>";
echo "</tr>";
//echo $show_discount;
if($show_discount=="Yes") {
echo "<tr  style='display:none'>";
echo "<td><strong>Discount :</strong></td>";
echo "<td > &nbsp; &nbsp;";
echo $currency1;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
echo number_format($subtotal1_dis,2);
echo "</td>";
echo "</tr>";
}


echo "<tr ><td style='border-bottom:1px solid #0f0f0f;'></td>";
echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f' align='right' colspan='6'><b>Freight: </b></td>";
echo "<td align='right' style='border-bottom:1px solid #0f0f0f;' colspan='2'>";
echo $currency1;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
$total_freight_amt_show=$freight_amount-$freight_gst_amount;
?>
<!--changed dropdown from text field on 24-5-2019 as disscssed with Mr. Pankaj & sandeep-->
<?php echo number_format($total_freight_amt_show);?>
<?php
echo "</td>";
echo "</tr>";
$Grand_subtotal1=$subtotal1;
$Total_GST_amount=($totalTax+$freight_gst_amount);

$CGST=$Total_GST_amount/2;
$SGST=$Total_GST_amount/2;
if($tax_included=='Excluded' || $GST_tax_amt>0)
{
//	echo "</tr>";
//echo $buyerstatecode;
//echo "<br>".$$sellerstatecode;
if($buyerstatecode==$sellerstatecode)
{
if($GST_tax_amt>0)
{

echo "<tr><td style='border-bottom:1px solid #0f0f0f;'></td>";
echo "<td style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' align='right' colspan='6'><strong>CGST :</strong></td>";
echo "<td style='border-bottom:1px solid #0f0f0f;' align='right'>".number_format($CGST,2)."</td>";
echo "</tr>";
echo "<tr><td style='border-bottom:1px solid #0f0f0f;'></td>";
echo "<td  colspan='6' style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' align='right'><strong>SGST :</strong></td>";
echo "<td style='border-bottom:1px solid #0f0f0f;' align='right'>".number_format($SGST,2)."</td>";
echo "</tr>";
}

}

else{
echo "<tr><td style='border-bottom:1px solid #0f0f0f;'></td>";
//echo "fdgkhgfds".$totalTax;
if($GST_tax_amt>0)
{
echo "<td  style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;' align='right' colspan='6'><strong>Output tax IGST:</strong></td>";
}
else
{
echo "<td colspan='6' style='border-bottom:1px solid #0f0f0f;'>&nbsp;</td>";
}

echo "<td style='border-bottom:1px solid #0f0f0f;' align='right'> &nbsp; &nbsp; ";
if($totalTax>0)
{
echo $currency1;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
echo number_format($totalTax+$freight_gst_amount,2);//total gst value rumit 
}
else
{
//echo $currency1;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
$subtotal_tax1=$subtotal_tax;
//echo $subtotal_tax1;
}//echo $subtotal1;
}
$Grand_subtotal1=$totalCost;//$subtotal1+$subtotal_tax1+$totalTax;
echo "</td>";
echo "</tr>";
}
/*	
echo "<tr class='text'>";
echo "<td class='pad' nowrap align='right'>Shipping &amp; Handling </td>";
echo "<td  align='left'>: &nbsp; &nbsp; $currency1 ";
printf(" %.2f ",$ship);
echo "</td>";
echo "</tr>";
*/

/*echo "<tr class='text'><td style='border-bottom:1px solid #0f0f0f;'></td>";
echo "<td colspan='6' style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;text-align:right'><h4>Grand Total :</h4></td>";
echo "<td style='border-bottom:1px solid #0f0f0f;text-align:right;'><h4> &nbsp; &nbsp; ";
echo $currency1;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
//		printf(" %.2f",$TotalOrder);
echo $Grand_subtotal=number_format($totalCost+$freight_amount,2);
echo "</h4></td>";
echo "</tr>";*/

$Grand_subtotal=$totalCost+$freight_amount;
$val=$Grand_subtotal;
$rounded_val=(round($val));
$rounded_val_show=number_format(($rounded_val-$val),2);
if($rounded_val_show!='0.00')
{
echo "<tr class='text'><td style='border-bottom:1px solid #0f0f0f;'></td>";
echo "<td colspan='6' style='border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;text-align:right'><h4>Round Off: </h4></td>";
echo "<td style='border-bottom:1px solid #0f0f0f;text-align:right;'><h4> &nbsp; &nbsp; ";
//echo $currency1;//str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
echo $rounded_val_show=number_format(($rounded_val-$val),2);
echo "</h4></td>";
echo "</tr>";
}
echo "<tr><td colspan='5'>";
$total=($totalTax+$freight_gst_amount);
$grand_total_amount_in_words = ucwords(amountInWords(round($totalCost+$freight_amount),2));
$tax_amount_in_words = ucwords(amountInWords($total));
//echo"<br>". ucwords($this->getIndianCurrency($total));
//echo"<br>". ucwords($this->convert_number_to_words($total));
//$obj    = new toWords($total);
//    echo"<br>". ucwords(substr($obj->words,0,-2));
echo "</td></tr>";	
echo '<tr class="trbgcolor"><td colspan="2"></td>
      <td class="text-end" style="text-align:right" colspan="4" ><b>Total</b></td>
      <td style="text-align:right"><b>'.$totalqty.' No\'s</b></td>
      <td style="text-align:right"><b>'.$currency1.number_format(round($totalCost+$freight_amount),2).'</b></td>
    </tr>';
echo '<tr>
	<td  colspan="8" style="border-bottom:1px solid #0f0f0f;">Amount Chargeable (In words) <br> <b> '.currency_name($invoice_currency).' '.$grand_total_amount_in_words.'</b> 
        
	</td>	
</tr>';
?>
<?php if($total > 0){
	/*echo "total_products_count".$total_products_count;
if($total_products_count >1)
{
echo '<tr><td colspan="10" class="break-before">&nbsp;</td></tr>';
}
else
{
	echo '<tr><td colspan="10" class="">&nbsp;</td></tr>';
}*/
	?>


<tr>
  <td colspan="4" align="left" style="border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;"><b>HSN/SAC </b></td>
  <td style="border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;"align="right"><b>Taxable Value3</b></td>
  <td colspan="2" style="border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;" align="right"><b>Integrated Tax</b></td>
  <td style="border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f;" align="right"><b>Total Tax Amount</b></td>
</tr>
<tr>
  <td colspan="5" style="border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;">&nbsp;</td>
  <td align="right" style="border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;">Rate</td>
  <td align="right" style="border-top:1px solid #0f0f0f; border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;">Amount</td>
  <td style="border-bottom:1px solid #0f0f0f;">&nbsp;</td>
</tr>
<?php
//echo $sqlOrderPro = "select * from tbl_order_product where order_id ='$pcode' order by hsn_code asc";
//$sqlOrderPro = "SELECT *, sum(Quantity*Price) as sub_total_price FROM `tbl_do_products`  WHERE `OID` = '$pcode' and Price!='0' GROUP by hsn_code";


if($invoice_ids=='' || $invoice_ids=='0')
{
//$sqlOrderPro = "select * from tbl_do_products where OID ='$pcode' order by ID asc ";
$sqlOrderPro = "SELECT *, sum(Quantity*Price) as sub_total_price FROM `tbl_do_products`  WHERE `OID` = '$pcode' and Price!='0' GROUP by hsn_code";
}
else
{
$sqlOrderPro = "select *, sum(quantity*price) as sub_total_price from tbl_invoice_products where tax_invoice_id ='$invoice_ids' and price!='0' GROUP by hsn_code ";
}

$rsOrderPro = DB::select(DB::raw($sqlOrderPro));
$pro_ctr	 = @count($rsOrderPro);
$h=0;
$subtotal1=0;

$GST_sale_type						= get_gst_sale_type($pcode);
$GST_sale_type_tax					= get_gst_sale_type_tax($GST_sale_type);
$invoice_freight_amount				= get_freight_amount($pcode);
$invoice_tax_freight_amount			= $invoice_freight_amount * ($GST_sale_type_tax/100);
$total_frieght_amt					= $invoice_freight_amount+$invoice_tax_freight_amount;
//echo "CTRRTR".$pro_ctr;
//echo "<br>TOT qty: ".$totalqty;
$ratio_freight_tax					= round($invoice_freight_amount/$pro_ctr,2);

$total_taxable_value = 0;
$total_tax_amount = 0;

foreach($rsOrderPro as $rowOrderPro)
{
$hsn_code				= $rowOrderPro->hsn_code;
$pro_id					= $rowOrderPro->pro_id;
$item_code				= isset($rowOrderPro->pro_model) ? $rowOrderPro->pro_model : '';
$GST_sale_type_tax		= $rowOrderPro->per_item_tax_rate;
//$tax_per 				= $rowOrderPro->per_item_tax_rate;//($GST_sale_type_tax/100);//"0.18";
$tax_per 				= ($GST_sale_type_tax/100);//"0.18";

$taxable_value			= $rowOrderPro->sub_total_price+$ratio_freight_tax;//$this->price_amount_with_model_no($item_code,$pcode)+$ratio_freight_tax;
$integrated_tax			= $taxable_value*$tax_per;
$total_taxable_value 	+=$taxable_value;
$total_tax_amount 		+=$integrated_tax;
?>
<tr>
  <td colspan="4" style="border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;"><?php echo $hsn_code;?></td>
  <td  style="border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;" align="right"><?php echo number_format($taxable_value,2);?></td>
  <td style="border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;" align="right"><?php echo $GST_sale_type_tax;?>%</td>
  <td  style="border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;" align="right"><?php echo number_format($integrated_tax,2);?></td>
  <td  style="border-bottom:1px solid #0f0f0f;" align="right"><?php echo number_format($integrated_tax,2);?></td>
</tr>
<?php }
?>
<tr class="trbgcolor">
  <td colspan="4" align="right" style="border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;"><b>Total</b></td>
  <td style="border-bottom:1px solid #0f0f0f;border-right:1px solid #0f0f0f;" align="right"><strong><?php echo number_format($total_taxable_value,2);?></strong></td>
  <td style="border-bottom:1px solid #0f0f0f;">&nbsp;</td>
  <td align="right" style="border-bottom:1px solid #0f0f0f; border-right:1px solid #0f0f0f;"><strong><?php echo number_format($total_tax_amount,2);?></strong></td>
  <td align="right" style="border-bottom:1px solid #0f0f0f;" ><strong><?php echo number_format($total_tax_amount,2);?></strong></td>
</tr>

<tr>
  <td colspan="8" style="border-bottom:1px solid #0f0f0f;">Tax Amount (In words) <br>
    <b> <?php echo currency_name($invoice_currency).' '.$tax_amount_in_words;?></b></td>
</tr>
<?php }?>
</table>
<?php
}


function vendor_name($ID){

    $row = DB::table('vendor_master')->select('C_Name')->where('ID', '=', $ID)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();
    $comp_name	  = isset($row->C_Name) ? $row->C_Name : '';
    return $comp_name;
}

function po_total($po_id){

    $sql	=	"SELECT PO_ID,Tax_Value, Date, Payment_Terms, Term_Delivery, Flag, SUM(Prodcut_Qty * Prodcut_Price) + Handling_Value + Bank_Value + Freight_Value as total_amount FROM `vendor_po_final` where PO_ID='".$po_id."'";
    $row = DB::select(DB::raw($sql)); 
    $row = $row[0];

    $total			=	$row->total_amount;
    $Tax_Value		= $total*($row->Tax_Value/100);   
    $total = $row->Flag."-".round(($total+$Tax_Value),2);
    return $total;
}

function dateSub1($days){

    $year  = date('Y');  
    $month = date('m');
    $date  = date('d');
    $time = @date('m/d/Y', mktime(0, 0, 0, $month, $date-$days, $year));
    return $time;
}

function vendor_purchase_type($VID){
    
    $row = DB::table('vendor_master')->select('purchase_type')->where('ID', '=', $VID)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();    
    $purchase_type	  = isset($row->purchase_type) ? $row->purchase_type : '';
    return $purchase_type;
}

function site_configuraction(){
    
    $row = DB::table('tbl_general_configuraction')->where('gen_config_id', '=', '1')->first();      
    return $row;
}

function proidentrydesc($id){

    $sqlproidentry = "select pro_desc_entry from tbl_products_entry where pro_id_entry = '$id' and deleteflag = 'active' and status='active'";
    $rowproidentry = DB::table('tbl_products_entry')->select('pro_desc_entry')->where('pro_id_entry', '=', $id)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();

    $pro_desc_entry	= isset($rowproidentry->pro_desc_entry) ? $rowproidentry->pro_desc_entry : '';    
    return ucfirst($pro_desc_entry);
}

function po_product_list_desc($pro_id){
    
    $rows_sub = DB::table('vendor_product_list')->select('Product_List')->where('ID', '=', $pro_id)->first();
    $Product_List	= isset($rows_sub->Product_List) ? $rows_sub->Product_List : ''; 
    return $Product_List;
}

function po_product_price($pro_id){

    $rows_sub = DB::table('vendor_product_list')->select('Prodcut_Price')->where('ID', '=', $pro_id)->first();
    $Prodcut_Price	= isset($rows_sub->Prodcut_Price) ? $rows_sub->Prodcut_Price : ''; 
    return $Prodcut_Price;
}

function po_acl_item_code($pro_id){

    $rows_sub = DB::table('vendor_product_list')->select('ACL_Item_Code')->where('ID', '=', $pro_id)->first();
    $ACL_Item_Code	= isset($rows_sub->ACL_Item_Code) ? $rows_sub->ACL_Item_Code : ''; 
    return $ACL_Item_Code;

}

function vendor_price_basis($VID){

    $rows_sub = DB::table('vendor_master')->select('Price_Basis')->where('ID', '=', $VID)->where('deleteflag', '=', 'active')->where('status', '=', 'active')->first();
    $Price_Basis	= isset($rows_sub->Price_Basis) ? $rows_sub->Price_Basis : ''; 
    return $Price_Basis;
}

function live_search_v($q) {
			
    $sql_product_vendor="SELECT vendor_master.ID, vendor_master.C_Name, vendor_product_list.Vendor_List, vendor_product_list.Product_List FROM `vendor_master` INNER JOIN vendor_product_list ON vendor_master.ID=vendor_product_list.Vendor_List where vendor_product_list.Product_List like '%$q%' GROUP by vendor_master.ID ORDER BY `vendor_master`.`ID` DESC";
    $rs = DB::select(DB::raw($sql_product_vendor)); 
    
    if(!empty($rs)){
        echo "<ul class='list-group'>";
        foreach($rs as $row) {
            @$hint.="<li class='list-group-item list-group-item-action'><a href='/admin/purchase_manager/purchase_order?Vendor_List=".$row->Vendor_List."' class='search_indi'>".$row->C_Name."<a></li>";
        }
        echo "</ul>";
    }else{
        $hint=	@$hint.="<li class='list-group-item list-group-item-action'><a href=''>No Record found<a></li>";
    }
    return @$hint;
}

function po_awb_invoice_path_new($po_id){

    $row = DB::table('vendor_po_invoice_new')->select('invoice_upload','awb_upload','boe_upload')->where('po_id', '=', $po_id)->first();
    $po_uploads_path	  = $row;
    return $po_uploads_path;
}

































function OrderTotalInfo_sales_home_letter($pcode){

    $symbol		= currencySymbol(1);
    $currency1 	= $symbol[0];
    $curValue 	= $symbol[1];

    $rsOrderTotal = DB::table('tbl_order')->select('orders_id')->where('orders_id', '=', $pcode)->get();
    echo "<pre>"; 
    print_r($rsOrderTotal);
    exit;
    if($rsOrderTotal > 0){
    $rowOrderTotal 	= mysqli_fetch_object($rsOrderTotal);
    $sqlOrderPro = "select * from tbl_order_product where order_id ='$pcode' order by order_pros_id asc ";
    $rsOrderPro	 = mysqli_query($GLOBALS["___mysqli_ston"],  $sqlOrderPro);
    $h=0;
    $subtotal1=0;
    while($rowOrderPro = mysqli_fetch_object($rsOrderPro)){
    $h++;
    $OrderProID	 = $rowOrderPro->order_pros_id;
    $proID	 	 = $rowOrderPro->pro_id;
    $groupID 	 = $rowOrderPro->group_id;
    $sqlAttr = "select * from tbl_group where group_id='$groupID'";
    $rsAttr  = mysqli_query($GLOBALS["___mysqli_ston"],  $sqlAttr);
    $i  	 = -1;
    $k  	 = 0;
    $cb 	 = 0;
    $tx		 = 0;
    $ta		 = 0;
    $ManufacturerID = $rowOrderPro->manufacturers_id;
    $orPrice = $rowOrderPro->pro_price;
    number_format($orPrice,2);
    $sql_dis="SELECT * FROM prowise_discount where orderid='".$pcode."' and proid='".$proID."'";
    $sql_dis_dis=mysqli_query($GLOBALS["___mysqli_ston"],  $sql_dis);
    $sql_dis_row=mysqli_fetch_object($sql_dis_dis);
    @$subtotal1+=($orPrice*$rowOrderPro->pro_quantity)- @$sql_dis_row->discount_amount;
    @$subtotal1_dis+=@$sql_dis_row->discount_amount;
    }
    $TotalOrder	   				= $rowOrderTotal->total_order_cost;
    $ship		   				= $rowOrderTotal->shipping_method_cost;
    $shippingValue				= $ship;
    $taxValue					= $rowOrderTotal->tax_cost;
    $tax_included				= $rowOrderTotal->tax_included;
    $tax_perc					= $rowOrderTotal->taxes_perc;
    $discount_perc				= $rowOrderTotal->discount_perc;
    $discount_per_amt			= $rowOrderTotal->discount_per_amt;
    $show_discount				=$rowOrderTotal->show_discount;
    
    $subtotal_after_discount    = $TotalOrder - $subtotal1_dis;
    if($tax_included=='Excluded'){
    @$subtotal_tax       		= $subtotal_after_discount*$tax_perc/100;
    }else{
    $subtotal_tax       		= $subtotal_after_discount;
    }
    if($tax_included=='Excluded'){
    $GrandTotalOrder			= $subtotal_after_discount + $subtotal_tax;
    }else{
    $GrandTotalOrder			= $subtotal_after_discount + 0;
    }
    $couponDiscount 			= $rowOrderTotal->coupon_discount;
    }
    "<table width='50%' border='0' cellpadding='5' cellspacing='0' >";
    "<tr class='text'>";
    "<td width='70%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left ' nowrap align='right'><strong>Sub Total :</strong> </td>";
    "<td width='30%'  align='right' class='tblBorder_invoice_bottom ' nowrap='nowrap'> &nbsp; &nbsp;";
    str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    number_format($subtotal1,2);
    "</td>";
    "</tr>";
    
    if($show_discount=="Yes") {
    "<tr class='text' style='display:none'>";
    "<td  class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><strong>Discount :</strong></td>";
    "<td   align='right'  class='tblBorder_invoice_bottom'  > &nbsp; &nbsp;";
    str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    number_format($subtotal1_dis,2);
    "</td>";
    "</tr>";
    }
    
    $Grand_subtotal1=$subtotal1;
    
    if($tax_included=='Excluded'){
    "</tr>";
    "<tr class='text'>";
    "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><strong>Add CST@ $tax_perc% :</strong></td>";
    "<td align='right'  class='tblBorder_invoice_bottom'  > &nbsp; &nbsp; ";
    str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    $subtotal_tax1=$subtotal_tax;
    number_format($subtotal_tax1,2);
    $Grand_subtotal1=$subtotal1+$subtotal_tax1;
    "</td>";
    "</tr>";
    }
    
    "<tr class='text'>";
    "<td class='pad tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><h4>Grand Total :</h4></td>";
    "<td align='right'  nowrap='nowrap' ><h4> &nbsp; &nbsp; ";
    str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    number_format($Grand_subtotal1,2);
    "</h4></td>";
    "</tr>";
    "</table>";
    return $Grand_subtotal1;
    }
	
    ///////////////////////////////////////
	
	
//RUMIT fucntion started 	
///////////////////////////////RUMIT functions for Sales dashboard Starts on 04-sept-2024 ///////////////////
//get TES id from account manager ID & its financial Year added on 1-feb-2022
function show_financial_year_name($financial_year)
{

$row = DB::table('tbl_financial_year')->select('fin_name')->where('financial_year', '=', $financial_year)->first();
    $fin_name	  = isset($row->fin_name) ? $row->fin_name : '0';
return $fin_name;
}

function show_financial_year_id($financial_year)
{


$row = DB::table('tbl_financial_year')->select('fin_id')->where('fin_name', '=', $financial_year)->first();
//DB::table('tbl_financial_year')->toSql();
//dd($row); //exit;
    $fin_id	  = isset($row->fin_id) ? $row->fin_id : '0';
	
 
$fin_id	  = $row->fin_id;
return $fin_id;
}



function TES_Total_Target($tes_id) {
//	echo "tes_id".$tes_id; exit;
//$row = DB::table('tbl_tes_manager')->select('SUM(actual_target) as tes_total')->whereIn('ID', $tes_id)->first();

          $sql = "select sum(actual_target) as tes_total from tbl_tes_manager where ID IN(".$tes_id.")";
        $row = DB::select(DB::raw($sql));   //exit;
	//print_r($row);exit;
		
        $TES_Total    =  isset($row[0]->tes_total) ? $row[0]->tes_total : '0';


	//$sql_tes_total	= "select sum(actual_target) as tes_total from tbl_tes_manager where ID IN(".$tes_id.") ";
////echo "<br>". $sql_tes_total	= "select sum(sub_total) as tes_total from tbl_tes where tes_id IN('".$tes_id."') "; exit;
//	$rs_tes_total		= mysqli_query($GLOBALS["___mysqli_ston"],$sql_tes_total);
//	$data_tes_row_total = @mysqli_fetch_object($rs_tes_total); 
//$TES_Total=  isset($row->tes_total) ? $row->tes_total : '0'; 

return $TES_Total;
}


function sum_of_credit_note($invoice_id,$start_date,$end_date,$acc_manager)
{

if($invoice_id!='' && $invoice_id!='0')
{
	$invoice_id_search=" and invoice_id='$invoice_id'";
}
else
{
	$invoice_id_search=" ";
}


if($start_date!='' && $start_date!='0')
{
	$date_search=" and credit_invoice_generated_date 
	BETWEEN  '$start_date' AND '$end_date'";
}
else
{
	$date_search=" ";
}


if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
{
	$acc_manager_search=" and prepared_by='$acc_manager'";
}
else
{
	$acc_manager_search=" ";
}

/*
if($aging_max!='' )
	{
	$aging_search_search="  and DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY))  BETWEEN $aging_min and $aging_max  "; //exit;
	}*/


/*
	if($company_name!='')
	{
	//$orders_status='Pending';
	$company_name_search=" and cus_com_name like '%$company_name%'";
	}*/

//invoice_generated_date BETWEEN '".$qtr_start_date_show."' AND '".$qtr_end_date_show."' and invoice_status='approved' $acc_manager_search	
	
//AND ( I_date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show' ) 	
	
//	echo $acc_manager;
 

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
{	
	$acc_manager_search_receivables=" and tti.prepared_by IN ($acc_manager) ";
	}
	
/*"<br>".$sql = "select SUM(sub_total_amount_without_gst) as  sum_of_credit_note from tbl_tax_credit_note_invoice
where 1=1 and invoice_status='approved' $date_search $invoice_id_search $acc_manager_search $company_name_search";
$rs  = mysqli_query($GLOBALS["___mysqli_ston"],  $sql);
$row = mysqli_fetch_object($rs);
$sum_of_credit_note	  	= $row->sum_of_credit_note;*/
//$credit_note_invoice_status	  = $row->credit_note_invoice_id;
$sql = "select SUM(sub_total_amount_without_gst) as  sum_of_credit_note from tbl_tax_credit_note_invoice
where 1=1 and invoice_status='approved' $date_search $invoice_id_search $acc_manager_search ";
        $row = DB::select(DB::raw($sql));   //exit;
	//print_r($row);exit;
		
        $sum_of_credit_note    =  isset($row[0]->sum_of_credit_note) ? $row[0]->sum_of_credit_note : '0';


return $sum_of_credit_note;
}

function invoice_total_by_date($acc_manager,$start_date,$end_date)
{

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
	{
	//$orders_status='Pending';
	$acc_manager_search=" and prepared_by='$acc_manager'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	
	else
	{
		$acc_manager_search="";
	}
/* "<br><br>".$sql = "SELECT  
SUM(sub_total_amount_without_gst * exchange_rate) as total_invoice_amount  from tbl_tax_invoice
where 
invoice_generated_date BETWEEN '".$start_date."' AND '".$end_date."' and invoice_status='approved'
$acc_manager_search";	
$rs = mysqli_query($GLOBALS["___mysqli_ston"],$sql);*/
$sql = "SELECT  
SUM(sub_total_amount_without_gst * exchange_rate) as total_invoice_amount  from tbl_tax_invoice
where 
invoice_generated_date BETWEEN '".$start_date."' AND '".$end_date."' and invoice_status='approved'
$acc_manager_search ";
        $row = DB::select(DB::raw($sql));   //exit;

	
 $total_invoice_amount    =  isset($row[0]->total_invoice_amount) ? $row[0]->total_invoice_amount : '0';
	
	
return $total_invoice_amount;
}


function payment_received_by_aging($acc_manager,$aging_min,$aging_max,$company_name)
{
//	echo "aging_max".$aging_min; //exit;
if($aging_max!='' && $aging_max!='0' )
	{
	$aging_search_search="  and DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY))  BETWEEN $aging_min and $aging_max  "; //exit;
	}
	else
	{
		$aging_search_search="";
	}

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
{	
	$acc_manager_search_receivables=" and tti.prepared_by='$acc_manager' ";
	}
else
{
	$acc_manager_search_receivables=" ";
}

	if($company_name!='')
	{
	//$orders_status='Pending';
	$company_name_search=" and tti.cus_com_name like '%$company_name%'";
	}
	else
	{
	$company_name_search="";
	}

 "<br>".$sql = "SELECT 
 SUM(tpr.payment_received_value)as total_payment_received,
 tti.exchange_rate,
DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)  AS  invoice_date_with_payment_terms,
DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging
from tbl_tax_invoice tti 
INNER JOIN tbl_supply_order_payment_terms_master s
LEFT JOIN tbl_payment_received tpr ON tti.invoice_id=tpr.invoice_id 
where tpr.invoice_id=tti.invoice_id 
and tti.payment_terms=s.supply_order_payment_terms_id
and tti.invoice_id > 230000
and tti.invoice_status='approved'
and tti.invoice_closed_status='No'
$acc_manager_search_receivables
$aging_search_search
$company_name_search
ORDER BY aging DESC";	
$row = DB::select(DB::raw($sql));   //exit;
$total_payment_received    =  isset($row[0]->total_payment_received) ? $row[0]->total_payment_received : '0';	
return $total_payment_received;
}

function pending_account_receivables($acc_manager,$aging_min,$aging_max,$enq_source_search,$company_name)
{
//	echo $acc_manager;
if($enq_source_search!='' && $enq_source_search!='0')
	{
		$enq_source_search_search=" AND  ref_source='$enq_source_search'";
	}
else
{
		$enq_source_search_search=" ";
}

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
{	
	$acc_manager_search_receivables=" and tti.prepared_by IN ($acc_manager) ";
	
if($aging_max!='' && $aging_max!='0' )
	{
	$aging_search_search="  and DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY))  BETWEEN $aging_min and $aging_max  "; //exit;
	}
	
	else
	{
		$aging_search_search="  ";
	}
	
}
else
{
	$acc_manager_search_receivables="";
			$aging_search_search="  ";
}
	if($company_name!='')
	{
	//$orders_status='Pending';
	$company_name_search=" and tti.cus_com_name like '%$company_name%'";
	}
	else
	{
		$company_name_search=" ";
	}
 "<br><br>".$sql ="SELECT tti.exchange_rate,
DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)  AS  invoice_date_with_payment_terms,
DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging,
SUM((tti.freight_amount+tti.sub_total_amount_without_gst+tti.total_gst_amount)* tti.exchange_rate) as total_value_receivables
from tbl_tax_invoice tti 
INNER JOIN tbl_supply_order_payment_terms_master s ON tti.payment_terms=s.supply_order_payment_terms_id
LEFT JOIN tbl_tax_credit_note_invoice ttcni ON ttcni.invoice_id=tti.invoice_id


where  
tti.invoice_id>230000
and tti.invoice_closed_status='No'
and tti.invoice_status='approved'
AND ttcni.invoice_id IS NULL

$acc_manager_search_receivables
$company_name_search
$aging_search_search

ORDER BY aging DESC;";



$row = DB::select(DB::raw($sql));   //exit;
$total_value_receivables    =  isset($row[0]->total_value_receivables) ? $row[0]->total_value_receivables : '0';	
	
 
return $total_value_receivables;
}



function sales_dashboard_orders_in_hand_value($acc_manager,$qtr_start_date_show,$qtr_end_date_show,$enq_stage,$hot_offer_month=0)
{


    "sales db hot_orders in hand::=".$hot_offer_month;

	//$date_ordered_value= '2022-03-31';
if($hot_offer_month!='' && $hot_offer_month!='0')
	{
	//$orders_status='Pending';
//	YEAR(date) = 2012 AND MONTH(date) = 1
//MONTH(date_ordered)=04 and YEAR(date_ordered)=2023;
	  "----".$hot_orders_in_hand_month_search= "  AND tbl_delivery_order.D_Order_Date >= CURDATE() - INTERVAL $hot_offer_month MONTH";//  "  and MONTH(date_ordered)='$hot_offer_month'"; 
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$hot_orders_in_hand_month_search="";
	}


	$date_ordered_value= '2022-03-31';

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
	{
	//$orders_status='Pending';
	$acc_manager_search=" and tbl_order.order_by='$acc_manager'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$acc_manager_search=" ";
	}
if($date_ordered_value!='')
	{
	//$orders_status='Pending';
	$date_ordered_search=" and date_ordered >= '$date_ordered_value'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}	
	else
	{
		$date_ordered_search=" ";
	}
	
/*"<br><br>".$sql = "SELECT SUM(total_order_cost_new) as total_price  FROM `tbl_order` WHERE orders_status='Pending' and deleteflag='active' $enq_stage_search $acc_manager_search $date_ordered_search ";	
$rs = mysqli_query($GLOBALS["___mysqli_ston"],$sql);*/

"<br><br>OID::".$sql = "SELECT 
SUM(tbl_do_products.Quantity * tbl_do_products.Price) as total_order_in_hand_value,
tbl_delivery_challan.id as delivery_challan_id
FROM tbl_delivery_order 
LEFT JOIN tbl_delivery_challan ON tbl_delivery_order.O_Id = tbl_delivery_challan.O_Id 
INNER JOIN tbl_order ON tbl_order.orders_id = tbl_delivery_order.O_Id 
INNER JOIN tbl_do_products ON tbl_delivery_order.O_Id = tbl_do_products.OID 
and tbl_delivery_challan.Invoice_No IS NULL AND  tbl_delivery_order.D_Order_Date BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show'  and tbl_delivery_order.D_Order_Date!='0000-00-00'
 $acc_manager_search $hot_orders_in_hand_month_search  GROUP BY tbl_delivery_challan.id"; //exit;
 
 
$row = DB::select(DB::raw($sql));   //exit;
$total_order_in_hand_value    =  isset($row[0]->total_order_in_hand_value) ? $row[0]->total_order_in_hand_value : '0';	


return $total_order_in_hand_value;
}


function opportunity_value_for_dashboard($acc_manager,$qtr_start_date_show,$qtr_end_date_show,$enq_stage,$hot_offer_month=0)
{
//echo 	$qtr_end_date_show;

  "opportunity hot_offer_month::=".$hot_offer_month;

	//$date_ordered_value= '2022-03-31';
if($hot_offer_month!='' && $hot_offer_month!='0')
	{
	//$orders_status='Pending';
//	YEAR(date) = 2012 AND MONTH(date) = 1
//MONTH(date_ordered)=04 and YEAR(date_ordered)=2023;
	$hot_offer_opportunity_month_search= "  AND follow_up_date >= CURDATE() - INTERVAL $hot_offer_month MONTH";//  "  and MONTH(date_ordered)='$hot_offer_month'"; 
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$hot_offer_opportunity_month_search="";
	}


	//$date_ordered_value= '2022-03-31';
if($enq_stage!='' && $enq_stage!='0')
	{
	//$orders_status='Pending';
	$enq_stage_search=" and offer_probability = '$enq_stage' ";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$enq_stage_search="";
	}

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
	{
	//$orders_status='Pending';
	$acc_manager_search=" and order_by='$acc_manager'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$acc_manager_search=" ";
	}
/*if($date_ordered_value!='')
	{
	//$orders_status='Pending';
//	$date_ordered_search=" and date_ordered >= '$date_ordered_value'";
	$date_ordered_search=" and follow_up_date >= '$date_ordered_value'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}	
	else
	{
		$date_ordered_search="";
	}
*/
if($qtr_start_date_show!='')
{
	$follow_up_date_ordered_search=" and follow_up_date  BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show';";	
}
else
{
	$follow_up_date_ordered_search="";
}
	
  "<br><br>".$sql = "SELECT SUM(total_order_cost_new) as total_price  FROM `tbl_order` WHERE orders_status='Pending' $hot_offer_opportunity_month_search and deleteflag='active' $enq_stage_search $acc_manager_search  $follow_up_date_ordered_search   ";	
  
  
$row = DB::select(DB::raw($sql));   //exit;
$q1_target    =  isset($row[0]->total_price) ? $row[0]->total_price : '0';	  
  
 
return $q1_target;
}


function order_total($pcode)
{
$sql="SELECT order_id, sum(pro_quantity* pro_price) as total_price FROM `tbl_order_product` WHERE `order_id` = $pcode ORDER BY `order_id` DESC";
$row = DB::select(DB::raw($sql));   //exit;
$Grand_subtotal1= $row[0]->total_price;
$gst=$Grand_subtotal1*18/100;
$final_total=$Grand_subtotal1+$gst;
$final_total   =  isset($final_total) ? $final_total : '0';	  
/*$rs  = mysqli_query($GLOBALS["___mysqli_ston"],  $sql);
@$row = @mysqli_fetch_object(@$rs);
$Grand_subtotal1= $row->total_price;
$gst=$Grand_subtotal1*18/100;
$final_total=$Grand_subtotal1+$gst;*/
return $final_total;
}


function opportunity_value_name($pcode)
{
$sql="SELECT opportunity_value_name  FROM `tbl_opportunity_value_master` WHERE `opportunity_value_id` = '$pcode' ";
$row = DB::select(DB::raw($sql));   //exit;$opportunity_value_name= $row->opportunity_value_name;
$opportunity_value_name    =  isset($row[0]->opportunity_value_name) ? $row[0]->opportunity_value_name : '0';	 
return $opportunity_value_name;
}


function task_type_name($STvalue)
{
$sqlcity = "select tasktype_name from tbl_tasktype_master where tasktype_abbrv = '$STvalue' and deleteflag = 'active'";
$row = DB::select(DB::raw($sqlcity));   //exit;$opportunity_value_name= $row->opportunity_value_name;
//$tasktype_name	  = $rowcity->tasktype_name;
$tasktype_name    =  isset($row[0]->tasktype_name) ? $row[0]->tasktype_name : '0';	 

return ucfirst($tasktype_name);
}


function get_customers_name_by_order_id($id)
{   
$sql = "select customers_name from tbl_order where orders_id = '$id' and deleteflag = 'active'";
$row = DB::select(DB::raw($sql));   //exit;$opportunity_value_name= $row->opportunity_value_name;
$customers_name    =  isset($row[0]->customers_name) ? $row[0]->customers_name : '0';	 
//$customers_name	  = $row->customers_name;
return $customers_name;
}

function get_customers_contact_no_by_order_id($id)
{   
$sql = "select customers_contact_no from tbl_order where orders_id = '$id' and deleteflag = 'active'";
$row = DB::select(DB::raw($sql));
$customers_contact_no    =  isset($row[0]->customers_contact_no) ? $row[0]->customers_contact_no : '0';	
//$customers_contact_no	  = $row->customers_contact_no;
return $customers_contact_no;
}

function get_customers_email_by_order_id($id)
{   
$sql = "select customers_email from tbl_order where orders_id = '$id' and deleteflag = 'active'";
$row = DB::select(DB::raw($sql));
$customers_email    =  isset($row[0]->customers_email) ? $row[0]->customers_email : '0';	
//$customers_contact_no	  = $row->customers_contact_no;
return $customers_email;
}


function supply_payment_terms_abbrv($ref_source)
{
$sql = "select supply_order_payment_terms_abbrv from tbl_supply_order_payment_terms_master where supply_order_payment_terms_id = '$ref_source' and deleteflag = 'active' and supply_order_payment_terms_status='active'";
$row = DB::select(DB::raw($sql));
$supply_order_payment_terms_abbrv    =  isset($row[0]->supply_order_payment_terms_abbrv) ? $row[0]->supply_order_payment_terms_abbrv : '0';
return $supply_order_payment_terms_abbrv;
}

function get_total_part_payment_received($invoice_id)
{
$sql = "select SUM(payment_received_value) as total_part_payment  from tbl_payment_received where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$total_part_payment    =  isset($row[0]->total_part_payment) ? $row[0]->total_part_payment : '0';
 
return $total_part_payment;
}

function invoice_cus_name($invoice_id)
{
$sql = "select cus_com_name from tbl_tax_invoice where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$cus_com_name    =  isset($row[0]->cus_com_name) ? $row[0]->cus_com_name : '0';

return  $cus_com_name;
}

function invoice_con_name($invoice_id)
{
$sql = "select con_name from tbl_tax_invoice where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$con_name    =  isset($row[0]->con_name) ? $row[0]->con_name : '0';
return  $con_name;
}


function invoice_con_mobile($invoice_id)
{
$sql = "select con_mobile from tbl_tax_invoice where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$con_mobile    =  isset($row[0]->con_mobile) ? $row[0]->con_mobile : '0';
return  $con_mobile;
}


function invoice_con_email($invoice_id)
{
$sql = "select con_email from tbl_tax_invoice where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$con_email    =  isset($row[0]->con_email) ? $row[0]->con_email : '0';
return  $con_email;
}

function get_total_credit_note($invoice_id)
{
$sql = "select SUM(credit_note_value) as total_credit_note_value  from tbl_payment_received where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$total_credit_note_value  =  isset($row[0]->total_credit_note_value) ? $row[0]->total_credit_note_value : '0';
return $total_credit_note_value;
}

function get_total_lda_other_value($invoice_id)
{
$sql = "select SUM(lda_other_value) as total_lda_other_value  from tbl_payment_received where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$total_lda_other_value  =  isset($row[0]->total_lda_other_value) ? $row[0]->total_lda_other_value : '0';

return $total_lda_other_value;
}


function get_total_invoice_with_gst($invoice_id)
{
$sql = "select SUM(sub_total_amount_without_gst+total_gst_amount+freight_amount) * exchange_rate as total_invoice_amount_with_tax  from tbl_tax_invoice where invoice_id = '$invoice_id' ";
$row = DB::select(DB::raw($sql));
$total_invoice_amount_with_tax  =  isset($row[0]->total_invoice_amount_with_tax) ? $row[0]->total_invoice_amount_with_tax : '0';

//$total_invoice_amount_with_tax	  = $row->total_invoice_amount_with_tax;
return $total_invoice_amount_with_tax;
}

function Product_app_id($IdValue)
{   
if(is_numeric($IdValue))
{
$sqlApplication = "select pro_id from tbl_index_g2 where match_pro_id_g2 = '$IdValue' and deleteflag = 'active'";

$row = DB::select(DB::raw($sqlApplication));
$pro_app_id  =  isset($row[0]->pro_id) ? $row[0]->pro_id : '0';
 
 
}
else
{
$pro_app_id 	= $IdValue;
}
return $pro_app_id;
}





//graph potential sales
function qtr_target_achived_by_invoice_for_graph($acc_manager,$qtr_start_date_show,$qtr_end_date_show,$month_search=0)
{
	//echo "====".$month_search;
	
/*if($hot_offer_month!='' && $hot_offer_month!='0')
	{
	//$orders_status='Pending';
//	YEAR(date) = 2012 AND MONTH(date) = 1
//MONTH(date_ordered)=04 and YEAR(date_ordered)=2023;
	$hot_offer_month_search= "  AND date_ordered >= CURDATE() - INTERVAL $hot_offer_month MONTH";//  "  and MONTH(date_ordered)='$hot_offer_month'"; 
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$hot_offer_month_search="";
	}
	*/
	
	
if($month_search!='' && $month_search!='0')
	{
//	YEAR(date) = 2012 AND MONTH(date) = 1
//MONTH(date_ordered)=04 and YEAR(date_ordered)=2023;
	$month_search_search= " AND invoice_generated_date >= CURDATE() - INTERVAL $month_search MONTH"; // "  and MONTH(invoice_generated_date)='$month_search'"; 
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}

else
{
	$month_search_search= "";
}

/*if($enq_source_search!='' && $enq_source_search!='0' )
	{
		$enq_source_search_search=" AND  l.ref_source='$enq_source_search'";
	}
else
{
			$enq_source_search_search=" ";
}*/
if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
	{
	//$orders_status='Pending';
	$acc_manager_search=" and prepared_by='$acc_manager'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	
	else
	{
			$acc_manager_search=" ";
	}
 "<br><br>".$sql = "SELECT  
SUM((sub_total_amount_without_gst ) * exchange_rate) as total_price  from tbl_tax_invoice
where 
DATE(invoice_generated_date) BETWEEN '".$qtr_start_date_show."' AND '".$qtr_end_date_show."' and invoice_status='approved' $month_search_search
$acc_manager_search";	


$row = DB::select(DB::raw($sql));
$total_price  =  isset($row[0]->total_price) ? $row[0]->total_price : '0';

return $total_price;
}

function hot_offer_value_for_dashboard($acc_manager,$qtr_start_date_show,$qtr_end_date_show,$hot_offer=0,$hot_offer_month=0)
{
"hot_offer_month::=".$hot_offer_month;
	$date_ordered_value= '2022-03-31';
if($hot_offer!='' && $hot_offer!='0')
	{
	//$orders_status='Pending';
	$hot_offer_search=" and hot_offer = '$hot_offer' ";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$hot_offer_search=" ";
	}



if($hot_offer_month!='' && $hot_offer_month!='0')
	{
	//$orders_status='Pending';
//	YEAR(date) = 2012 AND MONTH(date) = 1
//MONTH(date_ordered)=04 and YEAR(date_ordered)=2023;
	$hot_offer_month_search= "  AND date_ordered >= CURDATE() - INTERVAL $hot_offer_month MONTH";//  "  and MONTH(date_ordered)='$hot_offer_month'"; 
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	else
	{
		$hot_offer_month_search="";
	}


if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
	{
	//$orders_status='Pending';
	$acc_manager_search=" and order_by='$acc_manager'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
	
		else
	{
			$acc_manager_search=" ";
	}
if($date_ordered_value!='')
	{
	//$orders_status='Pending';
	$date_ordered_search=" and date_ordered >= '$date_ordered_value'";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}	
	else
	{
	$date_ordered_search="";		
	}
	
if($qtr_start_date_show!='')
{
	$follow_up_date_ordered_search=" and STR_TO_DATE(follow_up_date, '%Y-%m-%d') BETWEEN '$qtr_start_date_show' AND '$qtr_end_date_show';";	
}
	
	
 "<br><br>".$sql = "SELECT SUM(total_order_cost_new) as total_price  FROM `tbl_order` WHERE orders_status='Pending' $hot_offer_month_search  and deleteflag='active' $hot_offer_search $acc_manager_search 
$follow_up_date_ordered_search ";	

$row = DB::select(DB::raw($sql));
$total_price  =  isset($row[0]->total_price) ? $row[0]->total_price : '0';

 
return $total_price;
}



function total_offers_count_by_pro_id($start_date,$end_date,$pro_id,$month,$conversion_by)
{
if($start_date!='' )
	{
				
		if($month!='' && $month!='0')
		{
			$date_filter_search_month=" and MONTH(o.date_ordered)='$month' "; //exit;
		}
				
		else
		{
		$date_filter_search_month=" "; //exit;
		}
		
		
$date_filter_search=" and o.date_ordered BETWEEN '$start_date' AND '$end_date' $date_filter_search_month "; //exit;

	}


if($conversion_by!='' || $conversion_by!='0')
{
if($conversion_by=='3')
{
$conversion_by_search="  AND offer_probability IN (3)"; //exit;
}

if($conversion_by=='4')
{
$conversion_by_search="  AND offer_probability IN (4)"; //exit;
}

if($conversion_by=='5')
{
$conversion_by_search="  AND offer_probability IN (3,4,5,6,7)"; //exit;
}
	}
	else
	{
$conversion_by_search=" "; //exit;		
	}


 "<br>".$sql="SELECT count(DISTINCT  o.orders_id) total_order_count, 
SUM(top.pro_quantity) as total_quantity, 
top.pro_id, 
top.pro_model, 
o.order_by,
 o.orders_id 
 from tbl_order o 
 INNER JOIN tbl_order_product top ON o.orders_id=top.order_id 
 where 1=1 
 and top.pro_id='$pro_id' 
 $date_filter_search
 $conversion_by_search
 GROUP by top.pro_id;";	

/*$sql="SELECT COUNT(tti.invoice_id) as tot_invoice_count  

from tbl_tax_invoice tti 
INNER JOIN tbl_invoice_products tip ON tip.tax_invoice_id= tti.invoice_id 
where tti.invoice_id=tip.tax_invoice_id and tti.invoice_status='approved' 
$date_filter_search
and tip.pro_id='$pro_id'";

*/
//exit;
$row = DB::select(DB::raw($sql));
$total_order_count  =  isset($row[0]->total_order_count) ? $row[0]->total_order_count : '0';

 
return $total_order_count;
}


function orders_count_this_fy_yr($acc_manager,$start_date,$end_date,$month){
$date_search="  o.date_ordered BETWEEN  '$start_date' AND '$end_date'";	

//$month="3";
if($month!='' && $month!='0')
		{//and tti.invoice_generated_date <= CURDATE( ) - INTERVAL 12 MONTH
		 	$date_filter_search_month=" and (o.date_ordered ) >= CURDATE() - INTERVAL $month MONTH "; //exit;
		}
				
		else
		{
		$date_filter_search_month=" "; //exit;
		}

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All'){	
	$acc_manager_search=" and o.order_by='$acc_manager'";
}
else
{
	$acc_manager_search="";
}

$sql = "SELECT count(DISTINCT  o.orders_id) total_order_count,
o.order_by,
o.orders_id,
 MONTH(o.date_ordered) AS month ,
  MONTHNAME(o.date_ordered) AS month_name 
from tbl_order o  where  $date_search $acc_manager_search  $date_filter_search_month
 GROUP BY month
 ORDER by o.date_ordered "; //exit;
$row[] = DB::select(DB::raw($sql));
$total_order_count  =  isset($row[0]) ? $row[0] : '';
return $total_order_count;

}


function enquiry_count_this_fy_yr($acc_manager,$start_date,$end_date,$month){
 $date_search=" and  tbl_web_enq.Enq_Date BETWEEN  '$start_date' AND '$end_date'";	


if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All'){	
	$acc_manager_search=" and o.order_by='$acc_manager'";
}
else
{
	$acc_manager_search="";
}

if($month!='' && $month!='0')
		{//and tti.invoice_generated_date <= CURDATE( ) - INTERVAL 12 MONTH
			$date_filter_search_month=" and MONTH(tbl_web_enq.Enq_Date) >= CURDATE( ) - INTERVAL '$month' MONTH "; //exit;
		}
				
		else
		{
		$date_filter_search_month=" "; //exit;
		}
$sql = "SELECT count(DISTINCT  o.orders_id) total_enq_count,
o.order_by,
o.orders_id 
from tbl_web_enq   where 1=1 $acc_manager_search  $date_search $date_filter_search_month ";
$row = DB::select(DB::raw($sql));
$total_enq_count  =  isset($row[0]->total_enq_count) ? $row[0]->total_enq_count : '0';
return $total_enq_count;
}


function sales_enquiry_count_this_fy_yr($acc_manager,$start_date,$end_date,$month){
$date_search="   tbl_web_enq_edit.Enq_Date BETWEEN  '$start_date 00:00:59' AND '$end_date  00:00:59'";	
if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All'){	
$acc_manager_search=" and tbl_web_enq_edit.acc_manager='$acc_manager'";
}
else
{
$acc_manager_search="";
}

if($month!='' && $month!='0')
		{//and tti.invoice_generated_date <= CURDATE( ) - INTERVAL 12 MONTH
			$date_filter_search_month=" AND tbl_web_enq_edit.Enq_Date >= NOW() - INTERVAL $month MONTH" ;

		}
				
		else
		{
		$date_filter_search_month=" "; //exit;
		}

"<br>".$sql = "SELECT count(DISTINCT  tbl_web_enq_edit.enq_id) total_sales_enq_count, acc_manager, MONTH(tbl_web_enq_edit.Enq_Date) AS month,
  MONTHNAME(tbl_web_enq_edit.Enq_Date) AS month_name  
from tbl_web_enq_edit   where   $date_search $acc_manager_search $date_filter_search_month group by month   ";
$row[]= DB::select(DB::raw($sql));
$total_sales_enq_count  =$row[0];	  //isset($row[0]->total_sales_enq_count) ? $row[0]->total_sales_enq_count : '0';
return $total_sales_enq_count;
}


function total_invoice_count_this_fy_yr($acc_manager,$start_date,$end_date,$month)
{
//	$month='3';
if($start_date!='' )
	{
				
		if($month!='' && $month!='0')
		{//and tti.invoice_generated_date <= CURDATE( ) - INTERVAL 12 MONTH
			$date_filter_search_month=" and (tti.invoice_generated_date) >= NOW() - INTERVAL $month MONTH "; //exit;
		}
				
		else
		{
		$date_filter_search_month=" "; //exit;
		}

		
$date_filter_search=" and tti.invoice_generated_date BETWEEN '$start_date' AND '$end_date' $date_filter_search_month  "; //exit;

	}
	else
	{
		$date_filter_search=" ";
	}
	

if($acc_manager!='' && $acc_manager!='0' && $acc_manager!='All')
	{
	//$orders_status='Pending';
	$acc_manager_search=" and tti.prepared_by='$acc_manager' ";
//	$acc_manager_search_follow_up="and o.order_by=$acc_manager";
	}
else
{
		$acc_manager_search="";
}

 "<br>".$sql="SELECT COUNT(DISTINCT tti.invoice_id) as tot_invoice_count, MONTH(tti.invoice_generated_date) AS month,
MONTHNAME(tti.invoice_generated_date) AS month_name    
from tbl_tax_invoice tti 
INNER JOIN tbl_invoice_products tip ON tip.tax_invoice_id= tti.invoice_id 
where tti.invoice_id=tip.tax_invoice_id and tti.invoice_status='approved' 
$date_filter_search   $acc_manager_search group by month ";// exit;

$row[]= DB::select(DB::raw($sql));
$tot_invoice_count  =$row[0];

//exit;
return $tot_invoice_count;
}


function get_status_count($cond1,$cond2){ 

    $sql_tasks_data = "select id from events where $cond1 $cond2 order by start_event desc";  
    $tasks_data_count =  DB::select(DB::raw($sql_tasks_data));
    return @count($tasks_data_count);
}


function get_enq_status_count($cond1,$cond2){ 

//echo "<br>". $sql_enq_data = "select ID from tbl_web_enq_edit where 1=1 $cond1 $cond2 ";  
   
$sql_enq_data ="SELECT 
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
where tbl_web_enq_edit.deleteflag='active' 
and tbl_web_enq_edit.lead_id='0'
$cond1 $cond2
HAVING days_since_enq > 0
order by days_since_enq ";   
   
   
    $enq_data_count =  DB::select(DB::raw($sql_enq_data));
    return @count($enq_data_count);
}


function getData_without_condition($table_name, $orderby='1=1', $ase='asc')
{
$sql 	= "SELECT * FROM $table_name where deleteflag='active'  order by $orderby $ase";
$row[]= DB::select(DB::raw($sql));
$result  =$row[0];

/*$result = mysqli_query($GLOBALS["___mysqli_ston"],  $sql);
return $result;
*/}


function get_allowed_product_categories($acc_manager){      
    
    $rowadmin_accs = DB::table('tbl_admin')->select('allowed_category')->where('id', '=', $acc_manager)->first(); 
    $allowed_category = isset($rowadmin_accs->allowed_category) ? $rowadmin_accs->allowed_category : '';	

    return $allowed_category;
}

function pqv_incoming_stock_qty_with_date($model_no){

    $sql = "select vpf.Prodcut_Qty, vpf.E_Date, vpf.delivery from vendor_po_order vpo INNER JOIN vendor_po_final vpf ON vpo.ID = vpf.PO_ID where vpo.Confirm_Purchase='inactive' and vpf.upc_code='$model_no' and vpf.E_Date >=  (CURDATE() - INTERVAL 8 DAY) order by vpf.E_Date ";
    $rs = DB::select(DB::raw($sql));  
    
    foreach($rs as $row){
        echo "<strong>Qty:</strong> ".$Prodcut_Qty	 = $row->Prodcut_Qty."<br>";
        if($row->E_Date!='' ){
            echo "<strong>Incoming Stock Date:</strong> ".$E_date	 = date_format_india($row->E_Date . ' + 8 days')."<br>";//$row->E_Date."<br>";
        }
    }
}


function get_discount_percent($orderid,$proid,$price){      
//    echo "popo".$price; exit;
//$orderid="37541";
//$proid="3340";
    $rowadmin_accs = DB::table('prowise_discount')->select('discount_percent')->where('orderid', '=', $orderid)->where('proid', '=', $proid)->first(); 
//print_r($rowadmin_accs);	 exit;
    $discount_percent	  = isset($rowadmin_accs->discount_percent) ? $rowadmin_accs->discount_percent : '';
	$discounted_price_s= $discount_percent;			
	$discounted_price= $discounted_price_s;
	
  //  $allowed_category = isset($rowadmin_accs->allowed_category) ? $rowadmin_accs->allowed_category : '';	

    return $discounted_price;
}



function last_updated_payment_received()
{
$sql = "select inserted_date  from tbl_payment_received ORDER BY `tbl_payment_received`.`inserted_date` DESC limit 0,1";
$row = DB::select(DB::raw($sql));
$last_updated_payment_received    =  isset($row[0]->inserted_date) ? $row[0]->inserted_date : '0';
//$last_updated_payment_received_updated  = strtotime('+59 minutes', strtotime($last_updated_payment_received));
$minutes_to_add = 330;//add 5.30 hrs to this because currently it taken us time
$time = new DateTime($last_updated_payment_received);
$time->add(new DateInterval('PT' . $minutes_to_add . 'M'));
$last_updated_payment_received_updated = $time->format('Y-m-d H:i');
//echo date('Y-m-d H:i:s', $newtimestamp);
return $last_updated_payment_received_updated;
}



// Converts a number into a short version, eg: 1000 -> 1k
// Based on: http://stackoverflow.com/a/4371114
function number_format_short( $n, $precision = 2 ) {
    if ($n < 900) {
        // 0 - 900
        $n_format = number_format($n, $precision);
        $suffix = '';
    } else if ($n < 900000) {
        // 0.9k-850k
        $n_format = number_format($n / 1000, $precision);
        $suffix = ' K';
    } else if ($n < 900000000) {
        // 0.9m-850m
        $n_format = number_format($n / 1000000, $precision);
        $suffix = ' Lac';
    } else if ($n < 900000000000) {
        // 0.9b-850b
        $n_format = number_format($n / 1000000000, $precision);
        $suffix = ' Cr';
    } else {
        // 0.9t+
        $n_format = number_format($n / 1000000000000, $precision);
        $suffix = ' T';
    }
  // Remove unecessary zeroes after decimal. "1.0" -> "1"; "1.00" -> "1"
  // Intentionally does not affect partials, eg "1.50" -> "1.50"
    if ( $precision > 0 ) {
        $dotzero = '.' . str_repeat( '0', $precision );
        $n_format = str_replace( $dotzero, '', $n_format );
    }
    return $n_format . $suffix;
}
////////////////////////////////RUMIT functions for Sales dashboard ends ///////////////////
//RUMIT fucntion ends 


/****finance***/
function pending_account_receivables_by_aging($acc_manager,$aging_min,$aging_max,$company_name){

    $acc_manager_search_receivables = "";
    $company_name_search = "";

    if($aging_max!='' )
	{
	    $aging_search_search="  and DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY))  BETWEEN $aging_min and $aging_max  "; //exit;
	}
	if($company_name!='')
	{
	    $company_name_search=" and tti.cus_com_name like '%$company_name%'";
	}
    if($acc_manager!='' && $acc_manager!='0')
    {	
        $acc_manager_search_receivables=" and tti.prepared_by='$acc_manager' ";
    }
	
    $sql = "SELECT tti.exchange_rate,
    DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)  AS  invoice_date_with_payment_terms,
    DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging,
    SUM((tti.freight_amount+tti.sub_total_amount_without_gst+tti.total_gst_amount)* tti.exchange_rate) as total_value_receivables
    from tbl_tax_invoice tti 
    INNER JOIN tbl_supply_order_payment_terms_master s
    where   tti.payment_terms=s.supply_order_payment_terms_id
    and tti.invoice_id>230000
    and tti.invoice_status='approved'
    and tti.invoice_closed_status='No'
    $acc_manager_search_receivables
    $aging_search_search
    $company_name_search
    ORDER BY aging DESC";
    $rs = DB::select(DB::raw($sql));
    
    //printr($rs);

    if(!empty($rs)){
        $row = isset($rs[0]) ? $rs[0] : '';
        $total_value_receivables = isset($row->total_value_receivables) ? $row->total_value_receivables : 0; 
    }
    return $total_value_receivables;
}


function pending_account_receivables_by_aging_not_yet_due($acc_manager,$aging_min,$aging_max,$company_name){
   
        $acc_manager_search_receivables = "";
        $company_name_search = "";
        $aging_min = 0;
        $aging_search_search ="  and DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) <= $aging_min "; //exit;
       
        if($acc_manager!='' && $acc_manager!='0')
        {	
            $acc_manager_search_receivables=" and tti.prepared_by='$acc_manager' ";
        }

        if($company_name!='')
        {
            $company_name_search=" and tti.cus_com_name like '%$company_name%'";
        }	
        
    $sql = "SELECT tti.exchange_rate,
    DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)  AS  invoice_date_with_payment_terms,
    DATEDIFF(CURDATE(), DATE_ADD(tti.invoice_generated_date, INTERVAL s.supply_order_payment_terms_abbrv DAY)) AS aging,
    SUM((tti.freight_amount+tti.sub_total_amount_without_gst+tti.total_gst_amount)* tti.exchange_rate) as total_value_receivables
    from tbl_tax_invoice tti 

    INNER JOIN tbl_supply_order_payment_terms_master s
    where  tti.payment_terms=s.supply_order_payment_terms_id
    and tti.invoice_id>230000
    and tti.invoice_status='approved'
    and tti.invoice_closed_status='No'
    $acc_manager_search_receivables
    $aging_search_search
    $company_name_search
    ORDER BY aging DESC";	

    $rs = DB::select(DB::raw($sql));

    if(!empty($rs)){
        $row = isset($rs[0]) ? $rs[0] : '';
        $total_value_receivables = isset($row->total_value_receivables) ? $row->total_value_receivables : 0; 
    }

    return $total_value_receivables;
}

function get_payment_received_type($invoice_id){
   
    $row = DB::table('tbl_payment_received')->select('payment_received_type')->where('invoice_id', '=', $invoice_id)->first(); 
    $payment_received_type	  = isset($row->payment_received_type) ? $row->payment_received_type : '';
    return $payment_received_type;
}

function get_enquiry_id_by_order_id($order_id){

    $row = DB::table('tbl_order')->select('edited_enq_id')->where('orders_id', '=', $order_id)->first(); 
    $edited_enq_id	  = isset($row->edited_enq_id) ? $row->edited_enq_id : '';
    return $edited_enq_id;
}

function get_total_part_payment_received_by_order_id($o_id){

    $sql = "select SUM(payment_received_value) as total_part_payment  from tbl_payment_received where o_id = '$o_id' ";
    $row = DB::select(DB::raw($sql));
    $row = isset($row[0]) ? $row[0] : $row;
    $total_part_payment	  = isset($row->total_part_payment) ?  $row->total_part_payment : 0;
    return $total_part_payment;
}

function get_payment_received_type_by_order_id($order_id){

    $sql = "select payment_received_type from tbl_payment_received where o_id = '$order_id' ";
    $row = DB::select(DB::raw($sql));
    $row = isset($row[0]) ? $row[0] : $row;
    $payment_received_type	  = isset($row->payment_received_type) ? $row->payment_received_type : '';
    return $payment_received_type;
}

function get_payment_type_name($payment_type_id){

    $sql = "select payment_type_name from tbl_payment_type_master where payment_type_id = '$payment_type_id' ";
    $row = DB::select(DB::raw($sql));
   
    $payment_type_name = '';
    if(isset($row->payment_type_name)){
        $payment_type_name	  = $row->payment_type_name;
    }
    if(isset($row[0]->payment_type_name)){
        $payment_type_name	  = $row[0]->payment_type_name;
    }
    
    return $payment_type_name;
}

function get_comp_bank_name($bank_id){

    $sql = "select bank_name from tbl_company_bank_address where bank_id = '$bank_id' ";
    $row = DB::select(DB::raw($sql));

    $bank_name = '';
    if(isset($row->bank_name)){
        $bank_name	  = $row->bank_name;
    }
    if(isset($row[0]->bank_name)){
        $bank_name	  = $row[0]->bank_name;
    }

    return $bank_name;
}

function ven_payments_due_this_week_count(){

    $sql = "SELECT count(DISTINCT(vpi.vendor_id)) as ven_payments_due_this_week_count, vpf.Date, vpf.payment_terms, vpf.Term_Delivery, DATEDIFF(CURDATE(), (vpi.due_on)) as aging, vpf.Flag, vpi.po_id, vpi.vendor_id, vpi.id, vpi.value, vpi.invoice_no, vpi.invoice_date, vpi.due_on, vpi.payment_date_on, vpi.status FROM vendor_po_final vpf INNER JOIN vendor_po_invoice_new vpi ON vpf.PO_ID = vpi.po_id WHERE vpi.status=1 and DATEDIFF(CURDATE(), vpi.due_on) BETWEEN -7 and 0 ";
    $row = DB::select(DB::raw($sql)); 
    $row = isset($row[0]) ? $row[0] : $row;
    $ven_payments_due_this_week_count	  = isset($row->ven_payments_due_this_week_count) ? $row->ven_payments_due_this_week_count : '';
    return $ven_payments_due_this_week_count;
}

function performa_invoice_received_count(){

    $sql = "SELECT count(pi_id) as pi_received FROM `tbl_performa_invoice` where  DATE(`pi_generated_date`) = CURDATE() and save_send='yes' and deleteflag = 'active'";
    $row = DB::select(DB::raw($sql));    
    $pi_received	  = isset($row->pi_received) ? $row->pi_received : '';
    return $pi_received;
}

function service_application_name($ID)
{
    $row = DB::table('tbl_application_service')->select('application_service_name')->where('application_service_id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $application_service_name	  = isset($row->application_service_name) ? $row->application_service_name : '';
    return $application_service_name;
}
function service_app_id($IdValue){   

    if(is_numeric($IdValue)){

        $rowApplication = DB::table('tbl_index_s2')->select('service_id')->where('match_service_id_s2', '=', $IdValue)->where('deleteflag', '=', 'active')->first();
        $service_app_id	  = isset($rowApplication->service_id) ? $rowApplication->service_id : '';
    }
    else
    {
        $service_app_id 	= $IdValue;
    }
    return $service_app_id;
}

function Get_invoice_no($pcode){

    $sql = "select u_invoice_no from tbl_delivery_challan where O_Id='$pcode'";
    $row = DB::table('tbl_delivery_challan')->select('u_invoice_no')->where('O_Id', '=', $pcode)->first();
    
    return  isset($row->u_invoice_no) ? $row->u_invoice_no : '';
}

function incoming_inventory_stock($modelno,$month_search=0,$year_search=0,$datevalid_from=0,$datevalid_to=0){

    $month_search_search = "";
    $date_range_search = "";
    $searchproid = "";

    if($datevalid_from!='0' && $datevalid_to!='0' && $datevalid_from!='' && $datevalid_to!=''){    
        $date_range_search=" AND (date( date_ordered ) BETWEEN '$datevalid_from' AND '$datevalid_to')";    
    }    
    if($year_search!='' && $year_search!='0'){    
        $year_search_search=" AND YEAR( date_ordered ) IN ( $year_search )";    
    }
    if($month_search!='' && $month_search!='0'){    
        $month_search_search=" AND MONTH( date_ordered ) IN ( $month_search )";    
    }

    $searhRecord =  $year_search_search.$month_search_search.$date_range_search.$searchproid;    
    $sql ="	SELECT sum(pro_quantity) as incoming_stock FROM `tbl_inventory` WHERE `pro_model` LIKE '%$modelno%' AND stock_type='Incoming' $searhRecord order by date_ordered desc";    
    $row = DB::select(DB::raw($sql));    
        
    $sum = $row[0]->incoming_stock; 
    if($sum=='' || $sum=='0'){    
        $incoming_qty="0";    
    }else{
        $incoming_qty=$sum;    
    }    
    return $incoming_qty;    
}

function outgoing_inventory_stock($modelno,$month_search=0,$year_search=0,$datevalid_from=0,$datevalid_to=0){

    $month_search_search = "";
    $date_range_search = "";
    $searchproid = "";
    
    if($datevalid_from!='0' && $datevalid_to!='0' && $datevalid_from!='' && $datevalid_to!=''){
        $date_range_search=" AND (date( date_ordered ) BETWEEN '$datevalid_from' AND '$datevalid_to')";
    }
    if($year_search!='' && $year_search!='0'){
        $year_search_search=" AND YEAR( date_ordered ) IN ( $year_search )";
    }
    if($month_search!='' && $month_search!='0'){
        $month_search_search=" AND MONTH( date_ordered ) IN ( $month_search )";
    }
    $searhRecord =  $year_search_search.$month_search_search.$date_range_search.$searchproid;
    $sql ="SELECT sum(pro_quantity) as outgoing_stock FROM `tbl_inventory` WHERE TRIM(`pro_model`) =  '$modelno' AND stock_type='outgoing' $searhRecord order by date_ordered desc";
    $row = DB::select(DB::raw($sql));     
    $sum = $row[0]->outgoing_stock;
    
    if($sum=='' || $sum=='0'){
        $outgoing_stock="0";
    }else{
        $outgoing_stock=$sum;
    }
    return $outgoing_stock;
}


function product_qty($id){   

    $row = DB::table('tbl_products')->select('ware_house_stock')->where('pro_id', '=', $id)->where('deleteflag', '=', 'active')->first();    
    $pro_title	  = isset($row->ware_house_stock) ? $row->ware_house_stock : '';
    return $pro_title;
}

function stock_office_location_name($ID){  

    $rowState = DB::table('tbl_location')->where('id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $location_name	  = isset($rowState->location) ? $rowState->location : '';
    return ucfirst($location_name);
}

function old_stock_chart($pcode){
    
    $row_t = DB::table('tbl_demo_stock')->select('stock_id','transf_stock_id','remarks')->where('stock_id', '=', $pcode)->first(); 

    if(!empty($row_t))
    {       
        $transf_stock_id_get=$row_t->transf_stock_id;?>
        <ul align="center">
        <li><img src="/assets/images/truck.png" height="50px" title="" /></li>
        <li><?php echo stock_remarks($transf_stock_id_get);?></li>
        </ul>
        <?php
        old_stock_chart($transf_stock_id_get);
    }
   
}


function prowise_discount($proid,$order_id){  

    $rowState = DB::table('prowise_discount')->select('discount_amount','discount_percent')->where('proid', '=', $proid)->where('orderid', '=', $order_id)->first(); 
    $remarks	  = isset($rowState->remarks) ? $rowState->remarks : '';
    return $remarks;
}


function stock_remarks($ID){  

    $rowState = DB::table('tbl_demo_stock')->select('remarks')->where('stock_id', '=', $ID)->first(); 
    $remarks	  = isset($rowState->remarks) ? $rowState->remarks : '';
    return ucfirst($remarks);
}

function total_demo_stock($ID){ 

    $sqlState = "SELECT *, sum(qty) as product_stock_sum  from tbl_demo_stock where pro_id = '$ID' ";
    $rowState = DB::select(DB::raw($sqlState)); 
    $rowState = $rowState[0];
    $product_stock_sum	  = $rowState->product_stock_sum;
    return $product_stock_sum;
}

function proforma_invoice_total_view($pcode){

    $symbol		= currencySymbol(1);
    $currency1 	= $symbol[0];
    $curValue 	= $symbol[1];
   
    $rowOrderTotal = DB::table('tbl_order')->where('orders_id', '=', $pcode)->first(); 
    
    $subtotal1=0;
    $totalTax = 0;
    $totalCost = 0;
    $subtotal1 = 0;
    $subtotal1_dis = 0;
    
    if(!empty($rowOrderTotal)){
    
    $rsOrderPro = DB::table('tbl_order_product')->where('order_id', '=', $pcode)->get(); 
    $h=0;
    
    
    foreach($rsOrderPro as $rowOrderPro){
    
        $h++;
        $OrderProID	 		= $rowOrderPro->order_pros_id;
        $proID	 	 		= $rowOrderPro->pro_id;
        $groupID 	 		= $rowOrderPro->group_id;
        $orPrice 	 		= $rowOrderPro->pro_price;
        
        $sql_dis_row 		= DB::table('prowise_discount')->where('orderid', '=', $pcode)->where('proid', '=', $proID)->first(); 
        $discount_amount 	= isset($sql_dis_row->discount_amount) ? $sql_dis_row->discount_amount : 0; 
        $discount_percent 				= isset($sql_dis_row->discount_percent) ? $sql_dis_row->discount_percent : 0;

        $subtotal1						= ($orPrice*$rowOrderPro->pro_quantity) - $discount_amount;
        $subtotal1_dis					= $discount_amount;
        $discounted_price				= $orPrice*$discount_percent/100;	

        $per_product_GST_percentage		= $rowOrderPro->GST_percentage;
        @$discounted_price_tax_amt		= ($orPrice-$discounted_price)*$rowOrderPro->GST_percentage/100;			
        $SumProPrice 					= ($orPrice-$discounted_price+$discounted_price_tax_amt)* $rowOrderPro->pro_quantity;
        $totalTax+= $discounted_price_tax_amt * $rowOrderPro->pro_quantity;
        $ProTotalPrice 					= ($discounted_price_tax_amt+$orPrice-$discounted_price)* $rowOrderPro->pro_quantity;
        $freight_amount 				= $rowOrderPro->freight_amount;
        "freight without GST value:".$freight_amount_with_gst = $rowOrderPro->freight_amount/1.18;
        "<br>freight GST value:".$freight_gst_amount = $rowOrderPro->freight_amount-$freight_amount_with_gst;
        $totalCost     					= $totalCost + $ProTotalPrice;    				
    }

    $GST_tax_amt				= GST_tax_amount_on_offer($pcode);		
    $TotalOrder	   				= $rowOrderTotal->total_order_cost;
    $ship		   				= $rowOrderTotal->shipping_method_cost;
    $shippingValue				= $ship;
    $taxValue					= $rowOrderTotal->tax_cost;
    $tax_included				= $rowOrderTotal->tax_included;
    $tax_perc					= $rowOrderTotal->taxes_perc;
    $discount_perc				= $rowOrderTotal->discount_perc;
    $discount_per_amt			= $rowOrderTotal->discount_per_amt;
    $show_discount				= isset($rowOrderTotal->show_discount) ? $rowOrderTotal->show_discount : '';
    
    
    $subtotal_after_discount    = $TotalOrder - $subtotal1_dis;
    if($tax_included=='Excluded')
    {
    @$subtotal_tax       		= (float)$subtotal_after_discount * (float)$tax_perc/100;
    }
    else
    {
    $subtotal_tax       		= $subtotal_after_discount;
    }
    if($tax_included=='Excluded')
    {
    $GrandTotalOrder			= $subtotal_after_discount + $subtotal_tax;
    }
    else
    {
    $GrandTotalOrder			= $subtotal_after_discount + 0;
    }
    //$subtotal_final		= $subtotal_tax;
    $couponDiscount 			= $rowOrderTotal->coupon_discount;
    }
    $show_discount = isset($show_discount) ? $show_discount : '';
    $freight_amount = isset($freight_amount) ? $freight_amount : 0;
    $freight_gst_amount = isset($freight_gst_amount) ? $freight_gst_amount : 0;
    $subtotal1 = isset($subtotal1) ? $subtotal1 : 0;  
    $tax_included =   isset($tax_included) ? $tax_included : ''; 
    $GST_tax_amt =  isset($GST_tax_amt) ? $GST_tax_amt : ''; 
    

    "<table width='50%' border='0' cellpadding='5' cellspacing='0' >";
    /*echo "<tr class='pagehead'>";
    echo "<td colspan='2' class='pad' nowrap  align='left'>Order Total </td>";
    echo "</tr>";*/
    "<tr class='text'>";
    "<td width='45%' class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left ' nowrap align='right'><strong>Sub Total :</strong> </td>";
    "<td width='30%'  align='right' class='tblBorder_invoice_bottom ' nowrap='nowrap'> &nbsp; &nbsp;";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    //number_format($totalCost-$totalTax,2);
    "</td>";
    "</tr>";
    //echo $show_discount;
    if($show_discount=="Yes") {
    "<tr class='text' style='display:none'>";
    "<td  class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><strong>Discount :</strong></td>";
    "<td   align='right'  class='tblBorder_invoice_bottom'  > &nbsp; &nbsp;";
    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";
    number_format($subtotal1_dis,2);
    "</td>";
    "</tr>";
    }

    "<tr class='text'>";

    "<td class='pad tblBorder_invoice_right tblBorder_invoice_bottom tblBorder_invoice_left' nowrap align='right'><strong>Freight Value :</strong></td>";

    "<td align='right'  class='tblBorder_invoice_bottom' nowrap='nowrap' >&nbsp; &nbsp; ";

    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";

    number_format($freight_amount-$freight_gst_amount,2);

    "</td>";

    "</tr>";
    $Grand_subtotal1=$subtotal1;

    if($tax_included=='Excluded' || $GST_tax_amt>0)

    {

    //	echo "</tr>";

    "<tr class='text'>";

    if($GST_tax_amt>0)

    {

    "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><strong>Add IGST :</strong></td>";

    }

    else

    {

    "<td class='pad tblBorder_invoice_bottom tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><strong>Add VAT/CST@ $tax_perc% :</strong></td>";

    }

    "<td align='right'  class='tblBorder_invoice_bottom'  > &nbsp; &nbsp; ";

    if($totalTax>0)

    {

    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";

    number_format($totalTax+$freight_gst_amount,2);//total gst value rumit 


    }

    else

    {

    //echo str_replace("backoffice","crm",$rowOrderTotal->Price_value)." ";

    $subtotal_tax1=$subtotal_tax;

    number_format($subtotal_tax1,2);

    }//echo $subtotal1;

    $Grand_subtotal1=$totalCost;//$subtotal1+$subtotal_tax1+$totalTax;

    "</td>";

    "</tr>";

    }

    "<tr class='text'>";

    "<td class='pad tblBorder_invoice_right tblBorder_invoice_left' nowrap align='right'><h4>Grand Total :</h4></td>";

    "<td align='right'  nowrap='nowrap' ><h4> &nbsp; &nbsp; ";

    $Price_value = isset($rowOrderTotal->Price_value) ? $rowOrderTotal->Price_value : 0;

    str_replace("backoffice","crm",$Price_value)." ";

    //		printf(" %.2f",$TotalOrder);

    return ($totalCost+$freight_amount);

    "</h4></td>";

    "</tr>";

    "</table>";

}

function getTotal_pagesJoin($table_name, $orderby='1=1',$ase='asc',$max_results='1=1', $searchRecord='1=1',$star='*'){

    $sql_total	   = "SELECT COUNT('$star') as Num FROM $table_name  $searchRecord  order by $orderby $ase";
    $row = DB::select(DB::raw($sql_total));
    $row = isset($row[0]) ? $row[0] : $row;
    $total_results = $row->Num;
    $total_pages = ceil($total_results / $max_results); 
    return $total_pages;
}

function get_customers_shipping_company_by_order_id($ID){
    
    $row = DB::table('tbl_order')->select('shipping_company')->where('orders_id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $shipping_company	  = isset($row->shipping_company) ? $row->shipping_company : '';
    return $shipping_company;
}

function get_account_manager_by_order_id($ID){

    $row = DB::table('tbl_order')->select('order_by')->where('orders_id', '=', $ID)->where('deleteflag', '=', 'active')->first(); 
    $order_by	  = isset($row->order_by) ? $row->order_by : '';
    return $order_by;
}


function admin_sub_team_lead($ID)
{   
$sqladmin_accs = "select sub_team_lead from tbl_team where team_id = '$ID' and deleteflag = 'active'"; //exit;
$row = DB::select(DB::raw($sqladmin_accs));  
//        $rsadmin_accs    = $row[0]->sub_team_lead;

//$sub_team_lead	 = $row[0]->sub_team_lead;	
$sub_team_lead	 = isset($row[0]->sub_team_lead) ? $row[0]->sub_team_lead : '';	
if($sub_team_lead=='' || $sub_team_lead=='0')
{
$sub_team_lead="0";
}
return $sub_team_lead;
}

function admin_sub_team_lead2($ID)
{   
$sqladmin_accs = "select sub_team_lead2 from tbl_team where team_id = '$ID' and deleteflag = 'active'";
$row = DB::select(DB::raw($sqladmin_accs));  
//$sub_team_lead	 = $row[0]->sub_team_lead2;	
$sub_team_lead	 = isset($row[0]->sub_team_lead2) ? $row[0]->sub_team_lead2 : '';	
//$sub_team_lead = $rowadmin_accs->sub_team_lead2;	
if($sub_team_lead=='' || $sub_team_lead=='0')
{
$sub_team_lead="0";
}
return $sub_team_lead;
}

function admin_sub_team_lead3($ID)
{   
$sqladmin_accs = "select sub_team_lead3 from tbl_team where team_id = '$ID' and deleteflag = 'active'";
$row = DB::select(DB::raw($sqladmin_accs));  
//$sub_team_lead = $row[0]->sub_team_lead3;	
$sub_team_lead	 = isset($row[0]->sub_team_lead3) ? $row[0]->sub_team_lead3 : '';	
if($sub_team_lead=='' || $sub_team_lead=='0')
{
$sub_team_lead="0";
}
return $sub_team_lead;
}


function indiv_permission_sel_using_IN($page_id,$admin_role,$pcode,$perm_name)
{
$sql_page_check_individual="select GROUP_CONCAT(DISTINCT(assign_perm)) as assign_perm_all  from tbl_admin_access_in_module where page_id='".$page_id."' and admin_role_id='".$admin_role."' and admin_id='".$pcode."' and assign_perm IN (".$perm_name.")";
$rs_page_check_individual=mysqli_query($GLOBALS["___mysqli_ston"],  $sql_page_check_individual);	
$rs_num=mysqli_num_rows($rs_page_check_individual);	
while($row_page_check_individual = mysqli_fetch_object($rs_page_check_individual))
{
      $rows = array($row_page_check_individual->assign_perm_all);
}
return $rows;
}

/****finance ***/

function supply_payment_terms_name_do($ref_source){

    $sql = "select supply_order_payment_terms_name from tbl_supply_order_payment_terms_master where supply_order_payment_terms_id = '$ref_source' and deleteflag = 'active' and supply_order_payment_terms_status='active'";
    $row = DB::select(DB::raw($sql)); 
    $row = isset($row[0]) ? $row[0] : '';  
    $supply_payment_terms_name	  = $row->supply_order_payment_terms_name;
    return $supply_payment_terms_name;
}

function ModeName_do($STvalue){

    if(is_numeric($STvalue)){
        $sqlMode = "select * from tbl_mode_master where mode_id = '$STvalue' and deleteflag = 'active'";
        $rowMode = DB::select(DB::raw($sqlMode)); 
        $rowMode = isset($rowMode[0]) ? $rowMode[0] : ''; 
        $Mode	  = $rowMode->mode_name;
    }else{
        $Mode = $STvalue;
    }
    return ucfirst($Mode);
}