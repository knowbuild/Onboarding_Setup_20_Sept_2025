<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicesEntry extends Model
{
    protected $table = 'tbl_services_entry';
    protected $guarded=[];
    protected $primaryKey = 'service_id_entry';


   
    public function scopeActive($query)
{
    return $query->where('status', 'active')
                 ->where('deleteflag', 'active');
}
public function service()
{
    return $this->belongsTo(Service::class, 'service_id', 'service_id');
}
}
 