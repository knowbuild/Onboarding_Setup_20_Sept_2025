<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VendorConfirmMail extends Model
{
    protected $table = 'tbl_vendor_confirm_mail';
    protected $guarded=[];
    protected $primaryKey = 'vendor_conf_mail_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 