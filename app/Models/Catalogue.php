<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Catalogue extends Model
{
    protected $table = 'tbl_catalogues';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 