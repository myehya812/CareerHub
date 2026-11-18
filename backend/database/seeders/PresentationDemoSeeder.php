<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use App\Services\RecruitmentTransactions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PresentationDemoSeeder extends Seeder
{
    public const PASSWORD = '123456789';

    public function run(): void
    {
        $database = DB::connection()->getDatabaseName();
        $allowed = (app()->environment('local') && $database === 'careerhub_presentation_2026')
            || (app()->environment('testing') && str_ends_with($database, '_testing'));

        if (! $allowed || ! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Presentation data requires the local careerhub_presentation_2026 database or a native _testing database in the testing environment.');
        }

        $fixture = json_decode(file_get_contents(database_path('seeders/data/presentation.json')), true, 512, JSON_THROW_ON_ERROR);

        DB::transaction(function () use ($fixture) {
            $companies = [];
            $jobs = [];
            $closeAfterSeeding = [];

            foreach ($fixture['companies'] as $i => $data) {
                $company = $this->account($data['name'], $data['email'], 'company');
                $company->profile()->firstOrCreate([], [
                    'headline' => 'Hiring software and technology talent',
                    'bio' => 'Fictional company profile for the CareerHub presentation.',
                    'location' => $data['location'],
                    'skills' => $data['skills'],
                    'experience' => 'Demonstration technology team with active recruitment.',
                    'company_name' => $data['company_name'],
                    'website' => 'https://company'.($i + 1).'.example.test',
                ]);
                $companies[$i] = $company;

                foreach ($data['job_titles'] as $j => $title) {
                    $job = JobListing::where('user_id', $company->id)->where('title', $title)->first();
                    if (! $job) {
                        $job = $this->createJob($company, [
                            'title' => $title,
                            'description' => 'Fictional sample vacancy at '.$data['company_name'].' for the CareerHub presentation. Work with '.implode(', ', $data['skills']).'. Responsibilities include practical implementation, teamwork, documentation and testing.',
                            'location' => $data['location'],
                            'type' => $fixture['job_types'][$j],
                            'salary_min' => $fixture['salary_ranges'][$j][0],
                            'salary_max' => $fixture['salary_ranges'][$j][1],
                        ], $j === 3 ? 'draft' : 'active');
                        if ($j === 4) {
                            $closeAfterSeeding[] = $job;
                        }
                    }
                    $jobs[$i][$j] = $job;
                }
            }

            foreach ($fixture['job_seekers'] as $i => $data) {
                $seeker = $this->account($data['name'], $data['email'], 'job_seeker');
                $seeker->profile()->firstOrCreate([], [
                    'headline' => $data['headline'],
                    'bio' => 'Sample job-seeker profile for the CareerHub presentation. Interested in practical technology projects and teamwork.',
                    'location' => $data['location'],
                    'skills' => $data['skills'],
                    'experience' => 'Experience with academic projects, practical exercises and collaborative software development.',
                ]);

                foreach ($companies as $j => $company) {
                    $job = $jobs[$j][$i === $j ? 4 : ($i + $j) % 3];
                    if (Application::where('user_id', $seeker->id)->where('job_listing_id', $job->id)->exists()) {
                        continue;
                    }

                    // A missing historical fixture may need its vacancy reopened
                    // inside this transaction. Restore the prior state afterward.
                    $previousStatus = $job->status;
                    if ($previousStatus !== 'active') {
                        $job->update(['status' => 'active']);
                    }
                    $result = $this->procedure('ch_submit_application', [$seeker->id, $job->id]);
                    $status = $i === $j ? 'accepted' : ['pending', 'accepted', 'rejected'][($i + $j) % 3];
                    if ($status !== 'pending') {
                        $this->procedure('ch_update_application_status', [$company->id, $result['application_id'], $status]);
                    }
                    if ($previousStatus !== 'active') {
                        $job->update(['status' => $previousStatus]);
                    }
                }

                for ($k = 0; $k < 3; $k++) {
                    $this->procedure('ch_save_job', [$seeker->id, $jobs[($i + $k) % 5][($i + $k) % 3]->id]);
                }
            }

            foreach ($closeAfterSeeding as $job) {
                $job->update(['status' => 'closed']);
            }

            $this->starterJobs($fixture['starter_jobs']);
        });

        $this->command?->info('Presentation examples loaded. Existing records and passwords were preserved. New demo accounts use @careerhub.test and password '.self::PASSWORD.'.');
    }

    private function account(string $name, string $email, string $role): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name, 'role' => $role, 'password' => self::PASSWORD,
        ]);
        if ($user->role !== $role) {
            throw new RuntimeException('A presentation email already belongs to an account with a different role.');
        }

        return $user;
    }

    private function createJob(User $company, array $data, string $status = 'active'): JobListing
    {
        $result = $this->procedure('ch_create_job', [
            $company->id, $data['title'], $data['description'], $data['location'],
            $data['type'], $data['salary_min'], $data['salary_max'], 'USD', $status,
        ]);

        return JobListing::findOrFail($result['job_id']);
    }

    private function starterJobs(array $fixtures): void
    {
        $owner = null;
        foreach ($fixtures as $data) {
            $existing = JobListing::where('title', $data['title'])
                ->where('description', $data['description'])
                ->whereHas('user', fn ($query) => $query->where('role', 'company'))
                ->orderBy('id')->first();
            if ($existing) {
                $owner = $existing->user;
                break;
            }
        }

        if (! $owner) {
            $owner = $this->account('Demo Company', 'demo-company@careerhub.test', 'company');
            $owner->profile()->firstOrCreate([], [
                'company_name' => 'Demo Company', 'location' => 'Beirut',
                'bio' => 'Fictional owner of the six starter presentation vacancies.',
            ]);
        }

        foreach ($fixtures as $data) {
            if (! JobListing::where('user_id', $owner->id)->where('title', $data['title'])->exists()) {
                $this->createJob($owner, $data);
            }
        }
    }

    private function procedure(string $procedure, array $parameters): array
    {
        return app(RecruitmentTransactions::class)->execute(
            $procedure, $parameters,
            fn () => throw new RuntimeException('Native recruitment procedures are required.'),
        );
    }
}
