<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/orders', [
        'uses' => function () {
            return response()->json([
                'message' => 'Orders',
                'status' => 200,
                'url' => request()->url(),
                'path' => request()->path(),
            ]);
        },
    ]);

    Route::get('/tests3', [
        'uses' => function () {
            Storage::disk('s3')->put('uploads/test.txt', 'Hola mundo');
            $url = Storage::disk('s3')->temporaryUrl(
                'uploads/test.txt',
                now()->addMinutes(5)
            );
            $path = Storage::disk('s3')->path('uploads/test.txt');
            $exists = Storage::disk('s3')->exists('uploads/test.txt');
            $files = Storage::disk('s3')->files('uploads');
            return response()->json([
                'message' => 'Test S3',
                'status' => 200,
                'data' => [
                    'url' => $url,
                    'path' => $path,
                    'exists' => $exists,
                    'files' => $files,
                ],
            ]);
        },
    ]);
    // ... otras rutas
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
