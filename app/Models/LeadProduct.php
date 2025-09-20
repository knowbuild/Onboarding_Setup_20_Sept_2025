<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadProduct extends Model
{
    protected $table = 'tbl_lead_product';
    protected $guarded=[];
    protected $primaryKey = 'lead_pros_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    
    // In LeadProduct.php
public function application()
{
    return $this->belongsTo(Application::class, 'pro_category', 'application_id');
}

// In LeadProduct.php
public function applicationService()
{
    return $this->belongsTo(ApplicationService::class, 'pro_category', 'application_service_id');
}

}
 