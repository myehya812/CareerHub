<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\JobListingController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Auth;



Route::post('/register' , [AuthController::class, 'register']);
Route::post('/login', [AuthController::class,'login']);



Route::get('/jobs', [JobListingController::class, 'index']);

Route::get('/jobs/{id}', [JobListingController::class , 'show'] );



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/logout', [AuthController::class, 'logout'])
->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function(){
    Route::post('/jobs' , [JobListingController::class , 'store']);

    Route::put('/jobs/{id}', [JobListingController::class , 'update']);

    Route::delete('/jobs/{id}' , [JobListingController::class , 'destroy']);


});