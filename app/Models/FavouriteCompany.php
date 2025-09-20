<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavouriteCompany extends Model
{
    protected $table = 'tbl_fav_comp';
    protected $guarded=[];
    protected $primaryKey = 'fav_id';
    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }
}
  