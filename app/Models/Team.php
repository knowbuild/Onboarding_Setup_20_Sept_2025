<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model 
{
    protected $table = 'tbl_team';
    protected $guarded=[];
    protected $primaryKey = 'team_id';
    public function scopeActive($query)
    {
        return $query->where('deleteflag', 'active');
    }
        public function teamManagerData()
    {
        return $this->belongsTo(User::class,'team_manager','admin_id');
    }
        public function teamLeadData()
    {
        return $this->belongsTo(User::class,'sub_team_lead','admin_id');
    }

}
   