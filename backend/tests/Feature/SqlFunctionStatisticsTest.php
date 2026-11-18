<?php

namespace Tests\Feature;

use App\Database\CareerHubFunctions;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SqlFunctionStatisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== ':memory:' && ! str_ends_with($database, '_testing')) {
            throw new \RuntimeException('Use an isolated _testing database.');
        }
    }

    private function requireNative(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Stored SQL functions require MySQL/MariaDB.');
        }
    }

    private function countUsing(string $name, ?int $id): int
    {
        return (int) DB::selectOne("SELECT `$name`(?) AS total", [$id])->total;
    }

    private function user(string $role = 'job_seeker'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function job(User $company, string $status = 'active'): JobListing
    {
        return $company->jobListings()->create([
            'title' => 'CareerHub Reporting Fixture', 'description' => 'SQL function verification.',
            'location' => 'Beirut', 'type' => 'Full-Time', 'status' => $status, 'currency' => 'USD',
        ]);
    }

    private function fixture(): array
    {
        $company = $this->user('company');
        $otherCompany = $this->user('company');
        $seeker = $this->user();
        $otherSeeker = $this->user();
        $active = $this->job($company);
        $closed = $this->job($company);
        $draft = $this->job($company, 'draft');
        $otherJob = $this->job($otherCompany);
        $seeker->applications()->create(['job_listing_id' => $active->id, 'status' => 'pending']);
        $accepted = $seeker->applications()->create(['job_listing_id' => $closed->id, 'status' => 'pending']);
        $accepted->update(['status' => 'accepted']);
        $rejected = $seeker->applications()->create(['job_listing_id' => $otherJob->id, 'status' => 'pending']);
        $rejected->update(['status' => 'rejected']);
        $another = $otherSeeker->applications()->create(['job_listing_id' => $active->id, 'status' => 'pending']);
        $another->update(['status' => 'rejected']);
        $closed->update(['status' => 'closed']);
        $seeker->savedJobs()->create(['job_listing_id' => $active->id]);
        $seeker->savedJobs()->create(['job_listing_id' => $otherJob->id]);
        $otherSeeker->savedJobs()->create(['job_listing_id' => $active->id]);

        return compact('company', 'otherCompany', 'seeker', 'otherSeeker', 'active', 'closed', 'draft', 'otherJob');
    }

    public function test_all_six_functions_are_reading_data_and_not_declared_deterministic(): void
    {
        $this->requireNative();
        $routines = DB::table('information_schema.ROUTINES')
            ->where('ROUTINE_SCHEMA', DB::connection()->getDatabaseName())
            ->whereIn('ROUTINE_NAME', CareerHubFunctions::NAMES)->get();
        $this->assertCount(6, $routines);
        $this->assertCount(6, CareerHubFunctions::definitions());
        foreach ($routines as $routine) {
            $this->assertSame('FUNCTION', $routine->ROUTINE_TYPE);
            $this->assertSame('NO', $routine->IS_DETERMINISTIC);
            $this->assertSame('READS SQL DATA', $routine->SQL_DATA_ACCESS);
            $this->assertSame('INVOKER', $routine->SECURITY_TYPE);
            $this->assertStringContainsString('unsigned', strtolower($routine->DTD_IDENTIFIER));
        }
    }

    #[DataProvider('emptyIds')]
    public function test_null_and_unknown_ids_return_zero(?int $id): void
    {
        $this->requireNative();
        foreach (CareerHubFunctions::NAMES as $name) {
            $this->assertSame(0, $this->countUsing($name, $id));
        }
    }

    public static function emptyIds(): array
    {
        return ['null' => [null], 'zero' => [0], 'unknown' => [999999], 'large id' => [PHP_INT_MAX]];
    }

    public function test_counts_are_scoped_to_the_correct_user_company_and_job(): void
    {
        $this->requireNative();
        extract($this->fixture());
        $this->assertSame(3, $this->countUsing('ch_user_application_count', $seeker->id));
        $this->assertSame(1, $this->countUsing('ch_user_application_count', $otherSeeker->id));
        $this->assertSame(2, $this->countUsing('ch_user_saved_job_count', $seeker->id));
        $this->assertSame(1, $this->countUsing('ch_user_saved_job_count', $otherSeeker->id));
        $this->assertSame(3, $this->countUsing('ch_company_job_count', $company->id));
        $this->assertSame(1, $this->countUsing('ch_company_job_count', $otherCompany->id));
        $this->assertSame(3, $this->countUsing('ch_company_application_count', $company->id));
        $this->assertSame(1, $this->countUsing('ch_company_application_count', $otherCompany->id));
        $this->assertSame(2, $this->countUsing('ch_job_application_count', $active->id));
        $this->assertSame(1, $this->countUsing('ch_job_application_count', $closed->id));
        $this->assertSame(0, $this->countUsing('ch_job_application_count', $draft->id));
        $this->assertSame(0, $this->countUsing('ch_company_job_count', $seeker->id));
        $this->assertSame(0, $this->countUsing('ch_user_application_count', $company->id));
    }

    public function test_active_job_counts_exclude_drafts_and_closed_jobs(): void
    {
        $this->requireNative();
        extract($this->fixture());
        $this->assertSame(1, $this->countUsing('ch_company_active_job_count', $company->id));
        $active->update(['status' => 'closed']);
        $this->assertSame(0, $this->countUsing('ch_company_active_job_count', $company->id));
        $draft->update(['status' => 'active']);
        $this->assertSame(1, $this->countUsing('ch_company_active_job_count', $company->id));
        $this->assertSame(3, $this->countUsing('ch_company_job_count', $company->id));
    }

    public function test_history_and_status_changes_do_not_multiply_application_counts(): void
    {
        $this->requireNative();
        extract($this->fixture());
        $application = $seeker->applications()->where('job_listing_id', $active->id)->firstOrFail();
        foreach (['accepted', 'rejected', 'pending'] as $status) {
            $application->update(['status' => $status]);
        }
        $this->assertGreaterThan(4, DB::table('application_status_history')->count());
        $this->assertSame(3, $this->countUsing('ch_company_application_count', $company->id));
        $this->assertSame(3, $this->countUsing('ch_user_application_count', $seeker->id));
        $this->assertSame(2, $this->countUsing('ch_job_application_count', $active->id));
    }

    public function test_ownerless_legacy_jobs_are_not_attributed_to_a_company(): void
    {
        $this->requireNative();
        extract($this->fixture());
        $legacy = $this->job($company);
        $legacy->user_id = null;
        $legacy->save();
        $seeker->applications()->create(['job_listing_id' => $legacy->id, 'status' => 'pending']);
        $this->assertSame(3, $this->countUsing('ch_company_job_count', $company->id));
        $this->assertSame(3, $this->countUsing('ch_company_application_count', $company->id));
        $this->assertSame(1, $this->countUsing('ch_company_application_count', $otherCompany->id));
        $this->assertSame(1, $this->countUsing('ch_job_application_count', $legacy->id));
    }

    public function test_counts_follow_uncommitted_writes_and_rollback(): void
    {
        $this->requireNative();
        $company = $this->user('company');
        $job = $this->job($company);
        $seeker = $this->user();
        $level = DB::connection()->transactionLevel();
        $this->assertSame(0, $this->countUsing('ch_job_application_count', $job->id));
        DB::beginTransaction();
        $seeker->applications()->create(['job_listing_id' => $job->id, 'status' => 'pending']);
        $seeker->savedJobs()->create(['job_listing_id' => $job->id]);
        $this->assertSame(1, $this->countUsing('ch_job_application_count', $job->id));
        $this->assertSame(1, $this->countUsing('ch_user_saved_job_count', $seeker->id));
        $this->assertSame($level + 1, DB::connection()->transactionLevel());
        DB::rollBack();
        $this->assertSame(0, $this->countUsing('ch_job_application_count', $job->id));
        $this->assertSame(0, $this->countUsing('ch_company_application_count', $company->id));
        $this->assertSame(0, $this->countUsing('ch_user_saved_job_count', $seeker->id));
    }

    public function test_cascading_deletions_are_reflected_in_subsequent_counts(): void
    {
        $this->requireNative();
        extract($this->fixture());
        $active->delete();
        $this->assertSame(2, $this->countUsing('ch_company_job_count', $company->id));
        $this->assertSame(0, $this->countUsing('ch_company_active_job_count', $company->id));
        $this->assertSame(1, $this->countUsing('ch_company_application_count', $company->id));
        $this->assertSame(2, $this->countUsing('ch_user_application_count', $seeker->id));
        $this->assertSame(0, $this->countUsing('ch_user_application_count', $otherSeeker->id));
        $this->assertSame(1, $this->countUsing('ch_user_saved_job_count', $seeker->id));
        $otherCompany->delete();
        $this->assertSame(0, $this->countUsing('ch_company_job_count', $otherCompany->id));
        $this->assertSame(1, $this->countUsing('ch_user_application_count', $seeker->id));
        $this->assertSame(0, $this->countUsing('ch_user_saved_job_count', $seeker->id));
        $seeker->delete();
        $this->assertSame(0, $this->countUsing('ch_company_application_count', $company->id));
    }

    public function test_functions_work_inside_a_read_only_transaction(): void
    {
        $this->requireNative();
        $config = DB::connection()->getConfig();
        $pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $pdo->exec('START TRANSACTION READ ONLY');
        try {
            foreach (CareerHubFunctions::NAMES as $name) {
                $this->assertSame(0, (int) $pdo->query("SELECT `$name`(NULL)")->fetchColumn());
            }
            $this->assertTrue($pdo->inTransaction());
        } finally {
            $pdo->rollBack();
        }
    }

    public function test_reading_all_functions_does_not_modify_recruitment_or_history_rows(): void
    {
        $this->requireNative();
        extract($this->fixture());
        $snapshot = function () {
            $rows = [];
            foreach (['users', 'job_listings', 'profiles', 'applications', 'saved_jobs', 'application_status_history'] as $table) {
                $rows[$table] = DB::table($table)->orderBy('id')->get()->toJson();
            }

            return $rows;
        };
        $before = $snapshot();
        foreach (CareerHubFunctions::NAMES as $name) {
            $this->countUsing($name, $seeker->id);
            $this->countUsing($name, $company->id);
        }
        $this->assertSame($before, $snapshot());
    }

    public function test_existing_dashboards_return_the_same_scoped_integer_statistics(): void
    {
        extract($this->fixture());
        $this->actingAs($seeker)->getJson('/api/dashboard/job-seeker')->assertOk()
            ->assertJsonPath('data.stats.total_applications', 3)->assertJsonPath('data.stats.saved_jobs', 2);
        $this->actingAs($company)->getJson('/api/dashboard/company')->assertOk()
            ->assertJsonPath('data.stats.total_jobs', 3)->assertJsonPath('data.stats.total_applications', 3);
        $this->actingAs($otherCompany)->getJson('/api/dashboard/company')->assertOk()
            ->assertJsonPath('data.stats.total_jobs', 1)->assertJsonPath('data.stats.total_applications', 1);
    }

    public function test_dashboard_totals_are_not_limited_to_the_recent_records_list(): void
    {
        $company = $this->user('company');
        $seeker = $this->user();
        for ($index = 0; $index < 7; $index++) {
            $job = $this->job($company);
            if ($index < 6) {
                $seeker->applications()->create(['job_listing_id' => $job->id, 'status' => 'pending']);
                $seeker->savedJobs()->create(['job_listing_id' => $job->id]);
            }
        }
        $this->actingAs($seeker)->getJson('/api/dashboard/job-seeker')->assertOk()
            ->assertJsonPath('data.stats.total_applications', 6)->assertJsonPath('data.stats.saved_jobs', 6)
            ->assertJsonCount(3, 'data.recent_applications')->assertJsonCount(3, 'data.recent_saved_jobs');
        $this->actingAs($company)->getJson('/api/dashboard/company')->assertOk()
            ->assertJsonPath('data.stats.total_jobs', 7)->assertJsonPath('data.stats.total_applications', 6)
            ->assertJsonCount(5, 'data.recent_jobs');
    }

    public function test_dashboard_accounts_without_records_receive_integer_zeroes(): void
    {
        $this->actingAs($this->user())->getJson('/api/dashboard/job-seeker')->assertOk()
            ->assertJsonPath('data.stats.total_applications', 0)->assertJsonPath('data.stats.saved_jobs', 0);
        $this->actingAs($this->user('company'))->getJson('/api/dashboard/company')->assertOk()
            ->assertJsonPath('data.stats.total_jobs', 0)->assertJsonPath('data.stats.total_applications', 0);
    }

    public function test_dashboard_authentication_and_role_checks_still_apply(): void
    {
        $this->getJson('/api/dashboard/company')->assertUnauthorized();
        $this->getJson('/api/dashboard/job-seeker')->assertUnauthorized();
        $this->actingAs($this->user())->getJson('/api/dashboard/company')->assertForbidden();
        $this->actingAs($this->user('company'))->getJson('/api/dashboard/job-seeker')->assertForbidden();
    }
}
