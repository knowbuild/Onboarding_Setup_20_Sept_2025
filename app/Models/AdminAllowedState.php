<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAllowedState extends Model
{
    protected $table = 'tbl_admin_allowed_state';
    protected $guarded=[];
    protected $primaryKey = 'allowed_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 