<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceOrderComment extends Model
{
    protected $table = 'tbl_service_order_comment';
    protected $guarded=[];
    protected $primaryKey = 'service_order_comment_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 