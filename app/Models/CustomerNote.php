<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerNote extends Model
{
       protected $guarded=[];
  use SoftDeletes;
         public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id','admin_id');
    }
     public function creator()
    {
        return $this->belongsTo(User::class, 'created_by','admin_id');
    }
}
     