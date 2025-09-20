<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxCreditNoteInvoice extends Model
{
    protected $table = 'tbl_tax_credit_note_invoice';
    protected $guarded=[];
    protected $primaryKey = 'credit_note_invoice_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    
    // Common reusable scopes
public function scopeApproved($query)
{
    return $query->where('invoice_status', 'approved');
}

public function scopeBetweenDates($query, $start, $end)
{
    return $query->whereBetween('credit_invoice_generated_date', [$start, $end]);
}

public function products()
{
    return $this->hasMany(InvoiceProduct::class, 'tax_invoice_id', 'invoice_id');
}
public function services()
{
    return $this->hasMany(CreditNoteInvoiceProduct::class, 'tax_invoice_id', 'invoice_id');
}
}
   