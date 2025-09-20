<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenerateWarrantyItem extends Model
{
    protected $table = 'tbl_generate_warranty_items';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 