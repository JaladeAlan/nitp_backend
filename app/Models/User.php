<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /*************************
     * Roles
     *************************/
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    /*************************
     * Mass Assignment
     *************************/
    protected $fillable = [
        'name',
        'email',
        'password',
        'bank_name',
        'bank_code',
        'account_number',
        'account_name',
        'role', // 'admin' or 'member'
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
        'verification_code',
        'password_reset_code',
    ];

    /*************************
     * Type Casting
     *************************/
    protected $casts = [
        'email_verified_at'               => 'datetime',
        'password'                        => 'hashed',
        'verification_code_expiry'        => 'datetime',
        'password_reset_code_expires_at'  => 'datetime',
    ];

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
     * Email Verification
     *************************/
    public function sendEmailVerificationCode(): void
    {
        $this->verification_code = rand(100000, 999999); // 6-digit numeric code
        $this->verification_code_expiry = now()->addMinutes(30);
        $this->save();

        try {
            Mail::to($this->email)
                ->send(new \App\Mail\VerifyEmailMail($this, $this->verification_code));
        } catch (\Exception $e) {
            Log::error('Failed to send verification email: ' . $e->getMessage());
        }
    }

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(string $code): bool
    {
        if (
            $this->verification_code === $code &&
            $this->verification_code_expiry &&
            now()->lessThanOrEqualTo($this->verification_code_expiry)
        ) {
            $this->update([
                'email_verified_at'        => now(),
                'verification_code'        => null,
                'verification_code_expiry' => null,
            ]);

            return true;
        }

        return false;
    }

    /*************************
     * Role Helpers
     *************************/
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isMember(): bool
    {
        return $this->role === self::ROLE_MEMBER;
    }
}
