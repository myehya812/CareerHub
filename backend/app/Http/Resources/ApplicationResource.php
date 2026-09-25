<?php

namespace App\Http\Resources;

use App\Http\Resources\JobListingResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
    return [
    'id' => $this->id,

    'applicant' => new UserResource($this->whenLoaded('user')),

    'job' => new JobListingResource($this->whenLoaded('jobListing')),

    'status' => $this->status,

    'created_at' => $this->created_at,
    'updated_at' => $this->updated_at,
];
    }
}
