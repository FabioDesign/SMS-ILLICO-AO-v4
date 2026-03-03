<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lg',
        'uid',
        'nif',
        'otp',
        'avatar',
        'email',
        'number',
        'volume',
        'otp_at',
        'status',
        'website',
        'town_id',
        'address',
        'codepin',
        'company',
        'avatar_at',
        'login_at',
        'lastname',
        'password',
        'firstname',
        'password_at',
        'accountyp_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'otp_at' => 'datetime',
        'login_at' => 'datetime',
        'password_at' => 'datetime',
        'password' => 'hashed',
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
    // Génération de Filename unique
    public static function filenameUnique($ext)
    {
        do {
            $alfa = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ0123456789';
            $string = substr(str_shuffle($alfa), 0, 15) . '.' . $ext;
        } while(self::where('avatar', $string)->exists());
        return $string;
    }
    // Liste des villes
    public function town()
    {
        return $this->belongsTo(Town::class);
    }
    // Liste des types de comptes
    public function accountType()
    {
        return $this->belongsTo(AccountType::class, 'accountyp_id');
    }
}
