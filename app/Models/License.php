<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class License extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function durationService()
    {
        return $this->belongsTo(ServiceMaster::class, 'duration','service_id');
    }

     public function product()
    {
        return $this->belongsTo(ProductMain::class, 'product_id','pro_id');
    }
   
public function paymentRecives()
{
    return $this->hasMany(PaymentReceived::class, 'o_id', 'orders_id');
}
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by','admin_id');
    }
        public function paymentTerms()
    {
        return $this->belongsTo(SupplyOrderPaymentTermsMaster::class, 'term_payment_id','supply_order_payment_terms_id');
    }
}


    