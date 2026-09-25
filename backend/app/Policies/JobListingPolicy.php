<?php

namespace App\Policies;

use App\Models\JobListing;
use App\Models\User;

class JobListingPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, JobListing $jobListing): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === 'company';
    }

    public function update(User $user, JobListing $job): bool
    {
        return $user->role === 'company'
        && $job->user_id === $user->id;
    }

    public function delete(User $user, JobListing $job): bool
    {
        return $user->role === 'company'
            && $job->user_id === $user->id;
    }

    public function restore(User $user, JobListing $jobListing): bool
    {
        return false;
    }

    public function forceDelete(User $user, JobListing $jobListing): bool
    {
        return false;
    }
}