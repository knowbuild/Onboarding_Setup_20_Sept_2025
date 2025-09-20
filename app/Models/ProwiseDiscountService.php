<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProwiseDiscountService extends Model
{
    protected $table = 'prowise_discount_service';
    protected $guarded=[];

    protected $primaryKey = 'sno';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  