<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TablEnquiry extends Model
{
    protected $table = 'tbl_enquiry';
    protected $guarded=[];
    protected $primaryKey = 'enquiry_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 