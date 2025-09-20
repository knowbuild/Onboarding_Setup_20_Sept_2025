<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorPoFinal extends Model
{
    protected $table = 'vendor_po_final';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 