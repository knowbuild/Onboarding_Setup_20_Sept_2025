<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPoInvoice extends Model
{
    protected $table = 'vendor_po_invoice_new';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 