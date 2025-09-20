<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceApiManager extends Model
{
    protected $table = 'tbl_invoice_api_manager';
    protected $guarded=[];
    protected $primaryKey = 'api_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 