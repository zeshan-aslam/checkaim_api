<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $table = "partners_alerts";
    public $timestamps = false;

    public function ipDetail()
    {
      return $this->hasMany(P_Ipstats::class, 'id' , 'ipid');
    }
    public function auidDetail()
    {
      return $this->hasMany(P_Auid::class, 'id' , 'auidid');
    }
    public function aiAnalyDetail()
    {
        return $this->hasMany(P_Aianalytics::class , 'orderid' , 'orderid');
    }
    public function reletedAi()
    {
      return $this->hasMany(P_Aianalytics::class , 'id' , 'analyticsid');
    }
}
