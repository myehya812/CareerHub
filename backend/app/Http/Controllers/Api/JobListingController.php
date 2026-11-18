<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobListingRequest;
use App\Http\Requests\UpdateJobListingRequest;
use App\Models\JobListing;
use Illuminate\Http\Request;
use App\Http\Resources\JobListingResource;
use App\Services\RecruitmentTransactions;

class JobListingController extends Controller
{


    public function show(Request $request, $id)
    {
        $job = JobListing::with('user')->findOrFail($id);

        $viewer = $request->user();
        abort_if($job->status !== 'active' && ($viewer === null || $viewer->id !== $job->user_id), 404);

        return new JobListingResource($job);
    }


    public function index(Request $request)
    {
        $search = $request->query('search');  //Look in the URL query parameters for something called search.
        $location = $request->query('location');
        $type = $request->query('type');
        $sort = $request->query('sort', 'newest');

        $query = JobListing::with('user')->where('status', 'active');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($location) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($type) {
            $query->where('type',  $type);
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;

            case 'salary_low':
                $query->orderBy('salary_min', 'asc');
                break;

            case 'salary_high':
                $query->orderBy('salary_max', 'desc');
                break;

            default:
                $query->latest();
                break;
        }



        $jobs = $query->paginate(6);

        return JobListingResource::collection($jobs);
    }


    public function store(StoreJobListingRequest $request, RecruitmentTransactions $transactions)
    {

      $this->authorize('create' ,JobListing::class);

        $validated = $request->validated();



        $result = $transactions->execute('ch_create_job', [
            $request->user()->id, $validated['title'], $validated['description'],
            $validated['location'], $validated['type'], $validated['salary_min'] ?? null,
            $validated['salary_max'] ?? null, $validated['currency'], $validated['status'],
        ], function () use ($request, $validated) {
            return ['job_id' => $request->user()->jobListings()->create($validated)->id];
        });
        $job = JobListing::findOrFail($result['job_id']);


        return (new JobListingResource($job))->response()->setStatusCode(201);
    }

    public function update(UpdateJobListingRequest $request, int $id)
    {


        $job = JobListing::findOrFail($id);

        $this->authorize('update', $job);

        $validated = $request->validated();

        $job->update($validated);

        return new JobListingResource($job);
    }

    public function destroy(Request $request, $id, RecruitmentTransactions $transactions)
    {
        $job = JobListing::findOrFail($id);

        $this->authorize('delete', $job);

        $transactions->execute('ch_delete_job', [$request->user()->id, $job->id], function () use ($job) {
            $job->delete();

            return ['job_id' => $job->id];
        });

        return response()->noContent();
    }
}
