<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TesManager extends Model
{
    protected $table = 'tbl_tes_manager';
    protected $guarded=[];

    protected $primaryKey = 'ID';

    public function scopeApprovedActive($query)
{
    return $query->where('status', 'approved')
                 ->where('deleteflag', 'active');
}

    public function scopeApproved($query)
{
    return $query->where('status', 'approved');
}
 
public function scopeActive($query)
{
    return $query->where('deleteflag', 'active');
}
 
public function scopeBetweenDates($query, $start, $end)
{
    return $query->whereBetween('received_date', [$start, $end]);
}

public function financialYear() {
    return $this->belongsTo(Order::class, 'financial_year', 'fin_id');
}

public function accountManager () {
    return $this->belongsTo(User::class, 'account_manager'); // Assuming you have a User model
}
}
 