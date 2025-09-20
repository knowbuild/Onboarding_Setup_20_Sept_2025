<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventHistory extends Model
{
    protected $table = 'events_history';
    protected $guarded=[];

    protected $primaryKey = 'id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 