<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Redirection extends Model
{
    protected $table = 'tbl_redirection';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 