<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class P_Report extends Model
{
    use HasFactory;
    
    protected $table = "partners_reports";
    protected $fillable = ['mid','alertid','orderid','auidid','cannedresponseid','notes'];
    public $timestamps  = false;
}
