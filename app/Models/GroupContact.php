<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupContact extends Model
{
    public $table = 'group_contact';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];
    
    public $timestamps = false;
}