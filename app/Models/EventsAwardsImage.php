<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventsAwardsImage extends Model
{
    protected $table = 'tbl_events_awards_images';
    protected $guarded=[];
    protected $primaryKey = 'event_img_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 