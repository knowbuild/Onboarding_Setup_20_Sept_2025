<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'tbl_faq';
    protected $guarded=[];
    protected $primaryKey = 'faq_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 