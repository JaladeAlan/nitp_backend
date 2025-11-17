<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmailMail; 
use App\Mail\ResetPasswordEmail;

class AuthController extends Controller
{
    // -----------------------
    // Helper Methods
    // -----------------------
    
    private function sendSuccessResponse($data = [], $message = 'Success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    private function sendErrorResponse($message, $status = 400, $errors = [])
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    private function getUserByEmail(string $email)
    {
        return User::where('email', $email)->first();
    }

    private function generateVerificationCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    // -----------------------
    // Auth Methods
    // -----------------------

    public function me()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return $this->sendErrorResponse('Unauthorized', 401);
            }

            return $this->sendSuccessResponse([
                'id' => $user->id,
                'uid' => $user->uid,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'transaction_pin' => $user->transaction_pin,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'balance' => $user->balance,
                'bank_name' => $user->bank_name,
                'bank_code' => $user->bank_code,
                'account_number' => $user->account_number,
                'account_name' => $user->account_name,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error fetching user profile', ['error' => $e->getMessage()]);
            return $this->sendErrorResponse('Could not fetch user', 500, [
                'exception' => config('app.debug') ? $e->getMessage() : null
            ]);
        }
    }

    public function register(Request $request)
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
            return $this->sendErrorResponse('Validation errors occurred', 422, $e->validator->errors());
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $verificationCode = $this->generateVerificationCode();
            $user->verification_code = $verificationCode;
            $user->save();

            Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationCode));

            $token = JWTAuth::fromUser($user);

            return $this->sendSuccessResponse([
                'user' => $user,
                'token' => $token
            ], 'Registration successful. Please check your email for the verification code.', 201);
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Registration failed. Please try again later.', 500, [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|max:255',
                'password' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return $this->sendErrorResponse('Validation errors occurred', 422, $e->validator->errors());
        }

        $user = $this->getUserByEmail($request->email);
        if (!$user) {
            return $this->sendErrorResponse('User not found', 404);
        }

        if (!$user->hasVerifiedEmail()) {
            return $this->sendErrorResponse('Please verify your email before logging in.', 403);
        }

        try {
            if (!$token = JWTAuth::attempt($request->only('email', 'password'))) {
                return $this->sendErrorResponse('Invalid credentials', 401);
            }
        } catch (JWTException $e) {
            return $this->sendErrorResponse('Could not create token', 500, ['exception' => $e->getMessage()]);
        }

        return $this->sendSuccessResponse(['token' => $token], 'Login successful');
    }

    public function logout()
    {
        try {
            if (!JWTAuth::getToken()) {
                return $this->sendErrorResponse('Token not provided', 400);
            }
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->sendSuccessResponse([], 'Successfully logged out');
        } catch (JWTException $e) {
            return $this->sendErrorResponse('Could not log out', 500, ['exception' => $e->getMessage()]);
        }
    }

    public function refresh()
    {
        try {
            if (!JWTAuth::getToken()) {
                return $this->sendErrorResponse('Token not provided', 400);
            }
            $newToken = JWTAuth::refresh(JWTAuth::getToken());
            return $this->sendSuccessResponse(['token' => $newToken], 'Token refreshed successfully');
        } catch (JWTException $e) {
            return $this->sendErrorResponse('Could not refresh token', 500, ['exception' => $e->getMessage()]);
        }
    }

    // -----------------------
    // Password Reset
    // -----------------------

    public function sendPasswordResetCode(Request $request)
    {
        $request->validate(['email' => 'required|string|email']);

        $user = $this->getUserByEmail($request->email);
        if (!$user) return $this->sendErrorResponse('User not found', 404);

        $resetCode = $this->generateVerificationCode();
        $user->password_reset_code = $resetCode;
        $user->password_reset_code_expires_at = now()->addMinutes(30);
        $user->save();

        try {
            Mail::to($user->email)->send(new ResetPasswordEmail($user, $resetCode));
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Failed to send password reset email.', 500, ['exception' => $e->getMessage()]);
        }

        return $this->sendSuccessResponse([], 'Password reset code sent to your email.');
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'reset_code' => 'required|string|size:6',
        ]);

        $user = $this->getUserByEmail($request->email);
        if (!$user) return $this->sendErrorResponse('User not found', 404);

        if ($user->password_reset_code !== $request->reset_code || $user->password_reset_code_expires_at->isPast()) {
            return $this->sendErrorResponse('Invalid or expired reset code', 400);
        }

        $user->password_reset_code = null;
        $user->password_reset_code_expires_at = null;
        $user->save();

        return $this->sendSuccessResponse([], 'Reset code verified. You can now reset your password.');
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email',
                'password' => [
                    'required', 'string', 'min:8', 'confirmed',
                    'regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/','regex:/[@$!%*?&#]/'
                ],
            ], [
                'password.min' => 'The password must be at least 8 characters long.',
                'password.regex' => 'Password must include at least one uppercase, lowercase, number, and special character.',
                'password.confirmed' => 'The password confirmation does not match.',
            ]);
        } catch (ValidationException $e) {
            return $this->sendErrorResponse('Password validation errors occurred', 422, $e->validator->errors());
        }

        $user = $this->getUserByEmail($request->email);
        if (!$user) return $this->sendErrorResponse('No account found with this email.', 404);

        if ($user->password_reset_code !== null || $user->password_reset_code_expires_at !== null) {
            return $this->sendErrorResponse('Please verify the reset code before setting a new password.', 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Optionally: Invalidate all existing JWTs here

        return $this->sendSuccessResponse([], 'Password has been reset successfully.');
    }

    // -----------------------
    // Email Verification
    // -----------------------

    public function verifyEmailCode(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'verification_code' => 'required|string|size:6',
        ]);

        $user = $this->getUserByEmail($request->email);
        if (!$user) return $this->sendErrorResponse('User not found', 404);

        if ($user->verification_code !== $request->verification_code) {
            return $this->sendErrorResponse('Invalid verification code.', 400);
        }

        $user->markEmailAsVerified();
        $user->verification_code = null;
        $user->save();

        Log::info("User email verified: {$user->email}");

        return $this->sendSuccessResponse([], 'Email verified successfully.');
    }

    public function resendVerificationEmail(Request $request)
    {
        $request->validate(['email' => 'required|string|email']);

        $user = $this->getUserByEmail($request->email);
        if (!$user) return $this->sendErrorResponse('User not found', 404);

        if ($user->hasVerifiedEmail()) {
            return $this->sendErrorResponse('Your email is already verified.', 400);
        }

        $verificationCode = $this->generateVerificationCode();
        $user->verification_code = $verificationCode;
        $user->save();

        try {
            Mail::to($user->email)->send(new VerifyEmailMail($user, $verificationCode));
        } catch (\Exception $e) {
            return $this->sendErrorResponse('Failed to send verification email.', 500, ['exception' => $e->getMessage()]);
        }

        return $this->sendSuccessResponse([], 'A new verification code has been sent to your email.');
    }

    // -----------------------
    // Password Change
    // -----------------------

    public function changePassword(Request $request) 
    {
        $user = $request->user();
        if (!$user) {
            Log::warning('Unauthenticated password change attempt');
            return $this->sendErrorResponse('Unauthenticated.', 401);
        }

        try {
            $request->validate([
                'current_password' => 'required|string',
                'new_password' => [
                    'required','string','min:8','confirmed',
                    'regex:/[A-Z]/','regex:/[a-z]/','regex:/[0-9]/','regex:/[@$!%*?&#]/'
                ],
            ], [
                'new_password.min' => 'The new password must be at least 8 characters long.',
                'new_password.regex' => 'The new password must include at least one uppercase letter, one lowercase letter, one number, and one special character.',
                'new_password.confirmed' => 'The new password confirmation does not match.',
            ]);
        } catch (ValidationException $e) {
            return $this->sendErrorResponse('Password validation errors occurred', 422, $e->validator->errors());
        }

        try {
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->sendErrorResponse('Current password is incorrect.', 400);
            }

            if (Hash::check($request->new_password, $user->password)) {
                return $this->sendErrorResponse('New password cannot be the same as your current password.', 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return $this->sendSuccessResponse([], 'Password has been changed successfully.');
        } catch (\Exception $e) {
            Log::error('Error while changing password', ['user_id' => $user->id ?? null, 'exception' => $e->getMessage()]);
            return $this->sendErrorResponse('An unexpected error occurred while changing the password.', 500);
        }
    }
}
