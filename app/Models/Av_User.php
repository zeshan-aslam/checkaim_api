<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;


class Av_User extends Authenticatable
{
    use HasFactory,HasApiTokens;
   
   
    protected $table = "av_users";
    public $timestamps = false;
    protected $guarded =['date'];
    protected $fillable = ['email','password'];
}
