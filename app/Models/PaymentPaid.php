<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentPaid extends Model
{
    protected $table = 'tbl_payment_paid';
    protected $guarded=[];
    protected $primaryKey = 'payment_paid_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 