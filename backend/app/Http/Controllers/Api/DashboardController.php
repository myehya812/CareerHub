<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application;
use App\Http\Resources\JobSeekerDashboardResource;
use App\Http\Resources\CompanyDashboardResource;


class DashboardController extends Controller
{
    public function jobSeeker(Request $request)
    {

        $user = $request->user();

        if ($user->role !== 'job_seeker') {
            return response()->json([
                'message' => 'Only job seekers can access this dashboard.',
            ], 403);
        }


        $recentApplications = $user->applications()
            ->with('jobListing')
            ->latest()
            ->limit(3)
            ->get();


        $recentSavedJobs = $user->savedJobs()
            ->with('jobListing')
            ->latest()
            ->limit(3)
            ->get();


        return new JobSeekerDashboardResource([
            'stats' => [
                'total_applications' => $user->applications()->count(),
                'saved_jobs' => $user->savedJobs()->count(),
            ],

            'recent_applications' => $recentApplications,
            'recent_saved_jobs' => $recentSavedJobs,
        ]);
    }

    public function company(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only companies can access this dashboard.',
            ], 403);
        }

        $totalJobs = $user->jobListings()->count();

        $totalApplications  = Application::whereHas(
            'jobListing',

            function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }
        )->count();

        $recentJobs = $user->jobListings()
            ->withCount('applications')
            ->latest()
            ->limit(5)
            ->get();


        $recentApplications = Application::whereHas(
            'jobListing',
            function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }

        )->with(['user:id,name', 'jobListing:id,title'])->latest()->limit(5)->get();

        return new CompanyDashboardResource([
            'stats' => [
                'total_jobs' => $totalJobs,
                'total_applications' => $totalApplications,
            ],
            'recent_jobs' => $recentJobs,
            'recent_applications' => $recentApplications,
        ]);
    }
}
