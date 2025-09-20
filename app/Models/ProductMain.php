<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductMain extends Model
{
    protected $table = 'tbl_products';
    protected $primaryKey = 'pro_id';
    protected $guarded = [];

    // Scope for active products
  public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    } 
    // Product entry relationship
    public function productsEntry()
    {
        return $this->hasOne(ProductsEntry::class, 'pro_id', 'pro_id');
    }

    public function indexG2()
{
    return $this->hasOne(IndexG2::class, 'match_pro_id_g2', 'pro_id');
}

   public function pricing()
    {
        return $this->hasMany(ProductsEntry::class, 'pro_id', 'pro_id');
    }

    public function discounts()
    {
        return $this->hasMany(ProQtyMaxDiscountPercentage::class, 'proid', 'pro_id');
    }
    public function category()
{
    return $this->belongsTo(Application::class, 'cate_id', 'application_id');
}

public function typeClass()
{
    return $this->belongsTo(ProductTypeClassMaster::class, 'product_type_class_id', 'product_type_class_id');
}
}


           