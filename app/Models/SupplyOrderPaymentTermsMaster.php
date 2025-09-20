<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyOrderPaymentTermsMaster extends Model
{
    protected $table = 'tbl_supply_order_payment_terms_master';
    protected $guarded=[];
    protected $primaryKey = 'supply_order_payment_terms_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  