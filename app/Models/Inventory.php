<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'tbl_inventory';
    protected $guarded=[];
    protected $primaryKey = 'inv_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 