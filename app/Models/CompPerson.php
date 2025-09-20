<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompPerson extends Model
{
    protected $table = 'tbl_comp_person';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
   