<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\CommonMark\Extension\CommonMark\Node\Inline\Strong;
use App\Models\User;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'profile' => $user->profile,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $rules = [
            'bio' => 'sometimes|nullable|string|max:5000',
            'location' => 'sometimes|nullable|string|max:255',
        ];


        if($user->role === 'job_seeker'){
            $rules['headline'] = 'sometimes|nullable|string|max:255';
            $rules['skills']  = 'sometimes|nullable|array';
            $rules['skills.*'] = 'string|max:100';
            $rules['experience'] = 'sometimes|nullable|string|max:10000';
            
        }

        if($user->role === 'company'){

            $rules['company_name'] = 'sometimes|nullable|string|max:255';
            $rules['website'] = 'sometimes|nullable|url|max:255';
        }

        $validated = $request->validate($rules);

        $profile = $user->profile()->updateOrCreate(
            [],
            $validated
        );

        return response()->json([
            'message' => 'Profile updated successfully,',
            'profile' => $profile,
        ]);
     
    }

    public function uploadResume(Request $request){

    $user = $request->user();

    if($user->role !== 'job_seeker'){
        return response()->json([
            'message' => 'Only job seekers can upload a resume.'
        ], 403);
    }

    $validated = $request->validate([
        'resume' => 'required|file|mimes:pdf|max:5120',
    ]);

    $profile = $user->profile()->firstOrCreate();

    $file = $validated['resume'];

    $new_Path = $file->store('resume' , 'local');

    $oldPath = $profile->resume_path;

    $profile->resume_path = $new_Path;

    $profile->resume_original_name = $file->getClientOriginalName();
    $profile->save();

    if($oldPath){
        Storage::disk('local')->delete($oldPath);


    }


    return response() -> json([
        'message' => 'Resume uploaded successfully',
        'resume_original_name' => $profile->resume_original_name,
    ]);





    }

    public function downloadResume(Request $request){

    $user = $request->user();

    if ($user->role !== 'job_seeker') {
        return response()->json([
            'message' => 'Only job seekers have resumes.',
        ], 403);
    }


    $profile = $user->profile;

    if(!$profile || !$profile->resume_path){
        return response()->json([
            'message' => 'No resume found.',
        ], 404);
    }
    

    
    if(!Storage::disk('local')->exists($profile->resume_path)) {
        return response()->json([
            'message' => 'Resume file could not be found.',
        ], 404);
    }

    $filePath = Storage::disk('local')->path($profile->resume_path);

    return response()->download(
        $filePath,
        $profile->resume_original_name ?? 'resume.pdf'
    );

    }

    public function deleteResume(Request $request){

    $user = $request->user();

     if ($user->role !== 'job_seeker') {
        return response()->json([
            'message' => 'Only job seekers have resumes.',
        ], 403);
    }


    $profile = $user->profile;

    if (!$profile || !$profile->resume_path) {
        return response()->json([
            'message' => 'No resume found.',
        ], 404);
    }

    Storage::disk('local')->delete($profile->resume_path);

     $profile->resume_path = null;
     $profile->resume_original_name = null;
     $profile->save();


    return response()->json([
        'message' => 'Resume deleted successfully.',
    ]);


}



public function publicShow($id)
{
    $user = User::with('profile')->findOrFail($id);

    $profile = $user->profile;

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
        ],

        'profile' => $profile ? [
            'headline' => $profile->headline,
            'bio' => $profile->bio,
            'location' => $profile->location,
            'skills' => $profile->skills,
            'experience' => $profile->experience,
            'company_name' => $profile->company_name,
            'website' => $profile->website,
            'resume_original_name' => $profile->resume_original_name,
        ] : null,
    ]);
}




}