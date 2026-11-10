<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BodySms extends Model
{
    public $table = 'bodysms';

    protected $guarded = [];

    protected $casts = [
        'sending_at' => 'datetime',
    ];

    public function smstype()
    {
        return $this->belongsTo(SmsType::class, 'smstype_id');
    }

    public function numbers()
    {
        return $this->hasMany(NumberSms::class, 'bodysms_id');
    }

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
