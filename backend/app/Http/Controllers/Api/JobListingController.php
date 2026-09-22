<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $search =$request->query('search');  //Look in the URL query parameters for something called search.
        $location = $request->query('location');
        $type = $request->query('type');
        $sort = $request->query('sort' , 'newest');

        $query = JobListing::query();

        if($search){
            $query->where('title' , 'like' , "%{$search}%");
        }

        if($location){
            $query->where('location' , 'like' , "%{$location}%");
        }

        if($type){
            $query->where('type' ,  $type);
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


    public function store(Request $request)
    {
        $user = $request->user();


        if ($user->role !== 'company') {
            return response()->json([
                'message'   => 'Only companies can publish jobs.'
            ], 403);
        }


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
        // The relationship sets user_id so each job stays tied to its creator.
        $job = $request->user()->jobListings()->create($validated);

        return response()->json($job, 201);
    }

    public function update(Request $request, int $id)
    {
        

        $job = JobListing::findOrFail($id);

        $user = $request->user();

        if($user->role !== 'company'){
            return response()->json([
                'message' => 'Only company accounts can edit jobs.'
            ],403);
        }



    if($job->user_id !== $user->id){
        return response()->json([
            'message' => ' You are not allowed to edit this job.'
        ], 403);
    }



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

        $job->update($validated);

        return response()->json($job);
    }

   public function destroy(Request $request, $id)
{
    $job = JobListing::findOrFail($id);

    $user = $request->user();

   
    if ($user->role !== 'company') {
        return response()->json([
            'message' => 'Only company accounts can delete jobs.',
        ], 403);
    }

  
    if ($job->user_id !== $user->id) {
        return response()->json([
            'message' => 'You are not allowed to delete this job.',
        ], 403);
    }

  
    $job->delete();

    return response()->noContent();
}

}