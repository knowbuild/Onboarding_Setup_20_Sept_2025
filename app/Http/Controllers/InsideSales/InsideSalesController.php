<?php

namespace App\Http\Controllers\InsideSales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{
    Order, Application, ProductMain, EnqSource, Lead, Company, User, WebEnq, WebEnqEdit, CompanyExtn, PerformaInvoice, Event
};
use Carbon\Carbon;

class InsideSalesController extends Controller
{
    public function insideSalesListing(Request $request)
    {
        $today = now();
        $start_date = $request->date_from;
        $end_date = $request->date_to;

        // Enquiry responded date filter
        match ($request->enquiry_responded) {
            '1' => [$start_date, $end_date] = [$today->copy()->subDays(1)->toDateString(), $today->toDateString()],
            '3' => [$start_date, $end_date] = [$today->copy()->subDays(3)->toDateString(), $today->toDateString()],
            '5' => [$start_date, $end_date] = [$today->copy()->subDays(5)->toDateString(), $today->toDateString()],
            '0' => [$start_date, $end_date] = [null, null] && $request->merge(['lead_created' => 'Yes']),
            default => null
        };

        // Main query with relationships
        $query = WebEnq::with([
            'enqSource',
            'webEnquiryEdit' => fn($q) => $this->applyEditFilters($q, $request),
            'webEnquiryEdit.lead.company.companyExtn',
            'webEnquiryEdit.order' => fn($q) => $this->applyOrderFilters($q, $request),
            'webEnquiryEdit.application',
            'webEnquiryEdit.admin.designation',
            'webEnquiryEdit.segment',
        ])
        ->when($request->dead_duck, fn($q) => $q->where('dead_duck', $request->dead_duck))
        ->when($request->enq_id, fn($q) => $q->where('ID', $request->enq_id))
        ->when($start_date && $end_date, fn($q) => $q->whereBetween('Enq_Date', [$start_date, $end_date]))
        ->active();

        $today = now();
        $todayStart = $today->copy()->startOfDay()->toDateTimeString();
        $todayEnd = $today->copy()->endOfDay()->toDateTimeString();
        
        $todayEnqCount = WebEnq::with([
            'enqSource',
            'webEnquiryEdit' => fn($q) => $this->applyEditFilters($q, $request),
            'webEnquiryEdit.lead.company.companyExtn',
            'webEnquiryEdit.order' => fn($q) => $this->applyOrderFilters($q, $request),
            'webEnquiryEdit.application',
            'webEnquiryEdit.admin.designation',
            'webEnquiryEdit.segment',
        ])
        ->when($request->dead_duck, fn($q) => $q->where('dead_duck', $request->dead_duck))
        ->when($request->enq_id, fn($q) => $q->where('ID', $request->enq_id))
        ->whereBetween('Enq_Date', [$todayStart, $todayEnd])
        ->active()
        ->count();
        
        // Pagination
        $page = $request->pageno ?? 1;
        $perPage = $request->records ?? 100;
        $enquiries = $query->paginate($perPage, ['*'], 'page', $page);

        // Transform data
        $mapped = $enquiries->getCollection()->map(fn($enq) => $this->transformEnquiry($enq));

        return response()->json([
            'enquiry_data' => $mapped,
            'export_enquiry_data' => '',
            'num_rows_count' => $enquiries->total(),
            'assigned_today' => $todayEnqCount,
            'page' => $enquiries->currentPage(),
            'last_page' => $enquiries->lastPage(),
        ]);
    }

    protected function applyEditFilters($query, $request)
    {
        return $query->when($request->acc_manager, fn($q) => $q->where('acc_manager', $request->acc_manager))
            ->when($request->cust_segment, fn($q) => $q->where('cust_segment', $request->cust_segment))
            ->when($request->enq_stage, fn($q) => $q->where('enq_stage', $request->enq_stage))
            ->when($request->ref_source, fn($q) => $q->where('ref_source', $request->ref_source))
            ->when($request->product_category, fn($q) => $q->where('product_category', $request->product_category))
            ->when($request->hot_enquiry, fn($q) => $q->where('hot_enquiry', $request->hot_enquiry))
            ->when($request->lead_assigned_by, fn($q) => $q->where('assigned_by', $request->lead_assigned_by))
            ->when($request->search_by, function ($q) use ($request) {
                $term = $request->search_by;
                $q->where(fn($query) => $query
                    ->where('Cus_name', $term)
                    ->orWhere('Cus_email', $term)
                    ->orWhere('Cus_mob', $term));
            })
            ->when($request->offer_created === 'Yes', fn($q) => $q->where('order_id', '!=', 0))
            ->when($request->offer_created === 'No', fn($q) => $q->where('order_id', 0))
            ->when($request->lead_created === 'Yes', fn($q) => $q->where('lead_id', '!=', 0))
            ->when($request->lead_created === 'No', fn($q) => $q->where('lead_id', 0))
            ->when($request->last_updated_on === 'uil15d', fn($q) => $q->where('mel_updated_on', '>', now()->subDays(15)))
            ->when($request->last_updated_on === 'nuil15d', fn($q) => $q->where('mel_updated_on', '>', now()->subDays(115)));
    }

