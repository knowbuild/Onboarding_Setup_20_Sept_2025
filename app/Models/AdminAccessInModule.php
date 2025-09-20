<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAccessInModule extends Model
{
    protected $table = 'tbl_admin_access_in_module';
    protected $guarded=[];
    protected $primaryKey = 'access_id_emp';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
