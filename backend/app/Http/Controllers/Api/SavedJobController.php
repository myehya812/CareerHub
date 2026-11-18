<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\Client\ResponseSequence;
use Illuminate\Http\Request;
use App\Http\Resources\SavedJobResource;
use App\Services\RecruitmentTransactions;

class SavedJobController extends Controller
{
    public function store(Request $request,$id, RecruitmentTransactions $transactions){
        $user = $request->user();

        if($user->role !== 'job_seeker'){
            return response()->json(['message' => 'Only job seekers can save jobs.',], 403);
        }

        $job = JobListing::findOrFail($id);

        $result = $transactions->execute('ch_save_job', [$user->id, $job->id], function () use ($user, $job) {
            $savedJob = $user->savedJobs()->firstOrCreate(['job_listing_id' => $job->id]);

            return ['saved_job_id' => $savedJob->id, 'created' => (int) $savedJob->wasRecentlyCreated];
        });
        $created = (bool) $result['created'];


        return response()->json([
            'message' => $created ? 'Job saved successfully.' : 'Job already saved',
            'is_saved' => true,
            'saved_job_id' => (int) $result['saved_job_id'],
        ], $created ? 201 : 200);


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

    public function destroy(Request $request, $id, RecruitmentTransactions $transactions){

        $user = $request->user();

        if ($user->role !== 'job_seeker') {
        return response()->json([
            'message' => 'Only job seekers can remove saved jobs.',
        ], 403);
    }

        $job = JobListing::findOrFail($id);

        $transactions->execute('ch_remove_saved_job', [$user->id, $job->id], function () use ($user, $job) {
            $savedJob = $user->savedJobs()->where('job_listing_id', $job->id)->first();
            abort_if($savedJob === null, 404, 'This job is not saved.');
            $savedJob->delete();

            return ['saved_job_id' => $savedJob->id];
        });

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

        return SavedJobResource::collection($savedJobs);
    }

}
