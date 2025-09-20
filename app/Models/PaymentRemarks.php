<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentRemarks extends Model
{
    protected $table = 'tbl_payment_remarks';
    protected $guarded=[];
    protected $primaryKey = 'payment_remarks_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 