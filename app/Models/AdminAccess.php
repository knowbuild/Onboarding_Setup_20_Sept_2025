<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminAccess extends Model
{
    protected $table = 'tbl_admin_access';
    protected $guarded=[];
    protected $primaryKey = 'access_id';
    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }
     public function page()
    {
        return $this->belongsTo(WebsitePage::class, 'page_id', 'page_id');
    }

    public function module()
    {
        return $this->belongsTo(WebsitePageModule::class, 'module_id', 'module_id');
    }
      public function accessrole()
    {
        return $this->belongsTo(AdminRoleType::class, 'role_id','admin_role_id'); 
    }

}
 