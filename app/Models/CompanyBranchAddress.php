<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyBranchAddress extends Model
{
    protected $table = 'tbl_company_branch_address';
    protected $guarded=[];
    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
