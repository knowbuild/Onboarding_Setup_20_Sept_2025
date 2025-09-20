<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyExtn extends Model
{
    protected $table = 'tbl_company_extn';
    protected $guarded=[];
    protected $primaryKey = 'company_extn_id';
    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active') ->where('company_extn_status', 'active');
    }
}
  