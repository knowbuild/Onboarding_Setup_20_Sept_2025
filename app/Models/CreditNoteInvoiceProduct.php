<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteInvoiceProduct extends Model
{
    protected $table = 'tbl_credit_note_invoice_products';
    protected $guarded=[];

    protected $primaryKey = 'cn_tax_pro_id';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    
    public function productS2()
{
    return $this->belongsTo(IndexS2::class, 'pro_id', 'match_service_id_s2');
}
}
  