    protected function applyOrderFilters($query, $request)
    {
        return $query->when($request->hot_enquiry, fn($q) => $q->where('hot_offer', $request->hot_enquiry))
            ->when($request->order_no, fn($q) => $q->where('orders_id', $request->order_no));
    }

    protected function transformEnquiry($enq)
    {
        $edit = $enq->webEnquiryEdit;
        $lead = $edit?->lead;
        $order = $edit?->order;
        $company = $lead?->company;
        $companyExtn = $company?->companyExtn;
        $admin = $edit?->admin;
        $designation = $admin?->designation;
        $segment = $edit?->segment;

        $companyFullName = in_array($companyExtn?->company_extn_id, [5, 6])
            ? $company?->comp_name
            : $company?->comp_name . ' ' . $companyExtn?->company_extn_name;

        $days_since_offer = $order && $edit?->Enq_Date && $order->time_ordered
            ? floor((strtotime($order->time_ordered) - strtotime($edit->Enq_Date)) / 86400)
            : 0;

        $orders_id = $edit?->order_id;

        $product_items_details = [];
        $offer_details = [];
        $pi_id = "0";
        $performa_invoice_details = [];
        $customer_sales_cycle_duration = 0;
        $tasks_details_array = [];
        $track_image_tooltip = "On Track";
        $track_image = "ontrack.png";

        if (!empty($orders_id)) {
            $product_items_details = Order::find($orders_id)?->orderProducts()->pluck('pro_name') ?? collect();
            $offer_details = Order::active()->where('orders_id', $orders_id)->first();
            $pi_id = PerformaInvoice::active()->where('O_Id', $orders_id)->value('pi_id') ?? "0";
            $performa_invoice_details = PerformaInvoice::active()->find($pi_id) ?? [];

            $customer_id = $order?->customers_id;

            $avg_days = Order::with('taxInvoices')
                ->where('customers_id', $customer_id)
                ->get()
                ->flatMap(fn($order) =>
                    $order->taxInvoices->map(fn($invoice) =>
                        (strtotime($invoice->invoice_generated_date) - strtotime($order->date_ordered)) / 86400
                    )
                )->avg();

            $customer_sales_cycle_duration = round($avg_days ?? 15);

            $tasks_details_array = Event::active()
                ->where('lead_type', $orders_id)
                ->where('status', 'Pending')
                ->whereDate('start_event', '>=', now())
                ->orderBy('start_event')
                ->orderBy('id')
                ->get();

            $sales_cycle_duration = 30;

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
        }

        return [
            'company_full_name' => $companyFullName,
            'ID' => $enq->ID,
            'Cus_name' => $enq->Cus_name,
            'Cus_email' => $enq->Cus_email,
            'Cus_mob' => $enq->Cus_mob,
            'ref_source' => $enq->ref_source,
            'product_category' => $edit?->product_category,
            'Enq_Date' => $enq->Enq_Date,
            'enq_stage' => $edit?->enq_stage,
            'acc_manager' => $edit?->acc_manager,
            'enq_source_name' => $enq->enqSource?->enq_source_name,
            'hot_enquiry' => $edit?->hot_enquiry,
            'mel_updated_on' => $edit?->mel_updated_on,
            'days_since_offer' => $days_since_offer,
            'city' => $edit?->city,
            'state' => $edit?->state,
            'cust_segment_name' => $segment?->cust_segment_name,
            'application_name' => $edit?->application?->application_name,
            'admin_name' => $admin?->admin_fname . ' ' . $admin?->admin_lname,
            'acc_manager_designation_name' => $designation?->designation_name,
            'proforma_invoice_id' => $pi_id,
            'offer_data' => $offer_details,
            'product_items_details' => $product_items_details,
            'performa_invoice_details' => $performa_invoice_details,
            'offer_task_details' => $tasks_details_array,
            'sales_cycle_duration' => 30,
            'customer_sales_cycle_duration' => $customer_sales_cycle_duration,
            'track_image_tooltip' => $track_image_tooltip,
            'track_image' => $track_image,
        ];
    }
}
