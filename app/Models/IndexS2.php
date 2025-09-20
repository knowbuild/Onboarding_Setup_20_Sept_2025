<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexS2 extends Model
{
    protected $table = 'tbl_index_s2';
    protected $guarded=[];

    
    protected $primaryKey = 'match_id_s2';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    
    public function applicationService()
    {
        return $this->belongsTo(ApplicationService::class, 'service_id', 'application_service_id');
    }
}
  