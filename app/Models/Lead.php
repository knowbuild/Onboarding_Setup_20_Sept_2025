<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model 
{
    protected $table = 'tbl_lead';
    protected $guarded=[];

    protected $primaryKey = 'id';
    public function scopeActive($query)
{
    return $query->where('deleteflag', 'active')->where('status', 'active');
}
    public function enqSource()
{
    return $this->belongsTo(EnqSource::class, 'ref_source', 'enq_source_id');
}

public function customerSegment()
{
    return $this->belongsTo(CustSegment::class, 'cust_segment', 'cust_segment_id');
}

public function orders() {
    return $this->hasMany(Order::class, 'lead_id');
}
public function company()
{
    return $this->belongsTo(Company::class, 'comp_name', 'id');
}

}
    