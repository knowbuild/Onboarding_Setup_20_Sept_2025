<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationMaster extends Model
{
    protected $table = 'tbl_application_master';
    protected $guarded=[];
    protected $primaryKey = 'application_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
