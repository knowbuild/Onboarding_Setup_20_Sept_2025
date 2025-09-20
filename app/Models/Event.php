<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $table = 'events';
    protected $guarded=[];

    protected $primaryKey = 'id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active')->where('status', 'Completed');
    }

    public function taskTypes()
{
    return $this->hasMany(TaskTypeMaster::class, 'tasktype_abbrv', 'evttxt');
}

public function customer()
{
    return $this->belongsTo(Company::class, 'customer', 'id');
}

}
     