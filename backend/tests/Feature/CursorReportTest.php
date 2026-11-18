<?php

namespace Tests\Feature;

use App\Database\CareerHubCursors;
use App\Models\JobListing;
use App\Models\User;
use App\Services\RecruitmentCursorReports;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CursorReportTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== ':memory:' && ! str_ends_with($database, '_testing')) {
            throw new \RuntimeException('Use an isolated _testing database.');
        }
    }

    private function reports(): RecruitmentCursorReports
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('SQL cursor reports require MySQL/MariaDB.');
        }

        return app(RecruitmentCursorReports::class);
    }

    private function user(string $role = 'job_seeker'): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function job(User $owner, string $status = 'active'): JobListing
    {
        return $owner->jobListings()->create([
            'title' => "Backend \"PHP\" \\ SQL\nمرحبا", 'description' => 'Cursor reporting fixture.',
            'location' => 'Beirut', 'type' => 'Full-Time', 'status' => $status,
            'currency' => 'USD', 'salary_min' => null, 'salary_max' => 2500,
        ]);
    }

    private function fixture(): array
    {
        $company = $this->user('company');
        $company->profile()->create(['company_name' => 'شركة CareerHub']);
        $otherCompany = $this->user('company');
        $seeker = $this->user();
        $otherSeeker = $this->user();
        $active = $this->job($company);
        $closed = $this->job($company);
        $draft = $this->job($company, 'draft');
        $otherJob = $this->job($otherCompany);
        $first = $seeker->applications()->create(['job_listing_id' => $active->id, 'status' => 'pending']);
        $other = $otherSeeker->applications()->create(['job_listing_id' => $active->id, 'status' => 'pending']);
        $other->update(['status' => 'accepted']);
        $second = $seeker->applications()->create(['job_listing_id' => $closed->id, 'status' => 'pending']);
        $second->update(['status' => 'accepted']);
        $third = $seeker->applications()->create(['job_listing_id' => $otherJob->id, 'status' => 'pending']);
        $third->update(['status' => 'rejected']);
        $closed->update(['status' => 'closed']);
        foreach (['accepted', 'rejected', 'pending'] as $status) {
            $first->update(['status' => $status]);
        }

        return compact('company','otherCompany','seeker','otherSeeker','active','closed','draft','otherJob','first','second','third');
    }

    private function rejected(int $status, callable $operation): void
    {
        try {
            $operation();
            $this->fail('Expected the cursor report to reject this request.');
        } catch (HttpException $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }

    public function test_three_read_only_invoker_cursor_procedures_are_installed(): void
    {
        $this->reports();
        $routines = DB::table('information_schema.ROUTINES')->where('ROUTINE_SCHEMA',DB::connection()->getDatabaseName())
            ->whereIn('ROUTINE_NAME',CareerHubCursors::NAMES)->get();
        $this->assertCount(3, $routines);
        $this->assertCount(3, CareerHubCursors::definitions());
        foreach ($routines as $routine) {
            $this->assertSame('PROCEDURE', $routine->ROUTINE_TYPE);
            $this->assertSame('READS SQL DATA', $routine->SQL_DATA_ACCESS);
            $this->assertSame('INVOKER', $routine->SECURITY_TYPE);
        }
    }

    public function test_empty_reports_do_not_emit_a_phantom_row(): void
    {
        $reports = $this->reports();
        $empty = ['rows' => [], 'row_count' => 0, 'next_after_id' => null];
        $this->assertSame($empty, $reports->companyJobs($this->user('company')->id));
        $this->assertSame($empty, $reports->userApplications($this->user()->id));
    }

    public function test_unicode_job_survives_the_write_procedure_and_cursor_report(): void
    {
        $reports = $this->reports();
        $company = $this->user('company');
        $title = 'مطور CareerHub 🚀';
        $description = 'فرصة عمل في بيروت';
        $location = 'بيروت';
        $result = app(\App\Services\RecruitmentTransactions::class)->execute('ch_create_job', [
            $company->id, $title, $description, $location, 'Full-Time', null, null, 'USD', 'active',
        ], fn () => throw new \LogicException('Native database required.'));

        $job = JobListing::findOrFail($result['job_id']);
        $this->assertSame($title, $job->title);
        $this->assertSame($description, $job->description);
        $row = $reports->companyJobs($company->id)['rows'][0];
        $this->assertSame($title, $row['title']);
        $this->assertSame($location, $row['location']);
    }

    public function test_company_report_is_scoped_and_counts_each_application_once(): void
    {
        $reports = $this->reports();
        extract($this->fixture());
        $result = $reports->companyJobs($company->id);
        $this->assertSame(3, $result['row_count']);
        $this->assertSame([$active->id,$closed->id,$draft->id], array_column($result['rows'],'job_id'));
        $this->assertSame(['active','closed','draft'], array_column($result['rows'],'status'));
        $this->assertSame([2,1,0], array_column($result['rows'],'applications_total'));
        $this->assertSame([1,0,0], array_column($result['rows'],'pending'));
        $this->assertSame([1,1,0], array_column($result['rows'],'accepted'));
        $this->assertSame([0,0,0], array_column($result['rows'],'rejected'));
        $this->assertSame($active->title, $result['rows'][0]['title']);
        $this->assertNull($result['rows'][0]['salary_min']);
        $this->assertSame(2500, $result['rows'][0]['salary_max']);
        $this->assertSame([$otherJob->id], array_column($reports->companyJobs($otherCompany->id)['rows'],'job_id'));
    }

    public function test_user_report_preserves_statuses_company_names_unicode_and_history_counts(): void
    {
        $reports = $this->reports();
        extract($this->fixture());
        $result = $reports->userApplications($seeker->id);
        $this->assertSame(3, $result['row_count']);
        $this->assertSame([$first->id,$second->id,$third->id], array_column($result['rows'],'application_id'));
        $this->assertSame(['pending','accepted','rejected'], array_column($result['rows'],'status'));
        $this->assertSame(['active','closed','active'], array_column($result['rows'],'job_status'));
        $this->assertSame([4,2,2], array_column($result['rows'],'history_events'));
        $this->assertSame('شركة CareerHub', $result['rows'][0]['company_name']);
        $this->assertSame($otherCompany->name, $result['rows'][2]['company_name']);
        $this->assertSame($active->title, $result['rows'][0]['title']);
        $this->assertSame(1, $reports->userApplications($otherSeeker->id)['row_count']);
    }

    public function test_application_history_is_available_to_its_seeker_and_owning_company_only(): void
    {
        $reports = $this->reports();
        extract($this->fixture());
        $seekerResult = $reports->applicationHistory($seeker->id,$first->id);
        $this->assertSame($seekerResult, $reports->applicationHistory($company->id,$first->id));
        $this->assertSame(4, $seekerResult['row_count']);
        $this->assertSame([null,'pending','accepted','rejected'], array_column($seekerResult['rows'],'old_status'));
        $this->assertSame(['pending','accepted','rejected','pending'], array_column($seekerResult['rows'],'new_status'));
        $this->assertSame(['created','status_changed','status_changed','status_changed'], array_column($seekerResult['rows'],'event_type'));
        $this->rejected(403, fn () => $reports->applicationHistory($otherSeeker->id,$first->id));
        $this->rejected(403, fn () => $reports->applicationHistory($otherCompany->id,$first->id));
    }

    public function test_all_reports_page_by_id_without_skipping_or_repeating_the_last_row(): void
    {
        $reports = $this->reports();
        extract($this->fixture());
        $cases = [
            [fn ($after) => $reports->companyJobs($company->id,$after,2), 'job_id', [$active->id,$closed->id,$draft->id]],
            [fn ($after) => $reports->userApplications($seeker->id,$after,2), 'application_id', [$first->id,$second->id,$third->id]],
            [fn ($after) => $reports->applicationHistory($seeker->id,$first->id,$after,2), 'history_id', $first->statusHistory()->pluck('id')->all()],
        ];
        foreach ($cases as [$load,$key,$expected]) {
            $after = 0;
            $ids = [];
            for ($page = 0; $page < 10; $page++) {
                $result = $load($after);
                $this->assertLessThanOrEqual(2,$result['row_count']);
                if ($result['row_count'] === 0) {
                    $this->assertSame([], $result['rows']);
                    $this->assertNull($result['next_after_id']);
                    break;
                }
                $pageIds = array_column($result['rows'],$key);
                $this->assertSame(end($pageIds),$result['next_after_id']);
                $this->assertGreaterThan($after,$pageIds[0]);
                $ids = [...$ids,...$pageIds];
                $after = $result['next_after_id'];
            }
            $this->assertSame($expected,$ids);
        }
    }

    public function test_single_rows_and_null_values_are_returned_as_structured_json(): void
    {
        $reports = $this->reports();
        $company = $this->user('company');
        $seeker = $this->user();
        $job = $this->job($company);
        $id = DB::table('applications')->insertGetId(['user_id'=>$seeker->id,'job_listing_id'=>$job->id,'status'=>'pending']);
        $result = $reports->userApplications($seeker->id,null,1);
        $this->assertSame(1,$result['row_count']);
        $this->assertSame($id,$result['next_after_id']);
        $this->assertNull($result['rows'][0]['created_at']);
        $this->assertNull($result['rows'][0]['updated_at']);
        $this->assertSame($company->name,$result['rows'][0]['company_name']);
        $history = $reports->applicationHistory($seeker->id,$id,0,1);
        $this->assertSame(1,$history['row_count']);
        $this->assertNull($history['rows'][0]['old_status']);
        $this->assertSame('created',$history['rows'][0]['event_type']);
        $this->assertNotEmpty($history['rows'][0]['changed_at']);
    }

    public function test_ownerless_legacy_jobs_have_null_company_fields_and_no_company_access(): void
    {
        $reports = $this->reports();
        $company = $this->user('company');
        $seeker = $this->user();
        $job = $this->job($company);
        $job->user_id = null;
        $job->save();
        $application = $seeker->applications()->create(['job_listing_id'=>$job->id,'status'=>'pending']);
        $result = $reports->userApplications($seeker->id);
        $this->assertSame(1,$result['row_count']);
        $this->assertNull($result['rows'][0]['company_id']);
        $this->assertNull($result['rows'][0]['company_name']);
        $this->assertSame(0,$reports->companyJobs($company->id)['row_count']);
        $this->assertSame(1,$reports->applicationHistory($seeker->id,$application->id)['row_count']);
        $this->rejected(403,fn () => $reports->applicationHistory($company->id,$application->id));
    }

    public function test_role_and_record_validation_rejects_invalid_requests(): void
    {
        $reports = $this->reports();
        $company = $this->user('company');
        $seeker = $this->user();
        $this->rejected(403,fn () => $reports->companyJobs($seeker->id));
        $this->rejected(403,fn () => $reports->userApplications($company->id));
        $this->rejected(404,fn () => $reports->companyJobs(999999));
        $this->rejected(404,fn () => $reports->userApplications(999999));
        $this->rejected(404,fn () => $reports->applicationHistory(999999,999999));
        $this->rejected(404,fn () => $reports->applicationHistory($seeker->id,999999));
        $this->assertSame(0,$reports->companyJobs($company->id)['row_count']);
    }

    #[DataProvider('invalidPages')]
    public function test_invalid_pagination_is_rejected_and_the_connection_can_be_reused(int $after, int $limit): void
    {
        $reports = $this->reports();
        extract($this->fixture());
        $this->rejected(422,fn () => $reports->companyJobs($company->id,$after,$limit));
        $this->rejected(422,fn () => $reports->userApplications($seeker->id,$after,$limit));
        $this->rejected(422,fn () => $reports->applicationHistory($seeker->id,$first->id,$after,$limit));
        $this->assertSame(3,$reports->companyJobs($company->id)['row_count']);
    }

    public static function invalidPages(): array
    {
        return ['negative after' => [-1,10], 'zero limit' => [0,0], 'negative limit' => [0,-1], 'too many rows' => [0,201]];
    }

    public function test_maximum_page_size_is_bounded_and_large_json_is_not_truncated(): void
    {
        $reports = $this->reports();
        $company = $this->user('company');
        $rows = [];
        for ($index=0;$index<201;$index++) {
            $rows[] = ['user_id'=>$company->id,'title'=>str_repeat('مرحبا',40),'description'=>'Maximum-page fixture.',
                'location'=>'Beirut','type'=>'Full-Time','currency'=>'USD','status'=>'active'];
        }
        DB::table('job_listings')->insert($rows);
        $first = $reports->companyJobs($company->id,0,200);
        $second = $reports->companyJobs($company->id,$first['next_after_id'],200);
        $this->assertSame(200,$first['row_count']);
        $this->assertSame(1,$second['row_count']);
        $this->assertSame(str_repeat('مرحبا',40),$first['rows'][199]['title']);
        $this->assertSame(0,$reports->companyJobs($company->id,PHP_INT_MAX)['row_count']);
    }

    public function test_cursor_reads_do_not_change_records_or_commit_the_callers_transaction(): void
    {
        $reports = $this->reports();
        extract($this->fixture());
        $snapshot = function () {
            $rows = [];
            foreach (['users','job_listings','profiles','applications','saved_jobs','application_status_history'] as $table) {
                $rows[$table] = DB::table($table)->orderBy('id')->get()->toJson();
            }
            return $rows;
        };
        $before = $snapshot();
        $level = DB::connection()->transactionLevel();
        $reports->companyJobs($company->id);
        $reports->userApplications($seeker->id);
        $reports->applicationHistory($seeker->id,$first->id);
        $this->assertSame($before,$snapshot());
        $this->assertSame($level,DB::connection()->transactionLevel());
        DB::beginTransaction();
        $this->job($company);
        $this->assertSame(4,$reports->companyJobs($company->id)['row_count']);
        DB::rollBack();
        $this->assertSame(3,$reports->companyJobs($company->id)['row_count']);
    }

    public function test_command_returns_a_json_page_and_reports_business_failure(): void
    {
        $this->reports();
        $company = $this->user('company');
        $job = $this->job($company);
        $code = Artisan::call('careerhub:cursor-report',['report'=>'company-jobs','--actor'=>$company->id,'--limit'=>1]);
        $this->assertSame(0,$code);
        $result = json_decode(Artisan::output(),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame(1,$result['row_count']);
        $this->assertSame($job->id,$result['rows'][0]['job_id']);
        $this->assertSame(1,Artisan::call('careerhub:cursor-report',['report'=>'company-jobs','--actor'=>999999]));
    }

    #[DataProvider('invalidCommandOptions')]
    public function test_command_rejects_invalid_options_with_exit_code_two(array $arguments): void
    {
        $this->assertSame(2,Artisan::call('careerhub:cursor-report',$arguments));
    }

    public static function invalidCommandOptions(): array
    {
        return [
            'unknown report' => [['report'=>'unknown','--actor'=>'1']],
            'missing actor' => [['report'=>'company-jobs']],
            'invalid actor' => [['report'=>'company-jobs','--actor'=>'1 OR 1=1']],
            'negative after' => [['report'=>'company-jobs','--actor'=>'1','--after'=>'-1']],
            'zero limit' => [['report'=>'company-jobs','--actor'=>'1','--limit'=>'0']],
            'large limit' => [['report'=>'company-jobs','--actor'=>'1','--limit'=>'201']],
            'missing application' => [['report'=>'application-history','--actor'=>'1']],
            'unused application' => [['report'=>'company-jobs','--actor'=>'1','--application'=>'1']],
        ];
    }
}
