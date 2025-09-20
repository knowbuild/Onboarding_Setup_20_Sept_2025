<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferValidityMaster extends Model
{
    protected $table = 'tbl_offer_validity_master';
    protected $guarded=[];
    protected $primaryKey = 'offer_validity_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 