<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxInvoice extends Model
{ 
    protected $table = 'tbl_tax_invoice';
    protected $guarded=[];
    protected $primaryKey = 'invoice_id';
  
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active'); 
    }
        
    public function products()
{
    return $this->hasMany(InvoiceProduct::class, 'tax_invoice_id', 'invoice_id');
} 
public function services() 
{
    return $this->hasMany(CreditNoteInvoiceProduct::class, 'tax_invoice_id', 'invoice_id');
}
public function invoiceProduct() {
    return $this->hasMany(InvoiceProduct::class, 'tax_invoice_id', 'invoice_id');
}
public function order() {
    return $this->belongsTo(Order::class, 'o_id', 'orders_id');
}
public function preparedBy() {
    return $this->belongsTo(User::class, 'prepared_by'); // Assuming you have a User model
}
  
public function scopeApproved($query) 
{
    return $query->where('invoice_status', 'approved');
}
 
 
public function scopeBetweenDates($query, $start, $end)
{
    return $query->whereBetween('invoice_generated_date', [$start, $end]);
}

public function paymentReceived()
{
    return $this->hasMany(PaymentReceived::class, 'invoice_id', 'invoice_id');
}

public function paymentTerms()
{
    return $this->belongsTo(SupplyOrderPaymentTermsMaster::class, 'payment_terms', 'supply_order_payment_terms_id');
}

public function taxCreditNoteInvoices()
{
    return $this->hasMany(TaxCreditNoteInvoice::class, 'invoice_id', 'invoice_id');
}
public function creditNotes()
{
    return $this->hasMany(TaxCreditNoteInvoice::class, 'invoice_id', 'invoice_id');
}




}
       