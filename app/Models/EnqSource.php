<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnqSource extends Model
{
    protected $table = 'tbl_enq_source';
    protected $guarded=[];
    protected $primaryKey = 'enq_source_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  