<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmaillerLog extends Model
{
    protected $table = 'emailler_logs';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 