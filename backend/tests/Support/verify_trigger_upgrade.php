<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Laravel's rendering handler may finish a standalone script without a failure
// exit code. Make unexpected verifier/worker failures unambiguous to automation.
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, get_class($error).': '.$error->getMessage().PHP_EOL);
    exit(1);
});

use App\Database\CareerHubTriggers;
use App\Models\User;
use App\Services\RecruitmentTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
    || ! str_ends_with(DB::connection()->getDatabaseName(), '_testing')) {
    throw new RuntimeException('A disposable MySQL/MariaDB _testing database is required.');
}
foreach (['users','job_listings','profiles','applications','saved_jobs'] as $table) {
    if (DB::table($table)->count() !== 0) {
        throw new RuntimeException('Upgrade verification requires empty recruitment tables.');
    }
}
$expected = '2026_11_17_000200_create_application_history_and_triggers';
if (DB::table('migrations')->orderByDesc('id')->value('migration') !== $expected) {
    throw new RuntimeException('The test database must be migrated exactly through Step 3.');
}
if (Artisan::call('migrate:rollback', ['--step'=>1, '--force'=>true]) !== 0 || Schema::hasTable('application_status_history')) {
    throw new RuntimeException('Could not prepare the disposable Step 2 baseline.');
}
$company = User::factory()->create(['role' => 'company']);
$seeker = User::factory()->create(['role' => 'job_seeker']);
$service = app(RecruitmentTransactions::class);
$fallback = fn () => throw new LogicException('Target DBMS required.');
$job = $service->execute('ch_create_job', [$company->id,'Existing Step 2 vacancy','Existing valid application.','Beirut','Full-Time',null,null,'USD','active'], $fallback)['job_id'];
$application = $service->execute('ch_submit_application', [$seeker->id,$job], $fallback)['application_id'];
$service->execute('ch_update_application_status', [$company->id,$application,'accepted'], $fallback);
DB::table('job_listings')->where('id', $job)->update(['status' => 'closed']);

// Step 2 did not enforce cross-table user roles for direct SQL. This deliberately
// invalid fixture verifies that the Step 3 preflight stops before changing DDL.
$invalidJob = DB::table('job_listings')->insertGetId([
    'user_id'=>$seeker->id, 'title'=>'Invalid role fixture', 'description'=>'Test only',
    'location'=>'Beirut', 'type'=>'Full-Time', 'status'=>'active', 'currency'=>'USD',
]);
$migration = require database_path('migrations/2026_11_17_000200_create_application_history_and_triggers.php');
$blocked = false;
try {
    $migration->up();
} catch (RuntimeException $error) {
    if (!str_contains($error->getMessage(), 'wrong account role')) {
        throw $error;
    }
    $blocked = true;
}
if (!$blocked || Schema::hasTable('application_status_history') || DB::table('job_listings')->where('id',$invalidJob)->value('user_id') !== $seeker->id) {
    throw new RuntimeException('Preflight failed to preserve the existing database.');
}
echo "PASS invalid legacy role references block installation before history DDL; core records unchanged.\n";
DB::table('job_listings')->where('id',$invalidJob)->delete();

$snapshot = fn () => json_encode([
    DB::table('users')->orderBy('id')->get(), DB::table('job_listings')->orderBy('id')->get(),
    DB::table('applications')->orderBy('id')->get(), DB::table('saved_jobs')->orderBy('id')->get(),
]);
$before = $snapshot();
if (Artisan::call('migrate', ['--force'=>true]) !== 0) {
    throw new RuntimeException(Artisan::output());
}
$history = DB::table('application_status_history')->where('application_id',$application)->get();
if ($snapshot() !== $before || count($history)!==1 || $history[0]->event_type!=='baseline'
    || $history[0]->old_status!==null || $history[0]->new_status!=='accepted') {
    throw new RuntimeException('Existing application backfill was incorrect or modified core data.');
}
echo "PASS existing accepted application on a closed job is preserved and receives one truthful baseline event.\n";
$service->execute('ch_update_application_status', [$company->id,$application,'rejected'], $fallback);
if (DB::table('application_status_history')->where('application_id',$application)->count()!==2) {
    throw new RuntimeException('Upgraded application did not acquire its next history event.');
}
$before = $snapshot();
if (Artisan::call('migrate:rollback',['--step'=>1,'--force'=>true])!==0 || Schema::hasTable('application_status_history') || $snapshot()!==$before) {
    throw new RuntimeException('Step 3 rollback did not preserve the core records.');
}
$remaining = DB::table('information_schema.TRIGGERS')->where('TRIGGER_SCHEMA',DB::connection()->getDatabaseName())
    ->whereIn('TRIGGER_NAME',CareerHubTriggers::NAMES)->count();
if ($remaining!==0) {
    throw new RuntimeException('Step 3 rollback left project triggers installed.');
}
if (Artisan::call('migrate',['--force'=>true])!==0 || $snapshot()!==$before) {
    throw new RuntimeException('Step 3 reinstall modified core records.');
}
$baseline = DB::table('application_status_history')->where('application_id',$application)->first();
if ($baseline->event_type!=='baseline' || $baseline->new_status!=='rejected') {
    throw new RuntimeException('Reinstall did not record the current known status.');
}
echo "PASS trigger/history rollback and reinstall preserve core records; reinstall starts a new baseline.\n";
echo 'Server: '.DB::selectOne('SELECT VERSION() AS version')->version."\n";

$company->delete();
$seeker->delete();
