<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustSegment extends Model
{
    protected $table = 'tbl_cust_segment';
    protected $guarded=[];
    protected $primaryKey = 'cust_segment_id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active')->where('cust_segment_status', 'active');
    }
    
}
     