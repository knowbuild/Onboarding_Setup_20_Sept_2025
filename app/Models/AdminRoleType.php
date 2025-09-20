<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRoleType extends Model
{
    protected $table = 'tbl_admin_role_type';
    protected $guarded=[];
    protected $primaryKey = 'admin_role_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
