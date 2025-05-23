<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MediaController;

Route::get('/', [
    'uses' => function () {
        return response()->json([
            'message' => 'Welcome to the media API',
            'status' => 200,
            'url' => request()->url(),
            'path' => request()->path(),
        ]);
    },
]);


Route::middleware('auth.remote')->group(function () {
    Route::post('/users/{id}/photo', [MediaController::class, 'uploadUserPhoto']);
    Route::post('/classes/{id}/video', [MediaController::class, 'uploadClassVideo']);
    Route::post('/classes/{id}/photo', [MediaController::class, 'uploadClassPhoto']);
    Route::post('/courses/{id}/photo', [MediaController::class, 'uploadCoursePhoto']);

    Route::get('/users/{id}/photo', [MediaController::class, 'getUserPhoto']);
    Route::get('/classes/{id}/video', [MediaController::class, 'getClassVideo']);
    Route::get('/classes/{id}/photo', [MediaController::class, 'getClassPhoto']);
    Route::get('/courses/{id}/photo', [MediaController::class, 'getCoursePhoto']);

    //multiple
    Route::post('/courses/photos', [MediaController::class, 'getMultipleCoursePhotos']);

    
});



// not found route
Route::fallback(function () {
    return response()->json([
        'message' => 'Not Found',
        'status' => 404,
        'url' => request()->url(),
        'path' => request()->path(),
    ], 404);
});
