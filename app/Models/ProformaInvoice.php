<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProformaInvoice extends Model
{
    protected $table = 'tbl_performa_invoice';
    protected $primaryKey = 'pi_id';
    public $timestamps = false;

    protected $fillable = [
        'offer_id',
        'pi_no',
        'pi_date',
        'company_id',
        'person_id',
        'pi_status',
        'pi_amount',
        'advance_received',
        'advance_received_date',
        'advance_received_via',
        'advance_received_bank',
        'pi_validity_date',
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
        switch ($this->pi_status) {
            case 'pending':
                return 'Pending';
            case 'approved':
                return 'Approved';
            case 'rejected':
                return 'Rejected';
            default:
                return ucfirst($this->pi_status);
        }
    }

    // Check if payment received
    public function getPaymentStatusAttribute()
    {
        if ($this->advance_received > 0) {
            return 'Full Payment received';
        }
        return 'Payment Pending';
    }
}
