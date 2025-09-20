<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    protected $table = 'tbl_news';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 