<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventsAward extends Model
{
    protected $table = 'tbl_events_award';
    protected $guarded=[];
    protected $primaryKey = 'events_award_id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
