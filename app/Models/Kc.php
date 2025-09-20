<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kc extends Model
{
    protected $table = 'tbl_kc';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
