<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;
  
    /**
     * The attributes that are mass assignable.
     * 
     * @var array<int, string> 
     */
    protected $table = 'tbl_admin';
    protected $guarded=[];
    protected $primaryKey = 'admin_id';
    public $timestamps = true;
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the identifier for JWT authentication.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
 
    /**
     * Return custom JWT claims.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [];
    }

    public function scopeActive($query) {
        return $query->where('deleteflag', 'active');
    }

  
   public function department()
    {
        return $this->belongsTo(DepartmentComp::class, 'admin_role_id','department_id');
    }

    // User belongs to a Designation
    public function designation()
    {
        return $this->belongsTo(Designation::class,'admin_designation', 'designation_id');
    }

       public function modulePermissions()
    {
        return $this->belongsTo(AdminAccessInModule::class,'admin_id', 'admin_id');
    }
       public function allowedPMSRules()
    {
        return $this->belongsTo(AdminAllowedState::class,'admin_id', 'admin_id');
    }
                public function employeeRole()
    {
        return $this->belongsTo(AdminAccess::class,'allowed_module', 'role_id');
    }
              public function team()
    {
        return $this->belongsTo(Team::class,'admin_team', 'team_id');
    }
           public function businessFunction()
    {
        return $this->belongsTo(AdminRoleType::class,'admin_role_id', 'admin_role_id');
    }
}
