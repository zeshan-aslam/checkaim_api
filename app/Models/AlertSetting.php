<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlertSetting extends Model
{
    use HasFactory;
    protected $table = "partners_alertsettings";
    protected $fillable = ['mid','interactcount','searchprevtransterm','aov_threshold','order_amount'];
    public $timestamps = false;
}
