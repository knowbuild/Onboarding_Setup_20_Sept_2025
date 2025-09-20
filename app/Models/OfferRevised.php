<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferRevised extends Model
{
    protected $table = 'tbl_offer_revised';
    protected $guarded=[];
    protected $primaryKey = 'offer_revised_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 