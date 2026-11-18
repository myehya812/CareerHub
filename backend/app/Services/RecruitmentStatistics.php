<?php

namespace App\Services;

use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecruitmentStatistics
{
    public function forJobSeeker(User $user): array
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $stats = DB::selectOne('SELECT ch_user_application_count(?) AS total_applications, ch_user_saved_job_count(?) AS saved_jobs', [$user->id, $user->id]);

            return ['total_applications' => (int) $stats->total_applications, 'saved_jobs' => (int) $stats->saved_jobs];
        }

        return ['total_applications' => $user->applications()->count(), 'saved_jobs' => $user->savedJobs()->count()];
    }

    public function forCompany(User $user): array
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $stats = DB::selectOne('SELECT ch_company_job_count(?) AS total_jobs, ch_company_application_count(?) AS total_applications', [$user->id, $user->id]);

            return ['total_jobs' => (int) $stats->total_jobs, 'total_applications' => (int) $stats->total_applications];
        }

        return [
            'total_jobs' => $user->jobListings()->count(),
            'total_applications' => Application::whereHas('jobListing', fn ($query) => $query->where('user_id', $user->id))->count(),
        ];
    }
}
