<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\License;

class PaymentReceived extends Model
{
    protected $table = 'tbl_payment_received';
    protected $primaryKey = 'payment_received_id';
    protected $guarded = [];

    // Scope for active records
    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }

//protected static function booted()
  //  {
   //     static::created(function ($paymentReceived) {
            // Check if license already exists
 //           $exists = License::where('order_id', $paymentReceived->o_id)->exists();

  //          if (!$exists && function_exists('licenseCustomer')) {
  //              licenseCustomer($paymentReceived->payment_received_id);
  //          }
  //      });
  //  }
 
    // Relationships
    public function taxInvoice()
    {
        return $this->belongsTo(TaxInvoice::class, 'invoice_id', 'invoice_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'o_id', 'orders_id');
    }

    public function paymentTypeMaster()
    {
        return $this->belongsTo(PaymentTypeMaster::class, 'payment_received_type', 'payment_remarks_id');
    }

    public function companyBankAddress()
    {
        return $this->belongsTo(CompanyBankAddress::class, 'payment_received_in_bank', 'bank_id');
    }
}
