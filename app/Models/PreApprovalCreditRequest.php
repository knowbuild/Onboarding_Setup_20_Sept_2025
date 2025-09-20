<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreApprovalCreditRequest extends Model
{
    protected $table = 'tbl_pre_approval_credit_requests';
    protected $primaryKey = 'request_id';
    public $timestamps = false;

    protected $fillable = [
        'requested_on',
        'approval_for',
        'customer_name', 
        'account_manager',
        'approval_amount',
        'document_ref',
        'status',
        'approved_by',
        'approved_on',
        'rejected_by',
        'rejected_on',
        'remarks',
        'created_by',
        'created_at',
        'updated_at',
        'deleteflag'
    ];

    // Active scope for soft deletes
    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }

    // Get account manager name
    public function getAccountManagerNameAttribute()
    {
        return admin_name($this->account_manager);
    }

    // Get approval for name 
    public function getApprovalForNameAttribute()
    {
        return admin_name($this->approval_for);
    }

    // Get status badge
    public function getStatusBadgeAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'PENDING';
            case 'approved':
                return 'APPROVED'; 
            case 'rejected':
                return 'REJECTED';
            case 'expired':
                return 'EXPIRED';
            case 'revoked':
                return 'REVOKED';
            default:
                return 'UNKNOWN';
        }
    }
}
