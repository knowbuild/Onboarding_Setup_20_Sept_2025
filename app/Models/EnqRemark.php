<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnqRemark extends Model
{
    protected $table = 'tbl_enq_remarks';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 