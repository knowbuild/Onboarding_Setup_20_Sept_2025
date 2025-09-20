<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    protected $table = 'tbl_financial_year';
    protected $guarded=[];

    protected $primaryKey = 'fin_id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
   