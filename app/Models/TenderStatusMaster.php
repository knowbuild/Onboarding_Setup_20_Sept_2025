<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenderStatusMaster extends Model
{
    protected $table = 'tbl_tender_status_master';
    protected $guarded=[];
    protected $primaryKey = 'tender_status_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
