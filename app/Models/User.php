<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Haruncpi\LaravelIdGenerator\IdGenerator;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements MustVerifyEmail, JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'username',
        'name',
        'password',
        'phone_number',
        'email',
        'email_verified_at',
        'status',
        'roles',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'username',
        'role',
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            $user->code = IdGenerator::generate([
                'table' => 'users',
                'field' => 'code',
                'length' => 18,
                'prefix' => 'uid_' . Str::random(14),
                'reset_on_prefix_change' => true,
            ]);
        });
    }

    public function getJWTIdentifier() { 
        return $this->getKey(); 
    }

    public function getJWTCustomClaims() { 
        return []; 
    }

    public function profile() {
        return $this->hasOne(Profile::class);
    }

    public function addresses() {
        return $this->hasMany(Address::class);
    }
    
    public function cart() {
        return $this->hasOne(Cart::class);
    }

    public function orders() {
        return $this->hasMany(Order::class);
    }

    public function emailVerifications() {
        return $this->hasMany(EmailVerification::class);
    }
}
