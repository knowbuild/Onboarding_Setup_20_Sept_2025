<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeConfiguration extends Model
{
    protected $table = 'tbl_home_configuraction';
    protected $guarded=[];
    protected $primaryKey = 'gen_config_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 