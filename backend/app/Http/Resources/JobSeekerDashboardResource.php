<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobSeekerDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'total_applications' => $this['stats']['total_applications'],
                'saved_jobs' => $this['stats']['saved_jobs'],
            ],

            'recent_applications' => ApplicationResource::collection(
                $this['recent_applications']
            ),

            'recent_saved_jobs' => SavedJobResource::collection(
                $this['recent_saved_jobs']
            ),
        ];
    }
}