<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VendorPaymentDispute extends Model
{
    use HasFactory;

    protected $table = 'tbl_vendor_payment_disputes';
    protected $primaryKey = 'dispute_id';
    public $timestamps = true;

    protected $fillable = [
        'vendor_invoice_id',
        'po_id',
        'vendor_id',
        'dispute_type',
        'dispute_status',
        'dispute_reason',
        'disputed_amount',
        'updated_by',
        'resolution_notes',
        'priority'
    ];

    protected $casts = [
        'disputed_amount' => 'decimal:2'
    ];

    protected $attributes = [
        'dispute_status' => 'active',
        'dispute_type' => 'full_payment',
        'priority' => 'medium'
    ];

    /**
     * Scope to get only active records
     */
    public function scopeActive($query)
    {
        return $query->where('dispute_status', 'active');
    }

    /**
     * Relationship with VendorPoInvoice model
     */
    public function vendorInvoice()
    {
        return $this->belongsTo(VendorPoInvoice::class, 'vendor_invoice_id', 'ID');
    }

    /**
     * Get the admin who updated the dispute
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'admin_id');
    }

    /**
     * Check if dispute is active
     */
    public function isActive()
    {
        return $this->dispute_status === 'active';
    }

    /**
     * Check if dispute is resolved
     */
    public function isResolved()
    {
        return $this->dispute_status === 'resolved';
    }

    /**
     * Mark dispute as resolved
     */
    public function markAsResolved($adminId, $resolutionNotes = null)
    {
        $this->update([
            'dispute_status' => 'resolved',
            'resolution_notes' => $resolutionNotes,
            'updated_by' => $adminId,
            'updated_at' => now()
        ]);
    }

    /**
     * Get formatted dispute amount
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->disputed_amount, 2);
    }
}
