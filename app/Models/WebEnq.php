<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebEnq extends Model
{
    protected $table = 'tbl_web_enq';
    protected $guarded=[];
    protected $primaryKey = 'ID';

    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }

    public function edit()
    {
        return $this->hasOne(WebEnqEdit::class, 'enq_id', 'ID');
    }

    public function webEnquiryEdit()
    {
        return $this->hasOne(WebEnqEdit::class, 'enq_id', 'ID');
    }
    
    public function enqSource()
    {
        return $this->belongsTo(EnqSource::class, 'ref_source', 'enq_source_description');
    }
    
public function admin()
{
    return $this->belongsTo(User::class, 'added_by');
}

public function company()
{
    return $this->belongsTo(Company::class, 'company_id');
}
}
    