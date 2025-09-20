<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'tble_invoice';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
   