<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPoItem extends Model
{
    protected $table = 'vendor_po_item';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 