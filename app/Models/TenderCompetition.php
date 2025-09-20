<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenderCompetition extends Model
{
    protected $table = 'tbl_tender_competition';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 