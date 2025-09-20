<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpportunityValueMaster extends Model
{
    protected $table = 'tbl_opportunity_value_master';
    protected $guarded=[];
    protected $primaryKey = 'opportunity_value_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 