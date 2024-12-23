<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'code',
        'expires_at',
        'is_verified'
    ];

    protected $hidden = ['code'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($emailVerification) {
            do {
                $code = random_int(100000, 999999);
            } while (self::where('code', $code)->exists());

            $emailVerification->code = $code;

            $emailVerification->expires_at = Carbon::now()->addMinutes(15);
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
