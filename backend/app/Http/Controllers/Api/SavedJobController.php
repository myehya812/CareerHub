<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;

class SavedJobController extends Controller
{
    public function store(Request $request,$id){
        $user = $request->user();

        if($user->role !== 'job_seeker'){
            return response()->json(['message' => 'Only job seekers can save jobs.',], 403);
        }

        $job = JobListing::findOrFail($id);

        $savedJob = $user->savedJobs()->firstOrCreate([
    'job_listing_id' => $job->id,
                         ]);

        
        return response()->json([
            'message' => $savedJob->wasRecentlyCreated ? 'Job saved successfully.' : 'Job already saved',
            'is_saved' => true,
            'saved_job_id' =>$savedJob->id,
        ], $savedJob->wasRecentlyCreated ? 201 : 200);


    }


    public function status(Request $request,$id){

        $user = $request->user();

        if($user->role !== 'job_seeker'){
            return response()->json(['message' => 'Only job seekers can save jobs.',], 403);
        }

        $job  = JobListing::findOrFail($id);

        $isSaved = $user->savedJobs()->where('job_listing_id', $job->id)->exists();

        return response()->json([
            'is_saved' => $isSaved,
        ]);

    }

    public function destroy(Request $request, $id){

        $user = $request->user();

        if ($user->role !== 'job_seeker') {
        return response()->json([
            'message' => 'Only job seekers can remove saved jobs.',
        ], 403);
    }

        $job = JobListing::findOrFail($id);

        $savedJob = $user->savedJobs()->where('job_listing_id' , $job->id)->first();


        if(!$savedJob){
            return response()->json([
                'message' => 'This job is not saved.',
            ], 404);
        }


        $savedJob->delete();

        return response()->json([
            'message' => 'Job removed from saved jobs.',
            'is_saved'=> false,
        ]);

    }


    public function index(Request $request){
        $user = $request->user();

        if ($user->role !== 'job_seeker') {
        return response()->json([
            'message' => 'Only job seekers can view saved jobs.',
        ], 403);
    }

        $savedJobs = $user->savedJobs()->with('jobListing')->latest()->get();

        return response()->json([
            'saved_jobs'=>$savedJobs,
        ]);
    }

}
