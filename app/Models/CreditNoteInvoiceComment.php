<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteInvoiceComment extends Model
{
    protected $table = 'tbl_credit_note_invoice_comment';
    protected $guarded=[];
    protected $primaryKey = 'invoice_comment_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
