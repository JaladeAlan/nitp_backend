<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\NotificationController;
use App\Http\Middleware\CheckTransactionPin;

// Test route
Route::get('/test', function () {
    return response()->json(['message' => 'API is running']);
});

// ------------------------
// Public Routes
// ------------------------
Route::post('/register', [AuthController::class, 'register']); // User registration
Route::post('/login', [AuthController::class, 'login']); // Login route (JWT)

// Email verification
Route::post('/email/verify/code', [AuthController::class, 'verifyEmailCode']);
Route::post('/email/resend-verification', [AuthController::class, 'resendVerificationEmail']);

// Password reset
Route::prefix('password')->group(function () {
    Route::post('/reset/code', [AuthController::class, 'sendPasswordResetCode']); // Send reset code to email
    Route::post('/reset/verify', [AuthController::class, 'verifyResetCode']); // Verify reset code
    Route::post('/reset', [AuthController::class, 'resetPassword']); // Reset password with code
});

// Deposit callback (public & signed)
Route::get('/deposit/callback', [DepositController::class, 'handleDepositCallback'])->name('deposit.callback');

// Paystack webhook (public)
Route::post('/paystack/webhook', [WithdrawalController::class, 'handlePaystackCallback']);

// ------------------------
// Protected Routes (JWT)
// ------------------------
Route::middleware('jwt.auth')->group(function () {

    // Current authenticated user
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Routes requiring email verification
    Route::middleware('verified')->group(function () {

        // Authentication
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/user/change-password', [AuthController::class, 'changePassword']);

        // User stats & transactions
        Route::get('/user/stats', [UserController::class, 'getUserStats']);
        Route::get('/transactions/user', [UserController::class, 'getUserTransactions']);

        // Transaction PIN
        Route::post('/pin/forgot', [UserController::class, 'sendPinResetCode']);
        Route::post('/pin/verify-code', [UserController::class, 'verifyPinResetCode']);
        Route::post('/pin/reset', [UserController::class, 'resetTransactionPin']);
        Route::post('/pin/set', [UserController::class, 'setTransactionPin']);
        Route::post('/pin/update', [UserController::class, 'updateTransactionPin']);

        // Deposits & Withdrawals
        Route::post('/deposit', [DepositController::class, 'initiateDeposit']);
        Route::post('/withdraw', [WithdrawalController::class, 'requestWithdrawal'])
            ->middleware(CheckTransactionPin::class);
        Route::get('/withdrawals/{reference}', [WithdrawalController::class, 'getWithdrawalStatus']);
        Route::get('/withdrawal/retry', [WithdrawalController::class, 'retryPendingWithdrawals']);

        // Bank details
        Route::put('/user/bank-details', [UserController::class, 'updateBankDetails']);
        Route::get('/paystack/banks', [UserController::class, 'getBanks']);
        Route::post('/paystack/resolve-account', [UserController::class, 'resolveAccount']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'getNotifications']);
        Route::get('/notifications/unread', [NotificationController::class, 'getUnreadNotifications']);
        Route::post('/notifications/read', [NotificationController::class, 'markAllAsRead']);
    });
});

?>