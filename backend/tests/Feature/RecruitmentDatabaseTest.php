<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RecruitmentDatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        $inMemory = DB::getDriverName() === 'sqlite' && $database === ':memory:';

        if (! $inMemory && ! str_ends_with($database, '_testing')) {
            throw new \RuntimeException('Database tests require :memory: or a database ending in _testing.');
        }
    }

    private function company(): User
    {
        return User::factory()->create(['role' => 'company']);
    }

    private function seeker(): User
    {
        return User::factory()->create(['role' => 'job_seeker']);
    }

    private function job(?User $company = null): JobListing
    {
        return ($company ?? $this->company())->jobListings()->create($this->jobData());
    }

    private function jobData(array $overrides = []): array
    {
        return array_replace([
            'title' => 'Backend Developer',
            'description' => 'Build CareerHub APIs.',
            'location' => 'Beirut',
            'type' => 'Full-Time',
            'salary_min' => 1500,
            'salary_max' => 2500,
            'currency' => 'USD',
            'status' => 'active',
        ], $overrides);
    }

    public function test_schema_matches_the_existing_models(): void
    {
        foreach (['users', 'job_listings', 'profiles', 'applications', 'saved_jobs'] as $table) {
            $this->assertTrue(Schema::hasTable($table));
        }

        $this->assertTrue(Schema::hasColumns('profiles', ['company_name', 'resume_path', 'resume_original_name']));
        $this->assertFalse(Schema::hasColumn('profiles', 'campany_name'));
        $this->assertFalse(Schema::hasColumn('profiles', 'canmpany_name'));
    }

    public function test_company_profile_and_resume_metadata_can_be_persisted(): void
    {
        $company = $this->company();
        $this->actingAs($company)->patchJson('/api/profile', [
            'company_name' => 'CareerHub Test Company',
            'website' => 'https://example.test',
        ])->assertOk();
        $this->assertDatabaseHas('profiles', ['user_id' => $company->id, 'company_name' => 'CareerHub Test Company']);

        $profile = $this->seeker()->profile()->create(['skills' => ['PHP', 'SQL']]);
        $profile->resume_path = 'resume/test.pdf';
        $profile->resume_original_name = 'CV_2026.pdf';
        $profile->save();
        $this->assertSame(['PHP', 'SQL'], $profile->fresh()->skills);
        $this->assertSame('CV_2026.pdf', $profile->fresh()->resume_original_name);
    }

    public function test_user_email_is_unique(): void
    {
        $user = $this->seeker();
        $this->expectException(QueryException::class);
        User::factory()->create(['email' => $user->email]);
    }

    public function test_each_user_can_have_only_one_profile(): void
    {
        $user = $this->seeker();
        $user->profile()->create([]);
        $this->expectException(QueryException::class);
        $user->profile()->create([]);
    }

    public function test_duplicate_applications_are_rejected_by_the_database(): void
    {
        $job = $this->job();
        $user = $this->seeker();
        $user->applications()->create(['job_listing_id' => $job->id]);
        $this->expectException(QueryException::class);
        $user->applications()->create(['job_listing_id' => $job->id]);
    }

    public function test_duplicate_saved_jobs_are_rejected_by_the_database(): void
    {
        $job = $this->job();
        $user = $this->seeker();
        $user->savedJobs()->create(['job_listing_id' => $job->id]);
        $this->expectException(QueryException::class);
        $user->savedJobs()->create(['job_listing_id' => $job->id]);
    }

    public function test_applications_cannot_reference_a_missing_job(): void
    {
        $this->expectException(QueryException::class);
        $this->seeker()->applications()->create(['job_listing_id' => 999999]);
    }

    public function test_deleting_a_job_removes_its_applications_and_bookmarks(): void
    {
        $job = $this->job();
        $user = $this->seeker();
        $user->applications()->create(['job_listing_id' => $job->id]);
        $user->savedJobs()->create(['job_listing_id' => $job->id]);
        $job->delete();
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_deleting_a_seeker_removes_only_their_dependent_records(): void
    {
        $job = $this->job();
        $user = $this->seeker();
        $user->profile()->create([]);
        $user->applications()->create(['job_listing_id' => $job->id]);
        $user->savedJobs()->create(['job_listing_id' => $job->id]);
        $user->delete();
        $this->assertDatabaseCount('profiles', 0);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
        $this->assertDatabaseHas('job_listings', ['id' => $job->id]);
    }

    public function test_deleting_a_company_cascades_through_its_jobs(): void
    {
        $company = $this->company();
        $job = $this->job($company);
        $user = $this->seeker();
        $user->applications()->create(['job_listing_id' => $job->id]);
        $user->savedJobs()->create(['job_listing_id' => $job->id]);
        $company->delete();
        $this->assertDatabaseCount('job_listings', 0);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_existing_company_and_seeker_workflow(): void
    {
        $company = $this->company();
        $user = $this->seeker();
        $jobId = $this->actingAs($company)->postJson('/api/jobs', $this->jobData())
            ->assertCreated()->json('data.id');
        $this->assertDatabaseHas('job_listings', ['id' => $jobId, 'user_id' => $company->id]);

        $this->actingAs($user)->postJson("/api/jobs/$jobId/save")->assertCreated();
        $this->postJson("/api/jobs/$jobId/save")->assertOk();
        $this->assertDatabaseCount('saved_jobs', 1);
        $applicationId = $this->postJson("/api/jobs/$jobId/applications")
            ->assertCreated()->assertJsonPath('application.status', 'pending')->json('application.id');
        $this->postJson("/api/jobs/$jobId/applications")->assertStatus(409);
        $this->actingAs($company)->patchJson("/api/applications/$applicationId/status", ['status' => 'accepted'])
            ->assertOk();
        $this->assertDatabaseHas('applications', ['id' => $applicationId, 'user_id' => $user->id, 'status' => 'accepted']);
        $this->actingAs($user)->getJson('/api/applications')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/dashboard/job-seeker')->assertOk();
        $this->actingAs($company)->getJson('/api/dashboard/company')->assertOk();
    }

    public function test_other_companies_cannot_change_job_or_application_records(): void
    {
        $job = $this->job();
        $application = $this->seeker()->applications()->create(['job_listing_id' => $job->id]);
        $this->actingAs($this->company())->putJson("/api/jobs/$job->id", $this->jobData())->assertForbidden();
        $this->deleteJson("/api/jobs/$job->id")->assertForbidden();
        $this->patchJson("/api/applications/$application->id/status", ['status' => 'accepted'])->assertForbidden();
        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'pending']);
    }

    #[DataProvider('optionalSalaries')]
    public function test_salary_bounds_are_independently_optional(?int $minimum, ?int $maximum): void
    {
        $this->actingAs($this->company())->postJson('/api/jobs', $this->jobData([
            'salary_min' => $minimum, 'salary_max' => $maximum,
        ]))->assertCreated();
    }

    public static function optionalSalaries(): array
    {
        return ['neither' => [null, null], 'minimum only' => [1500, null], 'maximum only' => [null, 2500], 'equal' => [1500, 1500]];
    }

    public function test_invalid_salary_ranges_are_rejected_without_writing_a_job(): void
    {
        $this->actingAs($this->company())->postJson('/api/jobs', $this->jobData(['salary_min' => -1]))
            ->assertUnprocessable()->assertJsonValidationErrors('salary_min');
        $this->postJson('/api/jobs', $this->jobData(['salary_min' => 3000, 'salary_max' => 2000]))
            ->assertUnprocessable()->assertJsonValidationErrors('salary_max');
        $this->assertDatabaseCount('job_listings', 0);
    }

    public function test_editing_a_job_accepts_a_maximum_salary_without_a_minimum(): void
    {
        $company = $this->company();
        $job = $this->job($company);
        $this->actingAs($company)->putJson("/api/jobs/$job->id", $this->jobData([
            'salary_min' => null, 'salary_max' => 2500,
        ]))->assertOk();
        $this->assertDatabaseHas('job_listings', ['id' => $job->id, 'salary_min' => null, 'salary_max' => 2500]);
    }

    public function test_only_active_jobs_are_available_to_candidates(): void
    {
        $active = $this->job();
        $draft = $this->job();
        $draft->update(['status' => 'draft']);
        $closed = $this->job();
        $closed->update(['status' => 'closed']);

        $this->getJson('/api/jobs')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);
        $this->getJson("/api/jobs/$draft->id")->assertNotFound();
        $this->getJson("/api/jobs/$closed->id")->assertNotFound();

        $this->actingAs($this->seeker())->postJson("/api/jobs/$draft->id/applications")->assertStatus(409);
        $this->postJson("/api/jobs/$closed->id/applications")->assertStatus(409);
        $this->assertDatabaseCount('applications', 0);

        // Companies must still be able to load their own inactive jobs for editing.
        $this->actingAs($draft->user)->getJson("/api/jobs/$draft->id")->assertOk();
    }

    public function test_an_ownerless_legacy_draft_is_not_public(): void
    {
        $job = $this->job();
        $job->status = 'draft';
        $job->user_id = null;
        $job->save();

        $this->getJson("/api/jobs/$job->id")->assertNotFound();
        $this->actingAs($this->company())->getJson("/api/jobs/$job->id")->assertNotFound();
    }

    #[DataProvider('invalidDatabaseValues')]
    public function test_mysql_rejects_invalid_values_even_without_api_validation(string $table, array $changes): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('CHECK constraints must be verified on MySQL/MariaDB.');
        }

        $job = $this->job();
        $user = $this->seeker();
        $application = $user->applications()->create(['job_listing_id' => $job->id]);
        $id = match ($table) {
            'users' => $user->id,
            'job_listings' => $job->id,
            'applications' => $application->id,
        };
        $this->expectException(QueryException::class);
        DB::table($table)->where('id', $id)->update($changes);
    }

    public static function invalidDatabaseValues(): array
    {
        return [
            'user role' => ['users', ['role' => 'admin']],
            'role casing' => ['users', ['role' => 'Company']],
            'job type' => ['job_listings', ['type' => 'Unknown']],
            'job status' => ['job_listings', ['status' => 'Open']],
            'salary order' => ['job_listings', ['salary_min' => 3000, 'salary_max' => 1000]],
            'negative salary' => ['job_listings', ['salary_min' => -1]],
            'application status' => ['applications', ['status' => 'Interviewing']],
            'application status casing' => ['applications', ['status' => 'Accepted']],
        ];
    }
}
