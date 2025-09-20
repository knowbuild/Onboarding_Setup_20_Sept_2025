<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model 
{
    protected $table = 'tbl_comp';
    protected $guarded=[];


    protected $primaryKey = 'id';

      // Scope for active status
      public function scopeActive($query)
      {
          return $query->where('status', 'active')->where('deleteflag', 'active');
      }
       
    public function custSegment()
{
    return $this->belongsTo(CustSegment::class, 'cust_segment', 'cust_segment_id');
}
public function comPerson()
{
    return $this->hasOne(CompPerson::class, 'company_id', 'id');
}

public function companyExtn()
{
    return $this->belongsTo(CompanyExtn::class, 'co_extn_id', 'company_extn_id');
}

public function custSegmentRelation()
{
    return $this->belongsTo(CustSegment::class, 'cust_segment', 'cust_segment_id');
}


}
   