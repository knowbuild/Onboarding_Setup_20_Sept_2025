<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Currency;

class CustomerTradePrice extends Model
{
    protected $guarded=[];

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency', 'id');
    }
     
}
    