<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $table = 'links';
    protected $guarded=[];
    protected $primaryKey = 'link_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 