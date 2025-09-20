<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoadShow extends Model
{
    protected $table = 'tbl_road_show';
    protected $guarded=[];
    protected $primaryKey = 'roadshow_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 