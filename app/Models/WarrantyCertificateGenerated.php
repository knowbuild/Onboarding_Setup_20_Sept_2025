<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarrantyCertificateGenerated extends Model
{
    protected $table = 'tbl_warranty_certificate_generated';
    protected $guarded=[];
    protected $primaryKey = 'certificate_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
