<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobListingRequest;
use App\Http\Requests\UpdateJobListingRequest;
use App\Models\JobListing;
use Illuminate\Http\Request;

class JobListingController extends Controller
{


    public function show($id)
    {
        $job = JobListing::findOrFail($id);

        return response()->json($job);
    }


    public function index(Request $request)
    {
        $search = $request->query('search');  //Look in the URL query parameters for something called search.
        $location = $request->query('location');
        $type = $request->query('type');
        $sort = $request->query('sort', 'newest');

        $query = JobListing::query();

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

        return response()->json($jobs);
    }


    public function store(StoreJobListingRequest $request)
    {

      $this->authorize('create' ,JobListing::class);

        $validated = $request->validated();



        $job = $request->user()->jobListings()->create($validated);


        return response()->json($job, 201);
    }

    public function update(UpdateJobListingRequest $request, int $id)
    {


        $job = JobListing::findOrFail($id);

        $this->authorize('update', $job);

        $validated = $request->validated();

        $job->update($validated);

        return response()->json($job);
    }

    public function destroy($id)
    {
        $job = JobListing::findOrFail($id);

        $this->authorize('delete', $job);

        $job->delete();

        return response()->noContent();
    }
}
