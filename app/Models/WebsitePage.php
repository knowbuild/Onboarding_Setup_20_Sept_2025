<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePage extends Model
{
    protected $table = 'tbl_website_page';
    protected $guarded=[];
    protected $primaryKey = 'page_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 