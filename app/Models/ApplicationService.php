<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationService extends Model
{
    protected $table = 'tbl_application_service';
    protected $guarded=[];

    protected $primaryKey = 'application_service_id';

    public function scopeActive($query)
{
    return $query->where('application_service_status', 'active')->where('deleteflag', 'active');
}
}
   