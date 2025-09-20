<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OnboardingImages extends Model
{
    protected $table = 'tbl_onboarding_images';
    protected $guarded=[];
    protected $primaryKey = 'image_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
 