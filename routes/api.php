<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminNewsController;
use App\Http\Controllers\AdminEventController;
use App\Http\Controllers\AdminGalleryController;
use App\Http\Controllers\AdminProjectController;
use App\Http\Controllers\AdminResourceController;
use App\Http\Controllers\AdminPartnerController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\PartnerController;
use App\Http\Middleware\IsAdmin;

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {

    // Registration & Login
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Email verification
    Route::post('/email/verify', [AuthController::class, 'verifyEmailCode']);
    Route::post('/email/resend', [AuthController::class, 'resendVerificationEmail']);

    // Password reset
    Route::post('/password/request', [AuthController::class, 'sendPasswordResetCode']);
    Route::post('/password/verify', [AuthController::class, 'verifyResetCode']);
    Route::post('/password/reset', [AuthController::class, 'resetPassword']);

    // Must be authenticated
    Route::middleware('jwt.auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/password/change', [AuthController::class, 'changePassword']);
    });
});


/*
|--------------------------------------------------------------------------
| PUBLIC CONTENT ROUTES
|--------------------------------------------------------------------------
*/

Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{id}', [EventController::class, 'show']);

Route::get('/gallery', [GalleryController::class, 'index']);
Route::get('/gallery/{id}', [GalleryController::class, 'show']);

Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);

Route::get('/resources', [ResourceController::class, 'index']);

Route::get('/partners', [PartnerController::class, 'index']);


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES (JWT + Admin Role Required)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->middleware(['jwt.auth', IsAdmin::class])
    ->group(function () {

        // Dashboard Stats
        Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN - USERS
        |--------------------------------------------------------------------------
        */
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{id}', [AdminUserController::class, 'show']);
        Route::put('/users/{id}', [AdminUserController::class, 'update']);
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN - NEWS
        |--------------------------------------------------------------------------
        */
        Route::get('/news', [AdminNewsController::class, 'index']);
        Route::post('/news', [AdminNewsController::class, 'store']);
        Route::get('/news/{id}', [AdminNewsController::class, 'show']);
        Route::put('/news/{id}', [AdminNewsController::class, 'update']);
        Route::delete('/news/{id}', [AdminNewsController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN - EVENTS
        |--------------------------------------------------------------------------
        */
        Route::get('/events', [AdminEventController::class, 'index']);
        Route::post('/events', [AdminEventController::class, 'store']);
        Route::get('/events/{id}', [AdminEventController::class, 'show']);
        Route::put('/events/{id}', [AdminEventController::class, 'update']);
        Route::delete('/events/{id}', [AdminEventController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN - GALLERY
        |--------------------------------------------------------------------------
        */
        Route::get('/gallery', [AdminGalleryController::class, 'index']);
        Route::post('/gallery', [AdminGalleryController::class, 'store']); // image upload
        Route::delete('/gallery/{id}', [AdminGalleryController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN - PROJECTS
        |--------------------------------------------------------------------------
        */
        Route::get('/projects', [AdminProjectController::class, 'index']);
        Route::post('/projects', [AdminProjectController::class, 'store']);
        Route::get('/projects/{id}', [AdminProjectController::class, 'show']);
        Route::put('/projects/{id}', [AdminProjectController::class, 'update']);
        Route::delete('/projects/{id}', [AdminProjectController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN - RESOURCES
        |--------------------------------------------------------------------------
        */
        Route::get('/resources', [AdminResourceController::class, 'index']);
        Route::post('/resources', [AdminResourceController::class, 'store']); // file upload
        Route::delete('/resources/{id}', [AdminResourceController::class, 'destroy']);

        /*
        |--------------------------------------------------------------------------
        | ADMIN - PARTNERS
        |--------------------------------------------------------------------------
        */
        Route::get('/partners', [AdminPartnerController::class, 'index']);
        Route::post('/partners', [AdminPartnerController::class, 'store']);
        Route::put('/partners/{id}', [AdminPartnerController::class, 'update']);
        Route::delete('/partners/{id}', [AdminPartnerController::class, 'destroy']);
    });

