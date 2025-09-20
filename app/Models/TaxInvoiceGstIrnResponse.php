<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxInvoiceGstIrnResponse extends Model
{
    protected $table = 'tbl_tax_invoice_gst_irn_response';
    protected $guarded=[];
    protected $primaryKey = 'gst_irn_response_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
