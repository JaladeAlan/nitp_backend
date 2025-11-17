<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\PasswordRequest;
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
        return response()->json(['success' => true, 'message' => $msg, 'data' => $data], $status);
    }

    protected function error($msg = 'Error', $status = 400, $errors = []) {
        return response()->json(['success' => false, 'message' => $msg, 'errors' => $errors], $status);
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_MEMBER,
            ]);

            $user->sendEmailVerificationCode();

            $token = JWTAuth::fromUser($user);
            DB::commit();

            return $this->success(['user' => new UserResource($user), 'token' => $token], 'Registration successful', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Registration failed', ['err' => $e->getMessage()]);
            return $this->error('Registration failed', 500);
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $request->email)->first();

        if (!$user) return $this->error('Invalid credentials', 401);
        if (!$user->hasVerifiedEmail()) return $this->error('Please verify your email before logging in.', 403);

        try {
            if (!$token = auth('api')->attempt($credentials)) {
                return $this->error('Invalid credentials', 401);
            }
            return $this->success(['token' => $token, 'user' => new UserResource($user)], 'Login successful');
        } catch (\Exception $e) {
            Log::error('Login JWT error', ['err' => $e->getMessage()]);
            return $this->error('Could not create token', 500);
        }
    }

    public function me(Request $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->error('Unauthorized', 401);
        return $this->success(new UserResource($user));
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
            return $this->error('Could not refresh', 500);
        }
    }

    public function sendPasswordResetCode(PasswordRequest $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);

        $code = rand(100000, 999999);
        $user->password_reset_code = $code;
        $user->password_reset_code_expires_at = now()->addMinutes(30);
        $user->save();

        try {
            Mail::to($user->email)->send(new ResetPasswordEmail($user, $code));
        } catch (\Exception $e) {
            Log::warning('Reset mail failed: ' . $e->getMessage());
        }

        return $this->success([], 'Password reset code sent');
    }

    public function verifyResetCode(PasswordRequest $request)
    {
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
    }

    public function resetPassword(PasswordRequest $request)
    {
        $request->validate(['password' => 'required|confirmed|min:8']);

        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);

        if ($user->password_reset_code !== null || $user->password_reset_code_expires_at !== null) {
            return $this->error('Please verify the reset code first', 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return $this->success([], 'Password reset successful');
    }

    public function changePassword(PasswordRequest $request)
    {
        $user = auth('api')->user();
        if (!$user) return $this->error('Unauthorized', 401);

        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|confirmed|min:8'
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password incorrect', 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->success([], 'Password changed');
    }

    public function verifyEmailCode(Request $request)
    {
        $request->validate(['email' => 'required|email', 'verification_code' => 'required|string|size:6']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);

        if (!$user->markEmailAsVerified($request->verification_code)) {
            return $this->error('Invalid or expired verification code', 400);
        }

        return $this->success([], 'Email verified');
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);
        if ($user->hasVerifiedEmail()) return $this->error('Email already verified', 400);

        $user->sendEmailVerificationCode();
        return $this->success([], 'Verification code resent');
    }
}
