<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsitePageModule extends Model
{
    protected $table = 'tbl_website_page_module';
    protected $guarded=[];
    protected $primaryKey = 'module_id';
        public $timestamps = true;
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }

        public function pages()
    {
        return $this->hasMany(WebsitePage::class, 'module_id', 'module_id');
    }
}
   