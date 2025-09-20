<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Salutation extends Model
{
    protected $table = 'tbl_salutation';
    protected $guarded=[];
    protected $primaryKey = 'salutation_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 