<?php

use Illuminate\Support\Facades\Route;


// Route for storage link
Route::get('storage/{path}', 'StorageController@show')->name('storage.local');

// Route for health check
Route::get('up', function () {
    return 'Application is up';
});
