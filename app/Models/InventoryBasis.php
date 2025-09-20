<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryBasis extends Model
{
    protected $table = 'tbl_inventory_basis';
    protected $guarded=[];
    protected $primaryKey = 'inventory_basis_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 