<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SnoozeDaysMaster extends Model
{
    protected $table = 'tbl_snooze_days_master';
    protected $guarded=[];
    protected $primaryKey = 'snooze_days_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 