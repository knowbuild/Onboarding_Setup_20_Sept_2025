<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KeyCustomerMaster extends Model
{
    protected $table = 'tbl_key_customer_master';
    protected $guarded=[];
    protected $primaryKey = 'key_customer_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 