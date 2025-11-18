<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordRequest;
use App\Http\Requests\Auth\changePasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\VerifyEmailMail;
use App\Mail\ResetPasswordEmail;
use DB;

class AuthController extends Controller
{
    protected function success($data = [], $msg = 'Success', $status = 200) {
        return response()->json([
            'success' => true,
            'message' => $msg,
            'data' => $data
        ], $status);
    }

    protected function error($msg = 'Error', $status = 400, $errors = []) {
        return response()->json([
            'success' => false,
            'message' => $msg,
            'errors' => $errors
        ], $status);
    }

    public function register(RegisterRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_MEMBER,
            ]);

            $user->sendEmailVerificationCode();

            $token = JWTAuth::fromUser($user);
            DB::commit();

            return $this->success([
                'user' => new UserResource($user),
                'token' => $token
            ], 'Registration successful', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', ['err' => $e->getMessage()]);
            return $this->error('Registration failed', 500);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            $user = User::where('email', $request->email)->first();

            if (!$user) return $this->error('Invalid credentials', 401);
            if (!$user->hasVerifiedEmail()) return $this->error('Please verify your email before logging in.', 403);

            if (!$token = auth('api')->attempt($credentials)) {
                return $this->error('Invalid credentials', 401);
            }

            return $this->success([
                'token' => $token,
                'user' => new UserResource($user)
            ], 'Login successful');
        } catch (\Exception $e) {
            Log::error('Login JWT error', ['err' => $e->getMessage()]);
            return $this->error('Could not create token', 500);
        }
    }

    public function me(Request $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) {
                return $this->error('Unauthorized', 401);
            }

            return $this->success(new UserResource($user), 'User fetched successfully');
        } catch (\Exception $e) {
            Log::error('Fetch me error', ['err' => $e->getMessage()]);
            return $this->error('Failed to fetch user', 500);
        }
    }

    public function logout()
    {
        try {
            auth('api')->logout();
            return $this->success([], 'Logged out');
        } catch (\Exception $e) {
            Log::error('Logout error', ['err' => $e->getMessage()]);
            return $this->error('Logout failed', 500);
        }
    }

    public function refresh()
    {
        try {
            $token = auth('api')->refresh();
            return $this->success(['token' => $token], 'Token refreshed');
        } catch (\Exception $e) {
            return $this->error('Could not refresh token', 500);
        }
    }

    public function sendPasswordResetCode(PasswordRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            if (!$user) return $this->error('User not found', 404);

            $code = rand(100000, 999999);
            $user->password_reset_code = $code;
            $user->password_reset_code_expires_at = now()->addMinutes(30);
            $user->save();

            Mail::to($user->email)->send(new ResetPasswordEmail($user, $code));

            return $this->success([], 'Password reset code sent');
        } catch (\Exception $e) {
            Log::error('Reset code error', ['err' => $e->getMessage()]);
            return $this->error('Failed to send password reset code', 500);
        }
    }

    public function verifyResetCode(PasswordRequest $request)
    {
        try {
            $request->validate(['reset_code' => 'required|string|size:6']);

            $user = User::where('email', $request->email)->first();
            if (!$user) return $this->error('User not found', 404);

            if ($user->password_reset_code !== $request->reset_code || $user->password_reset_code_expires_at->isPast()) {
                return $this->error('Invalid or expired code', 400);
            }

            $user->password_reset_code = null;
            $user->password_reset_code_expires_at = null;
            $user->save();

            return $this->success([], 'Reset code verified');
        } catch (\Exception $e) {
            return $this->error('Failed to verify reset code', 500);
        }
    }

    public function resetPassword(PasswordRequest $request)
    {
        try {
            $request->validate(['password' => 'required|confirmed|min:8']);

            $user = User::where('email', $request->email)->first();
            if (!$user) return $this->error('User not found', 404);

            if ($user->password_reset_code !== null || $user->password_reset_code_expires_at !== null) {
                return $this->error('Please verify the reset code first', 400);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return $this->success([], 'Password reset successful');
        } catch (\Exception $e) {
            return $this->error('Failed to reset password', 500);
        }
    }

  public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = auth('api')->user();
            if (!$user) return $this->error('Unauthorized', 401);

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->error('Current password incorrect', 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return $this->success([], 'Password changed');
        } catch (\Exception $e) {
            return $this->error('Failed to change password', 500);
        }
    }

    public function verifyEmailCode(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'verification_code' => 'required|string|size:6'
            ]);

            $user = User::where('email', $request->email)->first();
            if (!$user) return $this->error('User not found', 404);

            if (!$user->verifyEmailCode($request->verification_code)) {
                return $this->error('Invalid or expired verification code', 400);
            }

            return $this->success([], 'Email verified');
        } catch (\Exception $e) {
            return $this->error('Failed to verify email', 500);
        }
    }

    public function resendVerificationEmail(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $user = User::where('email', $request->email)->first();
            if (!$user) return $this->error('User not found', 404);
            if ($user->hasVerifiedEmail()) return $this->error('Email already verified', 400);

            $user->sendEmailVerificationCode();

            return $this->success([], 'Verification code resent');
        } catch (\Exception $e) {
            return $this->error('Failed to resend verification email', 500);
        }
    }
}
