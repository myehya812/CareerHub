<?php

namespace App\Http\Requests;

use App\Models\JobListing;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;


class UpdateJobListingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    
    protected function failedAuthorization()
{
    $message = $this->user()?->role !== 'company'
        ? 'Only company accounts can edit jobs.'
        : 'You are not allowed to edit this job.';

    throw new AuthorizationException($message);
}

   
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'location' => 'required|string|max:255',
            'type' => 'required|in:Full-Time,Part-Time,Internship,Contract',
            'salary_min' => 'nullable|integer|min:0',
            'salary_max' => 'nullable|integer|min:0|gte:salary_min',
            'currency' => 'required|string|size:3',
            'status' => 'required|in:active,draft,closed',
        ];
    }
}
