<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
 
class InvoiceProduct extends Model
{
    protected $table = 'tbl_invoice_products';
    protected $guarded=[];
    protected $primaryKey = 'tax_pro_id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
      
    public function invoice()
{
    return $this->belongsTo(TaxInvoice::class, 'tax_invoice_id', 'invoice_id');
}
public function product() {
    return $this->belongsTo(ProductMain::class, 'pro_id', 'pro_id');
}
public function taxInvoice() {
    return $this->belongsTo(TaxInvoice::class, 'tax_invoice_id', 'invoice_id');
}
public function indexG2() {
    return $this->hasOne(IndexG2::class, 'match_pro_id_g2', 'pro_id');
}

public function productG2()
{
    
    return $this->belongsTo(IndexG2::class, 'pro_id', 'match_pro_id_g2');
}
}
           