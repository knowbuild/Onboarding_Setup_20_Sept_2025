<?php

namespace App\Models\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminUser extends Model
{
    use HasFactory;
    protected $table = 'tbl_admin';
    protected $primaryKey = 'admin_id'; // Define primary key
    protected $fillable = ['admin_fname', 'admin_lname'];

    // Relationship with WebEnquiryEdit
    public function webEnquiryEdits()
    {
        return $this->hasMany(WebEnquiryEdit::class, 'acc_manager', 'admin_id');
    }
}
