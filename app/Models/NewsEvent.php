<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsEvent extends Model
{
    protected $table = 'tbl_news_event';
    protected $guarded=[];
    protected $primaryKey = 'news_event_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 