<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrderPaymentTermsMaster extends Model
{
    protected $table = 'tbl_service_order_payment_terms_master';
    protected $guarded=[];
    protected $primaryKey = 'service_order_payment_terms_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 