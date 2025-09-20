<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PiComment extends Model
{
    protected $table = 'tbl_pi_comment';
    protected $guarded=[];
    protected $primaryKey = 'pi_comment_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 