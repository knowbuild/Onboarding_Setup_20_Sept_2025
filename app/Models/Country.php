<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded=[];
    protected $table = 'tbl_country';

    protected $primaryKey = 'country_id'; 
    
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
     
   public function currencyList()
    {
        return $this->belongsTo(Currency::class,'currency','id');
    }
      

    public function fiscalMonth()
    {
        return $this->belongsTo(FiscalMonth::class, 'fiscal_month');
    }
    public function country()
{
    return $this->belongsTo(State::class, 'zone_country_id', 'country_id');
}

}
 

 