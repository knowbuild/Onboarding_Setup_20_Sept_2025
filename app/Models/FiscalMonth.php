<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalMonth extends Model
{
    protected $guarded=[];

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }
    
    public function countries()
    {
        return $this->hasMany(Country::class, 'fiscal_month');
    }
}
  