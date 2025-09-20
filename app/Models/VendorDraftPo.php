<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorDraftPo extends Model
{
    protected $table = 'vendor_draft_po';
    protected $guarded=[];
    protected $primaryKey = 'draft_po_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 