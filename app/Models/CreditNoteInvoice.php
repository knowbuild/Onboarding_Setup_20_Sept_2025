<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditNoteInvoice extends Model
{
    protected $table = 'tbl_tax_credit_note_invoice';
    protected $primaryKey = 'credit_note_id';
    public $timestamps = false;

    protected $fillable = [
        'credit_note_no',
        'credit_note_date',
        'original_invoice_id',
        'original_invoice_no',
        'company_id',
        'person_id',
        'credit_amount',
        'credit_reason',
        'irn_status',
        'irn_no',
        'qr_code',
        'credit_note_status',
        'account_manager',
        'created_by',
        'created_date',
        'updated_date',
        'deleteflag'
    ];

    // Active scope for soft deletes
    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }

    // Get company name
    public function getCompanyNameAttribute()
    {
        return company_names($this->company_id);
    }

    // Get account manager name
    public function getAccountManagerNameAttribute()
    {
        return admin_name($this->account_manager);
    }

    // Get status badge
    public function getStatusBadgeAttribute()
    {
        switch ($this->credit_note_status) {
            case 'approved':
                return 'Approved';
            case 'pending':
                return 'Pending';
            case 'rejected':
                return 'Rejected';
            default:
                return ucfirst($this->credit_note_status);
        }
    }

    // Get IRN status badge
    public function getIrnStatusBadgeAttribute()
    {
        switch ($this->irn_status) {
            case 'ACT':
                return 'ACT';
            case 'N/A':
                return 'N/A';
            default:
                return $this->irn_status;
        }
    }
}
