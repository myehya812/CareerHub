<?php

namespace Tests\Feature;

use App\Database\CareerHubTriggers;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\User;
use App\Services\RecruitmentTransactions;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TriggerIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== ':memory:' && ! str_ends_with($database, '_testing')) {
            throw new \RuntimeException('Use an isolated _testing database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Trigger verification requires MySQL/MariaDB.');
        }
    }

    private function user(string $role = 'job_seeker'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function job(?User $owner = null): JobListing
    {
        return ($owner ?? $this->user('company'))->jobListings()->create([
            'title' => 'CareerHub Trigger Verification', 'description' => 'Recruitment fixture.',
            'location' => 'Beirut', 'type' => 'Full-Time', 'status' => 'active', 'currency' => 'USD',
        ]);
    }

    private function application(?User $seeker = null, ?JobListing $job = null): Application
    {
        return ($seeker ?? $this->user())->applications()->create([
            'job_listing_id' => ($job ?? $this->job())->id, 'status' => 'pending',
        ]);
    }

    private function assertSqlRejected(int $code, callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected the trigger to reject the SQL statement.');
        } catch (QueryException $error) {
            $this->assertSame($code, (int) $error->errorInfo[1]);
        }
    }

    public function test_all_nine_triggers_are_installed(): void
    {
        $triggers = DB::table('information_schema.TRIGGERS')
            ->where('TRIGGER_SCHEMA', DB::connection()->getDatabaseName())
            ->whereIn('TRIGGER_NAME', CareerHubTriggers::NAMES)->get();
        $this->assertCount(9, $triggers);
        $this->assertCount(9, CareerHubTriggers::definitions());
        $this->assertSame(['AFTER', 'BEFORE'], $triggers->pluck('ACTION_TIMING')->unique()->sort()->values()->all());
    }

    public function test_a_direct_sql_application_insert_automatically_creates_history(): void
    {
        $seeker = $this->user();
        $job = $this->job();
        $id = DB::table('applications')->insertGetId(['user_id' => $seeker->id, 'job_listing_id' => $job->id, 'status' => 'pending']);
        $history = Application::findOrFail($id)->statusHistory()->firstOrFail();
        $this->assertSame('created', $history->event_type);
        $this->assertNull($history->old_status);
        $this->assertSame('pending', $history->new_status);
        $this->assertNotNull($history->changed_at);
        $this->assertSame($id, $history->application->id);
    }

    public function test_history_records_the_correct_instant_for_a_non_utc_sql_session(): void
    {
        $this->assertSame('+00:00', DB::selectOne('SELECT @@session.time_zone AS zone')->zone);
        DB::statement("SET SESSION time_zone='+02:00'");
        try {
            $application = $this->application();
            $epoch = (int) DB::selectOne('SELECT UNIX_TIMESTAMP(changed_at) AS recorded_epoch FROM application_status_history WHERE application_id=?', [$application->id])->recorded_epoch;
            $this->assertLessThanOrEqual(2, abs(time() - $epoch));
        } finally {
            DB::statement("SET SESSION time_zone='+00:00'");
        }
    }

    public function test_only_real_status_changes_add_history_rows(): void
    {
        $application = $this->application();
        $application->update(['status' => 'pending']);
        DB::table('applications')->where('id', $application->id)->update(['updated_at' => '2026-11-17 12:00:00']);
        $this->assertDatabaseCount('application_status_history', 1);
        foreach (['accepted', 'rejected', 'pending'] as $status) {
            $application->update(['status' => $status]);
        }
        $history = $application->statusHistory()->get();
        $this->assertSame([null, 'pending', 'accepted', 'rejected'], $history->pluck('old_status')->all());
        $this->assertSame(['pending', 'accepted', 'rejected', 'pending'], $history->pluck('new_status')->all());
        $this->assertSame(['created', 'status_changed', 'status_changed', 'status_changed'], $history->pluck('event_type')->all());
    }

    public function test_status_review_still_works_after_the_job_is_closed(): void
    {
        $application = $this->application();
        $application->jobListing->update(['status' => 'closed']);
        $application->update(['status' => 'accepted']);
        $this->assertSame('accepted', $application->fresh()->status);
        $this->assertDatabaseCount('application_status_history', 2);
    }

    public function test_invalid_status_does_not_write_history(): void
    {
        $application = $this->application();
        try {
            DB::table('applications')->where('id', $application->id)->update(['status' => 'Interviewing']);
            $this->fail('Expected the existing status CHECK to fail.');
        } catch (QueryException $error) {
            $this->assertNotEmpty($error->errorInfo);
        }
        $this->assertSame('pending', $application->fresh()->status);
        $this->assertDatabaseCount('application_status_history', 1);
    }

    public function test_a_rejected_row_rolls_back_earlier_trigger_writes_in_a_bulk_insert(): void
    {
        $company = $this->user('company');
        $job = $this->job($company);
        $seeker = $this->user();
        $this->assertSqlRejected(30002, fn () => DB::table('applications')->insert([
            ['user_id' => $seeker->id, 'job_listing_id' => $job->id, 'status' => 'pending'],
            ['user_id' => $company->id, 'job_listing_id' => $job->id, 'status' => 'pending'],
        ]));
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('application_status_history', 0);
    }

    public function test_a_failed_bulk_update_rolls_back_earlier_status_history_events(): void
    {
        $first = $this->application();
        $second = $this->application();
        try {
            DB::update('UPDATE applications SET status=CASE WHEN id=? THEN ? ELSE ? END WHERE id IN (?,?) ORDER BY id',
                [$first->id, 'accepted', 'Interviewing', $first->id, $second->id]);
            $this->fail('Expected an invalid status in the bulk update to fail.');
        } catch (QueryException $error) {
            $this->assertNotEmpty($error->errorInfo);
        }
        $this->assertSame('pending', $first->fresh()->status);
        $this->assertSame('pending', $second->fresh()->status);
        $this->assertDatabaseCount('application_status_history', 2);
    }

    public function test_application_insert_and_update_history_roll_back_with_the_caller(): void
    {
        $seeker = $this->user();
        $job = $this->job();
        DB::beginTransaction();
        $application = $this->application($seeker, $job);
        $application->update(['status' => 'accepted']);
        $this->assertDatabaseCount('application_status_history', 2);
        DB::rollBack();
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('application_status_history', 0);
        $this->assertDatabaseHas('job_listings', ['id' => $job->id]);
    }

    public function test_rolling_back_a_status_change_restores_the_previous_history(): void
    {
        $application = $this->application();
        DB::beginTransaction();
        $application->update(['status' => 'accepted']);
        $this->assertDatabaseCount('application_status_history', 2);
        DB::rollBack();
        $this->assertSame('pending', $application->fresh()->status);
        $this->assertDatabaseCount('application_status_history', 1);
    }

    public function test_job_deletion_cascades_to_history_and_rollback_restores_everything(): void
    {
        $application = $this->application();
        $job = $application->jobListing;
        $application->update(['status' => 'accepted']);
        DB::beginTransaction();
        $job->delete();
        $this->assertDatabaseCount('application_status_history', 0);
        DB::rollBack();
        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('application_status_history', 2);
        JobListing::findOrFail($job->id)->delete();
        $this->assertDatabaseCount('application_status_history', 0);
    }

    public function test_deleting_a_seeker_removes_their_application_history(): void
    {
        $application = $this->application();
        $job = $application->jobListing;
        $application->user->delete();
        $this->assertDatabaseCount('application_status_history', 0);
        $this->assertDatabaseHas('job_listings', ['id' => $job->id]);
    }

    public function test_direct_sql_cannot_make_a_job_seeker_publish_a_job(): void
    {
        $seeker = $this->user();
        $this->assertSqlRejected(30002, fn () => $this->job($seeker));
        $job = $this->job();
        $this->assertSqlRejected(30002, fn () => DB::table('job_listings')->where('id', $job->id)->update(['user_id' => $seeker->id]));
        $this->assertSame($job->user_id, $job->fresh()->user_id);
    }

    public function test_direct_sql_cannot_make_a_company_apply_or_bookmark(): void
    {
        $company = $this->user('company');
        $job = $this->job($company);
        $this->assertSqlRejected(30002, fn () => $company->applications()->create(['job_listing_id' => $job->id, 'status' => 'pending']));
        $this->assertSqlRejected(30002, fn () => $company->savedJobs()->create(['job_listing_id' => $job->id]));
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('application_status_history', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
    }

    #[DataProvider('inactiveStatuses')]
    public function test_direct_sql_cannot_apply_to_an_inactive_job(string $status): void
    {
        $job = $this->job();
        $job->update(['status' => $status]);
        $this->assertSqlRejected(30003, fn () => $this->application(null, $job));
        $this->assertDatabaseCount('application_status_history', 0);
    }

    public static function inactiveStatuses(): array
    {
        return ['draft' => ['draft'], 'closed' => ['closed']];
    }

    public function test_application_identity_cannot_be_reassigned(): void
    {
        $application = $this->application();
        $other = $this->user();
        $otherJob = $this->job();
        foreach ([['user_id' => $other->id], ['job_listing_id' => $otherJob->id], ['id' => 999999]] as $changes) {
            $this->assertSqlRejected(30004, fn () => DB::table('applications')->where('id', $application->id)->update($changes));
        }
        $this->assertDatabaseCount('application_status_history', 1);
    }

    public function test_bookmark_identity_cannot_be_reassigned(): void
    {
        $seeker = $this->user();
        $job = $this->job();
        $saved = $seeker->savedJobs()->create(['job_listing_id' => $job->id]);
        foreach ([['user_id' => $this->user()->id], ['job_listing_id' => $this->job()->id], ['id' => 999999]] as $changes) {
            $this->assertSqlRejected(30004, fn () => DB::table('saved_jobs')->where('id', $saved->id)->update($changes));
        }
        $this->assertDatabaseCount('saved_jobs', 1);
    }

    public function test_role_changes_cannot_invalidate_existing_recruitment_records(): void
    {
        $company = $this->user('company');
        $job = $this->job($company);
        $seeker = $this->user();
        $this->application($seeker, $job);
        $bookmarkUser = $this->user();
        $bookmarkUser->savedJobs()->create(['job_listing_id' => $job->id]);
        $this->assertSqlRejected(30003, fn () => $company->update(['role' => 'job_seeker']));
        $this->assertSqlRejected(30003, fn () => $seeker->update(['role' => 'company']));
        $this->assertSqlRejected(30003, fn () => $bookmarkUser->update(['role' => 'company']));
        $this->assertSame('company', $company->fresh()->role);
        $this->assertSame('job_seeker', $seeker->fresh()->role);
        $this->assertSame('job_seeker', $bookmarkUser->fresh()->role);
    }

    public function test_unreferenced_role_changes_and_ordinary_user_updates_are_allowed(): void
    {
        $user = $this->user();
        $user->update(['role' => 'company']);
        $user->update(['role' => 'job_seeker']);
        $this->application($user);
        $user->update(['name' => 'Updated Name']);
        $this->assertSame('Updated Name', $user->fresh()->name);
    }

    public function test_ownerless_legacy_jobs_can_be_linked_to_a_valid_company(): void
    {
        $job = $this->job();
        $job->user_id = null;
        $job->save();
        $company = $this->user('company');
        $job->user_id = $company->id;
        $job->save();
        $this->assertSame($company->id, $job->fresh()->user_id);
    }

    public function test_procedures_keep_application_ids_distinct_from_history_ids(): void
    {
        $company = $this->user('company');
        $job = $this->job($company);
        $seeker = $this->user();
        $first = $this->application($seeker, $job);
        $first->update(['status' => 'accepted']);
        $secondSeeker = $this->user();
        $result = app(RecruitmentTransactions::class)->execute('ch_submit_application', [$secondSeeker->id, $job->id], fn () => []);
        $second = Application::findOrFail($result['application_id']);
        $this->assertSame($secondSeeker->id, $second->user_id);
        $this->assertNotSame($second->id, $second->statusHistory()->firstOrFail()->id);
    }

    public function test_api_submission_and_status_update_create_history_without_controller_duplicates(): void
    {
        $company = $this->user('company');
        $job = $this->job($company);
        $this->actingAs($this->user());
        $id = $this->postJson("/api/jobs/$job->id/applications")->assertCreated()->json('application.id');
        $this->postJson("/api/jobs/$job->id/applications")->assertStatus(409);
        $this->actingAs($company)->patchJson("/api/applications/$id/status", ['status' => 'accepted'])->assertOk();
        $this->patchJson("/api/applications/$id/status", ['status' => 'accepted'])->assertOk();
        $this->assertDatabaseCount('application_status_history', 2);
        $this->assertDatabaseHas('application_status_history', ['application_id' => $id, 'old_status' => 'pending', 'new_status' => 'accepted', 'event_type' => 'status_changed']);
    }

    public function test_invalid_manual_history_events_are_rejected_by_checks(): void
    {
        $application = $this->application();
        foreach ([
            ['old_status' => null, 'new_status' => 'pending', 'event_type' => 'status_changed'],
            ['old_status' => 'pending', 'new_status' => 'pending', 'event_type' => 'status_changed'],
            ['old_status' => null, 'new_status' => 'Interviewing', 'event_type' => 'created'],
        ] as $event) {
            try {
                DB::table('application_status_history')->insert(['application_id' => $application->id, ...$event]);
                $this->fail('Invalid history event was accepted.');
            } catch (QueryException $error) {
                $this->assertNotEmpty($error->errorInfo);
            }
        }
        $this->assertDatabaseCount('application_status_history', 1);
    }
}
