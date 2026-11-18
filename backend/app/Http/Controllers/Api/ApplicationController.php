<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ApplicationResource;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\RecruitmentTransactions;

class ApplicationController extends Controller
{
    public function store(Request $request, $id, RecruitmentTransactions $transactions)
    {
        $user = $request->user();

        if ($user->role !== 'job_seeker') {
            return response()->json([
                'message' => 'Only job seekers can apply to jobs.'
            ], 403);
        }

        $job = JobListing::findOrFail($id);

        if ($job->status !== 'active') {
            return response()->json([
                'message' => 'This job is not accepting applications.'
            ], 409);
        }

        $result = $transactions->execute('ch_submit_application', [$user->id, $job->id], function () use ($user, $job) {
            $application = $user->applications()->firstOrCreate([
                'job_listing_id' => $job->id,
            ], ['status' => 'pending']);
            abort_unless($application->wasRecentlyCreated, 409, 'You have already applied to this job.');

            return ['application_id' => $application->id];
        });
        $application = Application::findOrFail($result['application_id']);

        return response()->json([
            'message' => 'Application submitted successfully.',
            'application' => new ApplicationResource(
                $application->load(['user', 'jobListing'])
            ),
        ], 201);
    }

    public function status(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'job_seeker') {
            return response()->json([
                'message' => 'Only job seekers have application status.'
            ], 403);
        }

        JobListing::findOrFail($id);

        $hasApplied = $user->applications()
            ->where('job_listing_id', $id)
            ->exists();

        return response()->json([
            'has_applied' => $hasApplied,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role !== 'job_seeker') {
            return response()->json([
                'message' => 'Only job seekers can view their applications.'
            ], 403);
        }

        $applications = $user->applications()
            ->with('jobListing')
            ->latest()
            ->get();

        return ApplicationResource::collection($applications);
    }

    public function applicants(Request $request, $id)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only companies can view applicants.'
            ], 403);
        }

        $job = JobListing::findOrFail($id);

        if ($job->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to view applicants for this job.'
            ], 403);
        }

        $applications = $job->applications()
            ->with('user', 'jobListing')
            ->latest()
            ->get();

        return ApplicationResource::collection($applications);
    }

    public function updateStatus(Request $request, $id, RecruitmentTransactions $transactions)
    {
        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only companies can update application statuses.'
            ], 403);
        }

        $application = Application::with('jobListing')
            ->findOrFail($id);

        if ($application->jobListing->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to update this application.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,accepted,rejected',
        ]);

        $transactions->execute('ch_update_application_status', [$user->id, $application->id, $validated['status']], function () use ($application, $validated) {
            $application->update($validated);

            return ['application_id' => $application->id];
        });
        $application->refresh();

        return response()->json([
            'message' => 'Application status updated successfully.',
            'application' => new ApplicationResource(
                $application->load(['user', 'jobListing'])
            ),
        ]);
    }

    public function downloadResume(Request $request, $id)
    {

        $user = $request->user();

        if ($user->role !== 'company') {
            return response()->json([
                'message' => 'Only companies can download applicant resumes.',
            ], 403);
        }

        $application = Application::with([
            'jobListing',
            'user.profile',
        ])->findOrFail($id);


        if ($application->jobListing->user_id !== $user->id) {
            return response()->json([
                'message' => 'You are not allowed to access this resume.',
            ], 403);
        }


        $profile = $application->user->profile;

        if (!$profile || !$profile->resume_path) {
            return response()->json([
                'message' => 'This applicant has not uploaded a resume.',
            ], 404);
        }



        if (!Storage::disk('local')->exists($profile->resume_path)) {
            return response()->json([
                'message' => 'Resume file could not be found.',
            ], 404);
        }

        $filePath = Storage::disk('local')->path(
            $profile->resume_path
        );

        return response()->download(
            $filePath,
            $profile->resume_original_name ?? 'resume.pdf'
        );
    }
}
