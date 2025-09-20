<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexG2 extends Model
{
    protected $table = 'tbl_index_g2';
    protected $guarded=[];

    protected $primaryKey = 'match_id_g2';

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
    
    public function application1() {
        return $this->belongsTo(Application::class, 'application_id', 'pro_id');
    }
    public function application()
{
    return $this->belongsTo(Application::class, 'pro_id', 'application_id');
}


}
       