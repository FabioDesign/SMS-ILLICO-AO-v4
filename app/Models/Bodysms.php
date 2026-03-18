<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Bodysms extends Model
{
    public $table = 'bodysms';

    protected $fillable = [
        'uid',
        'pages',
        'chars',
        'status',
        'sender',
        'user_id',
        'message',
        'volume_sms',
        'smstype_id',
        'sending_at',
    ];

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
