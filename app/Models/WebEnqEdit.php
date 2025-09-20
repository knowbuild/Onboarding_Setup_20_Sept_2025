<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class WebEnqEdit extends Model
{
    protected $table = 'tbl_web_enq_edit';
    protected $guarded=[];
    protected $primaryKey = 'ID';

    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }

    public function enquiry() 
    {
        return $this->belongsTo(WebEnq::class, 'enq_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'country', 'country_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state', 'zone_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city', 'city_id');
    }
 
    public function segment()
    {
        return $this->belongsTo(CustSegment::class, 'cust_segment', 'cust_segment_id');
    }
 
    public function productCategory()
    {
        return $this->belongsTo(Application::class, 'product_category', 'application_id');
    }

     // Relationship to tbl_order
     public function orders()
     {
         return $this->hasMany(Order::class, 'order_id', 'order_id');
     }

     public function admin()
    {
        return $this->belongsTo(User::class, 'acc_manager', 'admin_id');
    }

    public function lead()
{
    return $this->belongsTo(Lead::class, 'lead_id', 'id');
}

public function order()
{
    return $this->hasOne(Order::class, 'edited_enq_id', 'ID');
}

public function application()
{
    return $this->belongsTo(Application::class, 'product_category', 'application_id');
}

}
  