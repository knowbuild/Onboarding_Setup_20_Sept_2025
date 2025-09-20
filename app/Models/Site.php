<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $table = 'sites';
    protected $guarded=[];

    protected $primaryKey = 'site_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 