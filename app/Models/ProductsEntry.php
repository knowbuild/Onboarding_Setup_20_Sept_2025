<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductsEntry extends Model
{
    protected $table = 'tbl_products_entry';
    protected $guarded=[];
    protected $primaryKey = 'pro_id_entry';

    public function product()
    {
        return $this->belongsTo(ProductMain::class, 'pro_id', 'pro_id');
    }
    public function scopeActive($query)
{
    return $query->where('status', 'active')
                 ->where('deleteflag', 'active');
}
}
     