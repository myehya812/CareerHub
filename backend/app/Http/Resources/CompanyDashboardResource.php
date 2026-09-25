<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'total_jobs' => $this['stats']['total_jobs'],
                'total_applications' => $this['stats']['total_applications'],
            ],

            'recent_jobs' => JobListingResource::collection(
                $this['recent_jobs']
            ),

            'recent_applications' => ApplicationResource::collection(
                $this['recent_applications']
            ),
        ];
    }
}