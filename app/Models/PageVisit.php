<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageVisit extends Model
{
    protected $table = 'tbl_page_visit';
    protected $guarded=[];
    protected $primaryKey = 'page_visit_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 