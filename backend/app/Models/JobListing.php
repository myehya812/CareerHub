<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
// Company that published this job
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
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
