<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;

class JobListingController extends Controller {

public function index(){

    $jobs = JobListing::all();

    return response()->json($jobs);
}

public function show(int $id){

$job = JobListing::findOrFail($id);

return response()->json($job);
    
}
    



};


