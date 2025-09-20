<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'tbl_services';
    protected $guarded=[];
    protected $primaryKey = 'service_id';


     // Scope for active products
     public function scopeActive($query)
     {
         return $query->where('status', 'active')
                      ->where('deleteflag', 'active');
     }
 
     // Product entry relationship
     public function serviceEntry()
     {
         return $this->hasOne(ServicesEntry::class, 'service_id', 'service_id');
     }
       public function pricing()
    {
        return $this->hasMany(ServicesEntry::class, 'service_id', 'service_id');
    }

    public function discounts()
    {
        return $this->hasMany(ServiceQtyMaxDiscountPercentage::class, 'serviceid', 'service_id');
    }
        public function category()
{
    return $this->belongsTo(ApplicationService::class, 'cate_id', 'application_service_id');
}

public function typeClass()
{
    return $this->belongsTo(ProductTypeClassMaster::class, 'price_list_type_id', 'product_type_class_id');
}
}
      