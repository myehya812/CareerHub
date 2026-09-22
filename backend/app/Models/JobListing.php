<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    protected $fillable = [
        'title',
        'description',
        'location',
        'type',
        'salary_min',
        'salary_max',
        'currency',
        'status',
    ];

    // Jobs posted by this user
public function jobListings()
{
    return $this->hasMany(JobListing::class);
}

// $Applications submitted by this user
public function applications()
{
    return $this->hasMany(Application::class);
}

public function savedJobs() : HasMany{
    return $this->hasMany(SavedJob::class);
}

}
