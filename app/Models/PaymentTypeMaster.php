<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentTypeMaster extends Model
{
    protected $table = 'tbl_payment_type_master';
    protected $guarded=[];
    protected $primaryKey = 'payment_remarks_id';
    
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  