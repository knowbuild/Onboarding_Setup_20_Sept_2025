<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleMaster extends Model
{
    protected $table = 'tbl_module_master';
    protected $guarded=[];
    protected $primaryKey = 'module_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  