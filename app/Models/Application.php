<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model; 

class Application extends Model
{
    protected $table = 'tbl_application';
    protected $guarded=[];

    protected $primaryKey = 'application_id';

    public function scopeActive($query)
    {
        return $query->where('application_status', 'active')->where('deleteflag', 'active');
    }
}
      