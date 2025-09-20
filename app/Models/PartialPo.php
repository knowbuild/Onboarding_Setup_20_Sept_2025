<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartialPo extends Model
{
    protected $table = 'partial_po';
    protected $guarded=[];
    protected $primaryKey = 'ID';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 