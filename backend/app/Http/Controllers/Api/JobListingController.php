<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobListingController extends Controller {

public function index(){

    $jobs = JobListing::all();

    return response()->json($jobs);
}

public function show(int $id){

$job = JobListing::findOrFail($id);

return response()->json($job);
    
}
    

public function store(Request $request){

$validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'type' => 'required|in:Full-Time,Part-Time,Internship,Contract',

            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0|gte:salary_min',

            'currency' => 'required|string|size:3',
            'status' => 'required|in:active,draft,closed',

]);

$job = JobListing::create($validated);

return response()->json($job , 201);

}


};


