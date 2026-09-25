<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class JobListingResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'type' => $this->type,

            'salary' => [
                'min' => $this->salary_min,
                'max' => $this->salary_max,
                'currency' => $this->currency,
            ],

            'status' => $this->status,

            'company' => new UserResource($this->whenLoaded('user')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
