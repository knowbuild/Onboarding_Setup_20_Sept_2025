<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PerformaInvoice extends Model
{
    protected $table = 'tbl_performa_invoice';
    protected $guarded=[];
    protected $primaryKey = 'pi_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
  