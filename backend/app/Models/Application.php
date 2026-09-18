<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'job_listing_id',
        'status',
    ];

    // The user who submitted this application
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // The job this application belongs to
    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }
}
