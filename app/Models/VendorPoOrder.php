<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPoOrder extends Model
{
    protected $table = 'vendor_po_order';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 