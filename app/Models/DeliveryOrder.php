<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $table = 'tbl_delivery_order';
    protected $guarded=[];

    protected $primaryKey = 'DO_ID';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    
    public function doProducts() {
        return $this->hasMany(DoProduct::class, 'OID','O_Id');
    }
    
    public function deliveryChallan() {
        return $this->hasOne(DeliveryChallan::class, 'O_Id','O_Id');
    }
    
    public function invoice() {
        return $this->hasOne(Invoice::class, 'o_id','O_Id');
    }
    
  
 
    public function order()
    {
        return $this->belongsTo(Order::class, 'O_Id', 'orders_id');
    }
    public function paymentTerms()
    {
        return $this->belongsTo(SupplyOrderPaymentTermsMaster::class, 'Payment_Terms', 'supply_order_payment_terms_id');
    }
    public function accountManager()
    {
        return $this->belongsTo(User::class, 'Prepared_by', 'admin_id');
    }
    
     public function modeMaster()
    {
        return $this->belongsTo(ModeMaster::class, 'mode_id', 'Delivery');
    }

         public function warrantyMaster()
    {
        return $this->belongsTo(WarrantyMaster::class, 'delivery_offer_warranty', 'warranty_id');
    }
}
   