<?php

use App\Http\Controllers\Api\Admin\AvailabilityController;
use App\Http\Controllers\Api\Admin\MentorController;
use App\Http\Controllers\Api\Admin\SlotController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Student\AppointmentController;
use App\Http\Controllers\Api\Student\SlotBrowserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

Route::get('/ping', function () {
    return response()->json([
        'success' => true,
        'message' => 'pong',
        'data' => null,
    ]);
});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin,super_admin'])
    ->group(function () {
        Route::get('/mentors', [MentorController::class, 'index']);
        Route::post('/mentors', [MentorController::class, 'store']);
        Route::patch('/mentors/{id}', [MentorController::class, 'update']);
        Route::delete('/mentors/{id}', [MentorController::class, 'destroy']);

        Route::get('/mentors/{mentorId}/availability', [AvailabilityController::class, 'indexByMentor']);
        Route::post('/mentors/{mentorId}/availability', [AvailabilityController::class, 'storeForMentor']);
        Route::patch('/availability/{id}', [AvailabilityController::class, 'update']);
        Route::delete('/availability/{id}', [AvailabilityController::class, 'destroy']);

        Route::post('/mentors/{mentorId}/generate-slots', [SlotController::class, 'generateForMentor']);
    });

Route::prefix('student')
    ->middleware(['auth:sanctum', 'role:student'])
    ->group(function () {
        Route::get('/slots', [SlotBrowserController::class, 'index']);
        Route::post('/appointments', [AppointmentController::class, 'store']);
        Route::get('/appointments', [AppointmentController::class, 'index']);
        Route::patch('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    });
