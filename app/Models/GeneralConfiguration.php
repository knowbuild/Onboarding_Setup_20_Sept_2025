<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GeneralConfiguration extends Model
{
    protected $table = 'tbl_general_configuraction';
    protected $guarded=[];
    protected $primaryKey = 'gen_config_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 