<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'tbl_notification';
    protected $guarded=[];
    protected $primaryKey = 'notification_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 