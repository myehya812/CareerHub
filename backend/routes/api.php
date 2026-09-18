<?php

use App\Http\Controllers\Api\ApplicationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\JobListingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);



Route::get('/jobs', [JobListingController::class, 'index']);
Route::get('/jobs/{id}', [JobListingController::class, 'show']);



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth:sanctum');




Route::middleware('auth:sanctum')->group(function () {



    // lal Jobs
    Route::post('/jobs', [JobListingController::class, 'store']);
    Route::put('/jobs/{id}', [JobListingController::class, 'update']);
    Route::delete('/jobs/{id}', [JobListingController::class, 'destroy']);




    // Job seeker appls
    Route::get('/applications', [ApplicationController::class, 'index']);
    Route::post('/jobs/{id}/applications', [ApplicationController::class, 'store']);
    Route::get('/jobs/{id}/application-status', [ApplicationController::class, 'status']);





    // Company applicant
    Route::get('/jobs/{id}/applications', [ApplicationController::class, 'applicants']);
    Route::patch('/applications/{id}/status', [ApplicationController::class, 'updateStatus']);
});