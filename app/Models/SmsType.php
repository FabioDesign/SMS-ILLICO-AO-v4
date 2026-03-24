<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsType extends Model
{
    public $table = 'sms_type';

    protected $guarded = [];
    
    public $timestamps = false;
}