<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $table = 'tbl_resume';
    protected $guarded=[];
    protected $primaryKey = 'resume_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 