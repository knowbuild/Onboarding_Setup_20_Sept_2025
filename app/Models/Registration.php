<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $table = 'tbl_registration';
    protected $guarded=[];
    protected $primaryKey = 'reg_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
