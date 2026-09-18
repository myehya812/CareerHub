<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function store(Request $request , $id){

    $user = $request->user();

    if($user->role !== 'job_seeker'){
        return response()->json([
            'message' => ' Only job seekers can apply to jobs.'
        ], 403);
    }


    $job = JobListing::findOrFail($id);

    $alreadyApplied = $user->applications()->where('job_listing_id', $job->id)->exists();


    if ($alreadyApplied) {
            return response()->json([
                'message' => 'You have already applied to this job.'
            ], 409);
        }

    $application = $user->applications()->create([
    'job_listing_id' => $job->id,
]);


    return response()->json([
        'message' => 'Application submitted successfully.',
        'application' => $application,
    ], 201);

    }


    public function status(Request $request , $id){
        $user = $request->user();

        if($user->role !== 'job_seeker'){
            return response()->json([
            'message' => 'Only job seekers have application status.'
        ], 403);
        }


    JobListing::findOrFail($id);

   $hasApplied = $user->applications()
        ->where('job_listing_id', $id)
        ->exists();

        return response()->json([
            'has_applied'=> '$hasApplied'
        ]);
    }


}
