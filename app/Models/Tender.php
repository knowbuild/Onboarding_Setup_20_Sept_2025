<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tender extends Model
{
    protected $table = 'tbl_tender';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 