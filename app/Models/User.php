<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /*************************
     * Mass Assignment
     *************************/
    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
        'account_number',
        'bank_code',
        'bank_name',
        'account_name',
        'uid',
        'verification_code',
        'verification_code_expiry',
        'password_reset_code',
        'password_reset_code_expires_at',
    ];

    /*************************
     * Hidden Data
     *************************/
    protected $hidden = [
        'password',
        'remember_token',
        'account_number',
        'bank_code',
        'verification_code',
        'password_reset_code',
    ];

    /*************************
     * Type Casting
     *************************/
    protected $casts = [
        'email_verified_at'              => 'datetime',
        'password'                       => 'hashed',
        'verification_code_expiry'       => 'datetime',
        'password_reset_code_expires_at' => 'datetime',
    ];

    /*************************
     * Auto UID Generator
     *************************/
    protected static function booted()
    {
        static::creating(function (User $user) {
            do {
                $user->uid = "USR-" . strtoupper(Str::random(6));
            } while (self::where('uid', $user->uid)->exists());
        });
    }

    /*************************
     * JWT Support
     *************************/
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [];
    }

    /*************************
     * Email Verification Logic
     *************************/
    public function sendEmailVerificationCode(): void
    {
        $this->verification_code = Str::random(6);
        $this->verification_code_expiry = now()->addMinutes(30);
        $this->save();

        try {
            Mail::to($this->email)
                ->send(new \App\Mail\VerifyEmailMail($this, $this->verification_code));
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }
    }

    public function verifyEmail(string $code): bool
    {
        if (
            $this->verification_code === $code &&
            $this->verification_code_expiry &&
            now()->lessThanOrEqualTo($this->verification_code_expiry)
        ) {
            $this->update([
                'email_verified_at'       => now(),
                'verification_code'       => null,
                'verification_code_expiry' => null,
            ]);

            return true;
        }

        return false;
    }

    /*************************
     * Wallet Logic
     *************************/
    public function deposit(float|int $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function withdraw(float|int $amount): bool
    {
        if ($this->balance < $amount) {
            return false;
        }

        $this->decrement('balance', $amount);
        return true;
    }
}
