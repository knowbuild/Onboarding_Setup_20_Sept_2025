<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'tbl_order';
    protected $guarded=[];

    protected $primaryKey = 'orders_id';
    
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
     
    public function webEnquiryEdit()
    {
        return $this->belongsTo(WebEnquiryEdit::class, 'edited_enq_id', 'id');
    }
 
    public function orderProducts() {
        return $this->hasMany(OrderProduct::class, 'order_id', 'orders_id');
    }
    public function taxInvoices() {
        return $this->hasMany(TaxInvoice::class, 'o_id', 'orders_id');
    }
    
 
public function customer()
{
    return $this->belongsTo(Company::class, 'customers_id', 'id');
}

public function lead()
{
    return $this->belongsTo(Lead::class, 'lead_id', 'id');
}


public function deliveryOrders() {
    return $this->hasMany(DeliveryOrder::class, 'O_Id', 'orders_id');
}

public function vehicle()
{
    return $this->belongsTo(Vehicle::class, 'vehicle_id'); // if a vehicle table exists
}


}  
   