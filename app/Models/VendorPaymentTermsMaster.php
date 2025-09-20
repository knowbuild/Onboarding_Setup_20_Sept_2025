<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPaymentTermsMaster extends Model
{
    protected $table = 'tbl_vendor_payment_terms_master';
    protected $guarded=[];
    protected $primaryKey = 'vendor_payment_terms_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 