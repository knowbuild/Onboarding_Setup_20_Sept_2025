<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jobs extends Model
{
    protected $table = 'tbl_jobs';
    protected $guarded=[];
    protected $primaryKey = 'job_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 