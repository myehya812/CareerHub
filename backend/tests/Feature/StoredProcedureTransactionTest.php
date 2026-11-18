<?php

namespace Tests\Feature;

use App\Database\CareerHubProcedures;
use App\Models\JobListing;
use App\Models\User;
use App\Services\RecruitmentTransactions;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDO;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class StoredProcedureTransactionTest extends TestCase
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
            $this->markTestSkipped('Stored procedures require MySQL/MariaDB.');
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

    private function runProcedure(string $name, array $parameters): array
    {
        return app(RecruitmentTransactions::class)->execute($name, $parameters, fn () => throw new \LogicException('Unexpected fallback.'));
    }

    private function createJob(User $company, array $overrides = []): int
    {
        $values = array_replace([
            'title' => 'CareerHub SQL Developer', 'description' => 'Recruitment procedure verification.',
            'location' => 'Beirut', 'type' => 'Full-Time', 'salary_min' => 1000,
            'salary_max' => 2000, 'currency' => 'USD', 'status' => 'active',
        ], $overrides);

        return (int) $this->runProcedure('ch_create_job', [$company->id, ...array_values($values)])['job_id'];
    }

    private function assertRejected(int $status, callable $action): void
    {
        try {
            $action();
            $this->fail('Expected the stored procedure to reject the request.');
        } catch (HttpException $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    public function test_all_seven_routines_are_installed_with_invoker_security(): void
    {
        $routines = DB::table('information_schema.ROUTINES')
            ->where('ROUTINE_SCHEMA', DB::connection()->getDatabaseName())
            ->whereIn('ROUTINE_NAME', CareerHubProcedures::NAMES)->get();
        $this->assertCount(7, $routines);
        foreach ($routines as $routine) {
            $this->assertSame('INVOKER', $routine->SECURITY_TYPE);
        }
        $this->assertCount(7, CareerHubProcedures::definitions());
    }

    public function test_a_direct_call_without_a_transaction_fails_before_writing(): void
    {
        $config = DB::connection()->getConfig();
        $pdo = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
            $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        try {
            $pdo->query("CALL ch_create_job(999999,'Test','Test','Beirut','Full-Time',NULL,NULL,'USD','active')");
            $this->fail('A caller transaction is required.');
        } catch (PDOException $error) {
            $this->assertSame(30005, (int) $error->errorInfo[1]);
        }
        $this->assertFalse($pdo->inTransaction());
        $this->assertDatabaseCount('job_listings', 0);
    }

    public function test_compound_workflow_respects_the_callers_rollback(): void
    {
        $company = $this->company();
        $seeker = $this->seeker();
        $level = DB::connection()->transactionLevel();
        DB::beginTransaction();
        $job = $this->createJob($company);
        $this->runProcedure('ch_submit_application', [$seeker->id, $job]);
        $this->runProcedure('ch_save_job', [$seeker->id, $job]);
        $this->assertDatabaseCount('job_listings', 1);
        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('saved_jobs', 1);
        $this->assertSame($level + 1, DB::connection()->transactionLevel());
        DB::rollBack();
        $this->assertDatabaseCount('job_listings', 0);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_a_later_procedure_error_rolls_back_earlier_writes_in_the_same_transaction(): void
    {
        $company = $this->company();
        $seeker = $this->seeker();
        $this->assertRejected(422, function () use ($company, $seeker) {
            DB::transaction(function () use ($company, $seeker) {
                $job = $this->createJob($company);
                $application = $this->runProcedure('ch_submit_application', [$seeker->id, $job]);
                $this->runProcedure('ch_save_job', [$seeker->id, $job]);
                $this->runProcedure('ch_update_application_status', [$company->id, $application['application_id'], 'withdrawn']);
            });
        });
        $this->assertDatabaseCount('job_listings', 0);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
    }

    public function test_an_unexpected_sql_error_also_rolls_back_the_whole_workflow(): void
    {
        $company = $this->company();
        try {
            DB::transaction(function () use ($company) {
                $job = $this->createJob($company);
                DB::table('job_listings')->where('id', $job)->update(['salary_min' => 5000, 'salary_max' => 1000]);
            });
            $this->fail('Expected a CHECK failure.');
        } catch (QueryException $error) {
            $this->assertNotEmpty($error->errorInfo);
        }
        $this->assertDatabaseCount('job_listings', 0);
    }

    #[DataProvider('invalidJobData')]
    public function test_create_job_rejects_invalid_values(array $overrides): void
    {
        $company = $this->company();
        $this->assertRejected(422, fn () => $this->createJob($company, $overrides));
        $this->assertDatabaseCount('job_listings', 0);
    }

    public static function invalidJobData(): array
    {
        return [
            'negative salary' => [['salary_min' => -1]],
            'reversed salary' => [['salary_min' => 3000]],
            'salary exceeds uint capacity' => [['salary_max' => 4294967296]],
            'unknown type' => [['type' => 'Freelance']],
            'unknown status' => [['status' => 'Open']],
            'missing currency' => [['currency' => null]],
            'empty title' => [['title' => ' ']],
        ];
    }

    public function test_only_a_real_company_can_create_jobs(): void
    {
        $this->assertRejected(403, fn () => $this->createJob($this->seeker()));
        $this->assertRejected(404, fn () => $this->runProcedure('ch_create_job', [999999, 'Title', 'Description', 'Beirut', 'Full-Time', null, null, 'USD', 'active']));
        $this->assertDatabaseCount('job_listings', 0);
    }

    public function test_submission_validates_role_existence_job_status_and_uniqueness(): void
    {
        $company = $this->company();
        $seeker = $this->seeker();
        $job = $this->createJob($company);
        $draft = $this->createJob($company, ['status' => 'draft']);
        $this->assertRejected(403, fn () => $this->runProcedure('ch_submit_application', [$company->id, $job]));
        $this->assertRejected(404, fn () => $this->runProcedure('ch_submit_application', [999999, $job]));
        $this->assertRejected(404, fn () => $this->runProcedure('ch_submit_application', [$seeker->id, 999999]));
        $this->assertRejected(409, fn () => $this->runProcedure('ch_submit_application', [$seeker->id, $draft]));
        $application = $this->runProcedure('ch_submit_application', [$seeker->id, $job]);
        $this->assertRejected(409, fn () => $this->runProcedure('ch_submit_application', [$seeker->id, $job]));
        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseHas('applications', ['id' => $application['application_id'], 'status' => 'pending']);
    }

    public function test_status_changes_require_the_owning_company_and_existing_status_values(): void
    {
        $company = $this->company();
        $seeker = $this->seeker();
        $job = $this->createJob($company);
        $application = $this->runProcedure('ch_submit_application', [$seeker->id, $job])['application_id'];
        $this->assertRejected(403, fn () => $this->runProcedure('ch_update_application_status', [$this->company()->id, $application, 'accepted']));
        $this->assertRejected(403, fn () => $this->runProcedure('ch_update_application_status', [$seeker->id, $application, 'accepted']));
        $this->assertRejected(404, fn () => $this->runProcedure('ch_update_application_status', [$company->id, 999999, 'accepted']));
        $this->assertRejected(422, fn () => $this->runProcedure('ch_update_application_status', [$company->id, $application, 'Accepted']));
        foreach (['accepted', 'pending', 'rejected'] as $status) {
            $this->runProcedure('ch_update_application_status', [$company->id, $application, $status]);
            $this->assertDatabaseHas('applications', ['id' => $application, 'status' => $status]);
        }
    }

    public function test_bookmarks_are_idempotent_and_only_the_users_own_bookmark_is_removed(): void
    {
        $company = $this->company();
        $seeker = $this->seeker();
        $job = $this->createJob($company);
        $first = $this->runProcedure('ch_save_job', [$seeker->id, $job]);
        $second = $this->runProcedure('ch_save_job', [$seeker->id, $job]);
        $this->assertSame(1, (int) $first['created']);
        $this->assertSame(0, (int) $second['created']);
        $this->assertSame((int) $first['saved_job_id'], (int) $second['saved_job_id']);
        $this->assertRejected(403, fn () => $this->runProcedure('ch_save_job', [$company->id, $job]));
        $this->assertRejected(403, fn () => $this->runProcedure('ch_remove_saved_job', [$company->id, $job]));
        $this->assertRejected(404, fn () => $this->runProcedure('ch_remove_saved_job', [$this->seeker()->id, $job]));
        $this->assertRejected(404, fn () => $this->runProcedure('ch_save_job', [$seeker->id, 999999]));
        $this->assertDatabaseCount('saved_jobs', 1);
        $this->runProcedure('ch_remove_saved_job', [$seeker->id, $job]);
        $this->assertRejected(404, fn () => $this->runProcedure('ch_remove_saved_job', [$seeker->id, $job]));
        $this->assertDatabaseCount('saved_jobs', 0);
    }

    public function test_deleting_a_job_and_its_dependent_records_is_reversible_until_commit(): void
    {
        $company = $this->company();
        $seeker = $this->seeker();
        $job = $this->createJob($company);
        $this->runProcedure('ch_submit_application', [$seeker->id, $job]);
        $this->runProcedure('ch_save_job', [$seeker->id, $job]);
        $this->assertRejected(403, fn () => $this->runProcedure('ch_delete_job', [$this->company()->id, $job]));
        $this->assertRejected(403, fn () => $this->runProcedure('ch_delete_job', [$seeker->id, $job]));
        $this->assertRejected(404, fn () => $this->runProcedure('ch_delete_job', [$company->id, 999999]));
        DB::beginTransaction();
        $this->runProcedure('ch_delete_job', [$company->id, $job]);
        $this->assertDatabaseCount('job_listings', 0);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
        DB::rollBack();
        $this->assertDatabaseCount('job_listings', 1);
        $this->assertDatabaseCount('applications', 1);
        $this->assertDatabaseCount('saved_jobs', 1);
        $this->runProcedure('ch_delete_job', [$company->id, $job]);
        $this->assertDatabaseCount('job_listings', 0);
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
        $this->assertNotNull(User::find($seeker->id));
    }

    public function test_existing_api_delete_and_unsave_endpoints_use_the_transaction_service(): void
    {
        $company = $this->company();
        $seeker = $this->seeker();
        $job = $this->createJob($company);
        $this->actingAs($seeker)->postJson("/api/jobs/$job/save")->assertCreated();
        $this->deleteJson("/api/jobs/$job/save")->assertOk();
        $this->deleteJson("/api/jobs/$job/save")->assertNotFound();
        $this->postJson("/api/jobs/$job/applications")->assertCreated();
        $this->postJson("/api/jobs/$job/save")->assertCreated();
        $this->actingAs($company)->deleteJson("/api/jobs/$job")->assertNoContent();
        $this->assertDatabaseCount('applications', 0);
        $this->assertDatabaseCount('saved_jobs', 0);
    }
}
