<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NumberSms extends Model
{
    public $table = 'numbersms';

    protected $guarded = [];
    
    public $timestamps = false;

    protected $casts = [
        'sending_at' => 'datetime',
        'processing_at' => 'datetime',
    ];

    // Génération de UUID unique
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
