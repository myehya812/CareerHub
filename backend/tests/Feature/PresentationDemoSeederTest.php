<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\User;
use App\Services\RecruitmentCursorReports;
use App\Services\RecruitmentStatistics;
use App\Services\RecruitmentTransactions;
use Database\Seeders\PresentationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class PresentationDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        if ($database !== ':memory:' && ! str_ends_with($database, '_testing')) {
            throw new RuntimeException('Use an isolated _testing database.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('The presentation dataset exercises native SQL procedures and triggers.');
        }
    }

    public function test_fresh_dataset_has_working_accounts_statistics_and_cursor_reports(): void
    {
        $this->seed(PresentationDemoSeeder::class);

        foreach (['users' => 11, 'profiles' => 11, 'job_listings' => 31,
            'applications' => 25, 'saved_jobs' => 15, 'application_status_history' => 44] as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertSame(21, DB::table('job_listings')->where('status', 'active')->count());
        $this->assertSame(5, DB::table('job_listings')->where('status', 'draft')->count());
        $this->assertSame(5, DB::table('job_listings')->where('status', 'closed')->count());
        $this->getJson('/api/jobs')->assertOk()->assertJsonPath('meta.total', 21);

        $stats = app(RecruitmentStatistics::class);
        $reports = app(RecruitmentCursorReports::class);
        for ($i = 1; $i <= 5; $i++) {
            $company = User::where('email', "company$i@careerhub.test")->firstOrFail();
            $seeker = User::where('email', "user$i@careerhub.test")->firstOrFail();
            foreach ([$company, $seeker] as $account) {
                $this->assertTrue(Hash::check(PresentationDemoSeeder::PASSWORD, $account->password));
                $this->assertNotSame(PresentationDemoSeeder::PASSWORD, $account->getRawOriginal('password'));
                Auth::forgetGuards();
                $this->withHeaders(['Origin' => 'http://localhost:3000', 'Referer' => 'http://localhost:3000/'])
                    ->postJson('/api/login', ['email' => $account->email, 'password' => PresentationDemoSeeder::PASSWORD])
                    ->assertOk()->assertJsonPath('user.role', $account->role);
                $this->postJson('/api/logout')->assertOk();
            }
            $this->assertSame(['total_jobs' => 5, 'total_applications' => 5], $stats->forCompany($company));
            $this->assertSame(['total_applications' => 5, 'saved_jobs' => 3], $stats->forJobSeeker($seeker));
            $this->assertSame(5, $reports->companyJobs($company->id)['row_count']);
            $this->assertSame(5, $reports->userApplications($seeker->id)['row_count']);
            foreach ($seeker->applications as $application) {
                $events = $reports->applicationHistory($seeker->id, $application->id)['rows'];
                $this->assertCount($application->status === 'pending' ? 1 : 2, $events);
                $this->assertSame($application->status, end($events)['new_status']);
            }
        }
    }

    public function test_repeating_the_loader_preserves_existing_activity_and_passwords(): void
    {
        $this->seed(PresentationDemoSeeder::class);
        $seeker = User::where('email', 'user1@careerhub.test')->firstOrFail();
        $seeker->update(['password' => 'ChangedDemoPassword123']);
        $seeker->profile->update(['headline' => 'My edited profile']);
        $application = Application::where('status', 'pending')->firstOrFail();
        app(RecruitmentTransactions::class)->execute('ch_update_application_status', [
            $application->jobListing->user_id, $application->id, 'accepted',
        ], fn () => throw new RuntimeException('Native database required.'));
        $snapshot = fn () => collect(['users', 'profiles', 'job_listings', 'applications', 'saved_jobs', 'application_status_history'])
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()])->all();
        $before = $snapshot();

        $this->seed(PresentationDemoSeeder::class);

        $this->assertSame($before, $snapshot());
        $this->assertTrue(Hash::check('ChangedDemoPassword123', $seeker->fresh()->password));
    }

    public function test_existing_starter_job_owner_and_credentials_are_preserved(): void
    {
        $owner = User::factory()->create(['role' => 'company', 'password' => 'ExistingPassword123']);
        $hash = $owner->getRawOriginal('password');
        $fixtures = json_decode(file_get_contents(database_path('seeders/data/presentation.json')), true, 512, JSON_THROW_ON_ERROR);
        $data = $fixtures['starter_jobs'][0];
        $result = app(RecruitmentTransactions::class)->execute('ch_create_job', [
            $owner->id, $data['title'], $data['description'], $data['location'], $data['type'],
            $data['salary_min'], $data['salary_max'], 'USD', 'active',
        ], fn () => throw new RuntimeException('Native database required.'));

        $this->seed(PresentationDemoSeeder::class);

        $this->assertDatabaseCount('users', 11);
        $this->assertDatabaseCount('job_listings', 31);
        $this->assertDatabaseMissing('users', ['email' => 'demo-company@careerhub.test']);
        $this->assertSame($hash, $owner->fresh()->getRawOriginal('password'));
        $this->assertDatabaseHas('job_listings', ['id' => $result['job_id'], 'user_id' => $owner->id]);
        $this->assertSame(6, $owner->jobListings()->count());
    }

    public function test_production_environment_is_refused_before_any_data_is_inserted(): void
    {
        $this->assertRefusedIn('production');
    }

    public function test_local_environment_with_an_unapproved_database_name_is_refused(): void
    {
        $this->assertRefusedIn('local');
    }

    private function assertRefusedIn(string $environment): void
    {
        $previous = $this->app['env'];
        try {
            $this->app['env'] = $environment;
            try {
                (new PresentationDemoSeeder)->run();
                $this->fail('Expected the presentation database guard to reject this environment.');
            } catch (RuntimeException $error) {
                $this->assertStringContainsString('Presentation data requires', $error->getMessage());
            }
        } finally {
            $this->app['env'] = $previous;
        }
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('job_listings', 0);
    }
}
