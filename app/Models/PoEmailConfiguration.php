<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoEmailConfiguration extends Model
{
    protected $table = 'tbl_po_email_configuration';
    protected $guarded=[];
    protected $primaryKey = 'po_config_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 