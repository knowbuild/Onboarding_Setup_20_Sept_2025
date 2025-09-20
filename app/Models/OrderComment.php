<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderComment extends Model
{
    protected $table = 'tbl_order_comment';
    protected $guarded=[];
    protected $primaryKey = 'order_comment_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 