<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Allmonth extends Model
{
    protected $table = 'allmonths';
    protected $guarded=[];
    protected $primaryKey = 'month_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
