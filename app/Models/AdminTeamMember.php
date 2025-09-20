<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminTeamMember extends Model
{
    protected $table = 'tbl_admin_team_members';
    protected $guarded=[];
    protected $primaryKey = 'tbl_admin_team_member_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
}
