<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tes extends Model
{
    protected $table = 'tbl_tes';
    protected $guarded=[];
    protected $primaryKey = 'ID';
   
    public function event()
{
    return $this->belongsTo(Event::class, 'comp_id', 'customer');
}

}
    