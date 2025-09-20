<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceServiceItem extends Model
{
    protected $table = 'tble_invoice_service_items';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 