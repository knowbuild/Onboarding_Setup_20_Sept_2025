<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProwiseDiscount extends Model
{
    protected $table = 'prowise_discount';
    protected $guarded=[];
    protected $primaryKey = 'sno';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  