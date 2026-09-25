<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'headline' => $this->headline,
            'bio' => $this->bio,
            'location' => $this->location,

            'skills' => $this->skills,
            'experience' => $this->experience,

            'company_name' => $this->company_name,
            'website' => $this->website,

            'resume' => [
                'available' => !is_null($this->resume_path),
                'name' => $this->resume_original_name,
            ],

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}