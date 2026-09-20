<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobListingController;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Public hode l crud
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/jobs', [JobListingController::class, 'index']);
Route::get('/jobs/{id}', [JobListingController::class, 'show']);


// Authenticated
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);


    // Jobs
    Route::post('/jobs', [JobListingController::class, 'store']);
    Route::put('/jobs/{id}', [JobListingController::class, 'update']);
    Route::delete('/jobs/{id}', [JobListingController::class, 'destroy']);


    // Applications
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/jobs/{id}/applications', [ApplicationController::class, 'store']);
    Route::get('/jobs/{id}/application-status', [ApplicationController::class, 'status']);


    // Company applicants
    Route::get('/jobs/{id}/applications', [ApplicationController::class, 'applicants']);
    Route::patch('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
    Route::get('/applications/{id}/resume', [ApplicationController::class, 'downloadResume']);


    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::patch('/profile', [ProfileController::class, 'update']);

    Route::post('/profile/resume', [ProfileController::class, 'uploadResume']);
    Route::get('/profile/resume', [ProfileController::class, 'downloadResume']);
    Route::delete('/profile/resume', [ProfileController::class, 'deleteResume']);

    Route::get('/users/{id}/profile', [ProfileController::class, 'publicShow']);
});