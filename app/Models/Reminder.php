<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reminder extends Model
{
    protected $guarded=[];
  use SoftDeletes;
  
    public function scopeActive($query) {
        return $query->where('status', 'active');
    }

        // Reminder belongs to a Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Reminder belongs to a User (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
