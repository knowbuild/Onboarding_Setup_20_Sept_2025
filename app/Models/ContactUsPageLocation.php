<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactUsPageLocation extends Model
{
    protected $table = 'tbl_contact_us_page_location';
    protected $guarded=[];

    protected $primaryKey = 'id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
