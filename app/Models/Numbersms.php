<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Numbersms extends Model
{
    public $table = 'numbersms';

    protected $fillable = [
        'uid',
        'status',
        'number',
        'volume_sms',
        'bodysms_id',
        'sending_at',
    ];
    
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sending_at' => 'datetime',
    ];

    // Génération de UUID unique
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uid)) {
                $model->uid = Str::uuid()->toString();
            }
        });
    }    
}
