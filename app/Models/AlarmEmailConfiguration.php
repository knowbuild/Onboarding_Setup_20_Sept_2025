<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlarmEmailConfiguration extends Model
{
    protected $table = 'tbl_alarm_email_configuration';
    protected $guarded=[];
    protected $primaryKey = 'alarm_config_id';
    public function scopeActive($query)
{
    return $query->where('deleteflag', 'active');
}

}
 