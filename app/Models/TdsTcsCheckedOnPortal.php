<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TdsTcsCheckedOnPortal extends Model
{
    protected $table = 'tbl_tds_tcs_checked_on_portal';
    protected $guarded=[];
    protected $primaryKey = 'checked_on_portal_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 