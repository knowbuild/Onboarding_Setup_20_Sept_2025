<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleConfigMaster extends Model
{
    protected $table = 'tbl_module_config_master';
    protected $guarded=[];
    protected $primaryKey = 'module_config_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 