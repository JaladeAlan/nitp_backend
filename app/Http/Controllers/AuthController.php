<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;
use App\Mail\VerifyEmailMail;
use App\Mail\ResetPasswordEmail;

class AuthController extends Controller
{
    private function success($data = [], string $message = 'Success', int $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    private function error(string $message, int $status = 400, array $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public function me(): \Illuminate\Http\JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return $this->error('Unauthorized', 401);
        }

        return $this->success(['user' => $user]);
    }

    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/[A-Z]/', 'regex:/[a-z]/',
                    'regex:/[0-9]/', 'regex:/[@$!%*?&#]/',
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->error('Validation errors occurred', 422, $e->errors());
        }

        try {
            $verificationCode = rand(100000, 999999);

            /** @var User $user */
            $user = User::create([
                'name'              => $request->name,
                'email'             => $request->email,
                'password'          => Hash::make($request->password),
                'verification_code' => $verificationCode,
            ]);

            Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationCode));

            $token = JWTAuth::fromUser($user);

            return $this->success(
                ['user' => $user, 'token' => $token],
                'Registration successful. Please check your email for the verification code.',
                201
            );

        } catch (\Exception $e) {
            return $this->error('Registration failed. Please try again later.', 500);
        }
    }

    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error('Validation errors occurred', 422, $e->errors());
        }

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();

        if (!$user) return $this->error('User not found', 404);
        if (!$user->hasVerifiedEmail()) return $this->error('Please verify your email before logging in.', 403);

        try {
            if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
                return $this->error('Invalid credentials', 401);
            }
        } catch (JWTException $e) {
            return $this->error('Could not create token', 500);
        }

        return $this->success(['token' => $token], 'Login successful');
    }

    public function logout(): \Illuminate\Http\JsonResponse
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->success([], 'Successfully logged out');
        } catch (JWTException $e) {
            return $this->error('Could not log out', 500);
        }
    }

    public function refresh(): \Illuminate\Http\JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            return $this->success(['token' => $token], 'Token refreshed successfully');
        } catch (JWTException $e) {
            return $this->error('Could not refresh token', 500);
        }
    }

    public function sendPasswordResetCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['email' => 'required|string|email']);
        
        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);

        $resetCode = rand(100000, 999999);

        /** @var User $user */
        $user->update([
            'password_reset_code' => $resetCode,
            'password_reset_code_expires_at' => now()->addMinutes(30),
        ]);

        try {
            Mail::to($user->email)->send(new ResetPasswordEmail($user, $resetCode));
        } catch (\Exception $e) {
            return $this->error('Failed to send password reset email.', 500);
        }

        return $this->success([], 'Password reset code sent to your email.');
    }

    public function verifyResetCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'reset_code' => 'required|string|size:6',
        ]);

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);

        if (
            $user->password_reset_code !== $request->reset_code ||
            $user->password_reset_code_expires_at->isPast()
        ) {
            return $this->error('Invalid or expired reset code', 400);
        }

        /** @var User $user */
        $user->update([
            'password_reset_code' => null,
            'password_reset_code_expires_at' => null,
        ]);

        return $this->success([], 'Reset code verified.');
    }

    public function resetPassword(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/[A-Z]/', 'regex:/[a-z]/',
                    'regex:/[0-9]/', 'regex:/[@$!%*?&#]/',
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->error('Password validation errors occurred', 422, $e->errors());
        }

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('No account found with this email.', 404);

        if ($user->password_reset_code !== null) {
            return $this->error('Please verify the reset code before setting a new password.', 400);
        }

        /** @var User $user */
        $user->update(['password' => Hash::make($request->password)]);

        return $this->success([], 'Password has been reset successfully.');
    }

    public function verifyEmailCode(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'verification_code' => 'required|string|size:6',
        ]);

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);

        if ($user->verification_code !== $request->verification_code) {
            return $this->error('Invalid verification code.', 400);
        }

        /** @var User $user */
        $user->update([
            'verification_code' => null,
            'email_verified_at' => now(),
        ]);

        return $this->success([], 'Email verified successfully.');
    }

    public function resendVerificationEmail(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['email' => 'required|string|email']);

        /** @var User|null $user */
        $user = User::where('email', $request->email)->first();
        if (!$user) return $this->error('User not found', 404);
        if ($user->hasVerifiedEmail()) return $this->error('Your email is already verified.', 400);

        $verificationCode = rand(100000, 999999);

        /** @var User $user */
        $user->update(['verification_code' => $verificationCode]);

        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationCode));
        } catch (\Exception $e) {
            return $this->error('Failed to send verification email.', 500);
        }

        return $this->success([], 'A new verification code has been sent to your email.');
    }

    public function changePassword(Request $request): \Illuminate\Http\JsonResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user) return $this->error('Unauthenticated.', 401);

        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/[A-Z]/', 'regex:/[a-z]/',
                    'regex:/[0-9]/', 'regex:/[@$!%*?&#]/',
                ],
            ]);
        } catch (ValidationException $e) {
            return $this->error('Password validation errors occurred', 422, $e->errors());
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Current password is incorrect.', 400);
        }

        if (Hash::check($request->new_password, $user->password)) {
            return $this->error('New password cannot be the same as your current password.', 400);
        }

        /** @var User $user */
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->success([], 'Password has been changed successfully.');
    }
}
