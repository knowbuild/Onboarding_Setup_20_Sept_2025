<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPoInvoiceNew extends Model
{
    protected $table = 'vendor_po_invoice_new';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 