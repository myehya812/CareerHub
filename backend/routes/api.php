<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobListingController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/jobs', [JobListingController::class, 'index']);

Route::get('/jobs/{id}', [JobListingController::class , 'show'] );

Route::post('/jobs' , [JobListingController::class , 'store']);

Route::put('/jobs/{id}', [JobListingController::class , 'update']);