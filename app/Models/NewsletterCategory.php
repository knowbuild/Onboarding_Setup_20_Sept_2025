<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterCategory extends Model
{
    protected $table = 'tbl_newsletter_category';
    protected $guarded=[];
    protected $primaryKey = 'news_cat_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 