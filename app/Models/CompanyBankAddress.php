<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBankAddress extends Model
{
    protected $table = 'tbl_company_bank_address';
    protected $guarded=[];
    protected $primaryKey = 'bank_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 