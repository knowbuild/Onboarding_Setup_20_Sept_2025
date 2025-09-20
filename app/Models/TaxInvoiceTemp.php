<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxInvoiceTemp extends Model
{
    protected $table = 'tbl_tax_invoice_temp';
    protected $guarded=[];

    protected $primaryKey = 'invoice_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 