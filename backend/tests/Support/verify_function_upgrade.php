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

use App\Database\CareerHubFunctions;
use App\Database\CareerHubProcedures;
use App\Database\CareerHubTriggers;
use App\Models\User;
use App\Services\RecruitmentTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
    || ! str_ends_with(DB::connection()->getDatabaseName(), '_testing')) {
    throw new RuntimeException('A disposable MySQL/MariaDB _testing database is required.');
}
$tables = ['users','job_listings','profiles','applications','saved_jobs','application_status_history'];
foreach ($tables as $table) {
    if (DB::table($table)->count() !== 0) {
        throw new RuntimeException('Function upgrade verification requires empty recruitment/history tables.');
    }
}
if (DB::table('migrations')->orderByDesc('id')->value('migration') !== '2026_11_17_000300_create_careerhub_functions') {
    throw new RuntimeException('The test database must be migrated exactly through Step 4.');
}

$company = null;
$seeker = null;
try {
    $company = User::factory()->create(['role' => 'company']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);
    $service = app(RecruitmentTransactions::class);
    $fallback = fn () => throw new LogicException('Target DBMS required.');
    $job = $service->execute('ch_create_job', [$company->id,'Function migration fixture','Existing record preservation.','Beirut','Full-Time',null,null,'USD','active'], $fallback)['job_id'];
    $application = $service->execute('ch_submit_application', [$seeker->id,$job], $fallback)['application_id'];
    $service->execute('ch_update_application_status', [$company->id,$application,'accepted'], $fallback);
    $service->execute('ch_save_job', [$seeker->id,$job], $fallback);
    DB::table('job_listings')->where('id',$job)->update(['status'=>'closed']);

    $snapshot = function () use ($tables) {
        $rows = [];
        foreach ($tables as $table) {
            $rows[$table] = DB::table($table)->orderBy('id')->get()->toJson();
        }
        return $rows;
    };
    $before = $snapshot();
    if (Artisan::call('migrate:rollback',['--step'=>1,'--force'=>true])!==0) {
        throw new RuntimeException(Artisan::output());
    }
    $schema = DB::connection()->getDatabaseName();
    $functionCount = DB::table('information_schema.ROUTINES')->where('ROUTINE_SCHEMA',$schema)->whereIn('ROUTINE_NAME',CareerHubFunctions::NAMES)->count();
    $procedureCount = DB::table('information_schema.ROUTINES')->where('ROUTINE_SCHEMA',$schema)->whereIn('ROUTINE_NAME',CareerHubProcedures::NAMES)->count();
    $triggerCount = DB::table('information_schema.TRIGGERS')->where('TRIGGER_SCHEMA',$schema)->whereIn('TRIGGER_NAME',CareerHubTriggers::NAMES)->count();
    if ($functionCount!==0 || $procedureCount!==7 || $triggerCount!==9 || $snapshot()!==$before) {
        throw new RuntimeException('Function rollback modified data, procedures or triggers.');
    }
    echo "PASS function rollback preserves all recruitment/history rows, seven procedures and nine triggers.\n";
    if (Artisan::call('migrate',['--force'=>true])!==0 || $snapshot()!==$before) {
        throw new RuntimeException('Function installation did not preserve the existing Step 3 records.');
    }
    $result = DB::selectOne('SELECT ch_user_application_count(?) AS user_apps,ch_user_saved_job_count(?) AS bookmarks,ch_company_job_count(?) AS jobs,ch_company_active_job_count(?) AS active_jobs,ch_company_application_count(?) AS company_apps,ch_job_application_count(?) AS job_apps',
        [$seeker->id,$seeker->id,$company->id,$company->id,$company->id,$job]);
    if (array_values(array_map('intval',(array)$result))!==[1,1,1,0,1,1]) {
        throw new RuntimeException('Function installation did not report the existing records correctly.');
    }
    echo "PASS function installation reports existing accepted applications and closed vacancies correctly without changing their history.\n";
    echo 'Server: '.DB::selectOne('SELECT VERSION() AS version')->version."\n";
} finally {
    $company?->delete();
    $seeker?->delete();
}
