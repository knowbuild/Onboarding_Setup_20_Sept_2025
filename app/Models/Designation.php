<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Designation extends Model
{
    protected $table = 'tbl_designation';
    protected $guarded=[];

    protected $primaryKey = 'designation_id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  