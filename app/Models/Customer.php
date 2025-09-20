<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $guarded=[];

    public function scopeActive($query) {
        return $query->where('status', 'active');
    }
 
    // Customer's general country
    public function countryRelation()
    {
    return $this->belongsTo(Country::class, 'country', 'country_id');
    }
  
    // Customer's currency
    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    // Fiscal year
    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class, 'fiscal_year_id');
    }

    // Revenue per year
    public function revenuePerYear()
    {
        return $this->belongsTo(RevenuePerYear::class, 'revenue_per_year_id');
    }

    // Company location
    public function companyCountry()
    {
        return $this->belongsTo(Country::class, 'company_country_id', 'country_id');
    }

    public function companyState()
    {
        return $this->belongsTo(State::class, 'company_state_id','zone_id');
    }

    public function companyCity()
    {
        return $this->belongsTo(City::class, 'company_city_id','city_id');
    }

    // Purchase location
    public function purchaseCountry()
    {
        return $this->belongsTo(Country::class, 'purchase_country_id');
    }

    public function purchaseState()
    {
        return $this->belongsTo(State::class, 'purchase_state_id');
    }

    public function purchaseCity()
    {
        return $this->belongsTo(City::class, 'purchase_city_id');
    }

    public function customerUsers()
{
    return $this->hasMany(User::class, 'customer_id');
}
public function contacts()
{
    return $this->hasMany(CustomerContact::class, 'tenant_id');
}

    public function accountManagers()
{
    return $this->belongsTo(User::class, 'account_manger_id','admin_id');
}
   public function source()
{
    return $this->belongsTo(EnqSource::class, 'source_id','enq_source_id');
} 
public function favourite()
{
    return $this->hasMany(Favourite::class, 'customer_id');
}



    public function orders()
{
    return $this->hasMany(Order::class, 'customer_id');
}

public function inquiry()
{
    return $this->hasMany(Inquiry::class, 'customer_id');
}

 public function segment()
    {
        return $this->belongsTo(CustSegment::class, 'segment_id');
    }

      public function onboardingProgres()
    {
        return $this->hasMany(OnboardingProgres::class, 'customer_id');
    }
    
    public function notesRelation()
    {
        return $this->hasMany(CustomerNote::class, 'customer_id');
    }
       public function licenseRelation()
    {
        return $this->hasMany(License::class, 'customer_id');
    }
           public function reminderRelation()
    {
        return $this->hasMany(Reminder::class, 'customer_id');
    }

       public function saleOfferTemplate()
        {
        return $this->belongsTo(SaleOfferTemplate::class, 'customer_id');
        } 


    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id');
    }
  public function customerBank()
    {
        return $this->belongsTo(CustomerBank::class, 'tenant_id');
    }
}
