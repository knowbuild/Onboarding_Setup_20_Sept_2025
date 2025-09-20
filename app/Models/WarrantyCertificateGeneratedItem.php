<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyCertificateGeneratedItem extends Model
{
    protected $table = 'tbl_warranty_certificate_generated_items';
    protected $guarded=[];
    protected $primaryKey = 'supply_order_payment_terms_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 