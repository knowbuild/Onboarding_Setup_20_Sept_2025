<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $table = 'tbl_zones';
    protected $guarded=[];
    protected $primaryKey = 'zone_id';

    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    } 

    public function countriesList()
    {
        return $this->belongsTo(Country::class,'zone_country_id','country_id');
    }
   

}
  