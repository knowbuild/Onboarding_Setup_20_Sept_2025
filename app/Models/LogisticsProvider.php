<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticsProvider extends Model
{
    protected $guarded=[];
    protected $table = 'tbl_courier_master';

    protected $primaryKey = 'courier_id'; 
    
    public function scopeActive($query) {
        return $query->where('courier_status', 'active');
    }

}
 

 