<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prefix extends Model
{
    public $table = 'prefix';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    
    public $timestamps = false;
}