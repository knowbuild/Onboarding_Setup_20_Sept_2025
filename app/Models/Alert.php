<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = 'alert';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
