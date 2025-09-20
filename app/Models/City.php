<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $table = 'all_cities';
    protected $guarded=[];
    protected $primaryKey = 'city_id';

    public function scopeActive($query) 
    {
        return $query->where('deleteflag', 'active')->where('status', 'active');
    }
        public function stateList()
    {
        return $this->belongsTo(State::class,'city_id','state_code');
    }
  
}
  