<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;

class DashboardController extends Controller
{
    public function jobSeeker(Request $request){

        $user = $request->user();

        if($user->role !== 'job_seeker'){
            return response()->json([
                'message' => 'Only job seekers can access this dashboard.',
            ], 403);
        }


        $recentApplications =$user->applications()->with('jobListing:id,title,location,type')->latest()->limit(3)->get();

        $recentSavedJobs = $user->savedJobs()->with('jobListing:id,title,location,type')->latest()->limit(3)->get();


        return response()->json([
            'stats' => [
                'total_applications' => $user->applications()->count(),
                'saved_jobs' => $user->savedJobs()->count(),
            ],

            'recent_applications' => $recentApplications,
            'recent_saved_jobs' =>$recentSavedJobs,
        ]);
    }

    public function company(Request $request){
        $user = $request->user();

        if ($user->role !== 'company') {
        return response()->json([
            'message' => 'Only companies can access this dashboard.',
        ], 403);
    }

    $totalJobs = $user->jobListings()->count();

    $totalApplications  = Application::whereHas(
        'jobListing',

        function ($query) use ($user){
            $query->where('user_id' , $user->id);
        }
    )->count();

    $recentJobs = $user->jobListings()->select(['id','title','status','created_at'])->withCount('applications')->latest()->limit(5)->get();


    $recentApplications = Application::whereHas(
    'jobListing',
    function ($query) use ($user) {
        $query->where('user_id', $user->id);
    }

)->with(['user:id,name', 'jobListing:id,title'])->latest()->limit(5)->get();

    return response()->json([
        'stats' => [
            'total_jobs' => $totalJobs,
            'total_applications' => $totalApplications,
        ],
        'recent_jobs' => $recentJobs,
        'recent_applications'=>$recentApplications,
    ]);

    

    }




}
