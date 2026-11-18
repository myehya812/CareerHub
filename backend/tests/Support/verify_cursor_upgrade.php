<?php

require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR,get_class($error).': '.$error->getMessage().PHP_EOL);
    exit(1);
});

use App\Database\CareerHubCursors;
use App\Database\CareerHubFunctions;
use App\Database\CareerHubProcedures;
use App\Database\CareerHubTriggers;
use App\Models\User;
use App\Services\RecruitmentCursorReports;
use App\Services\RecruitmentTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

if (!in_array(DB::getDriverName(),['mysql','mariadb'],true)
    || !str_ends_with(DB::connection()->getDatabaseName(),'_testing')) {
    throw new RuntimeException('A disposable MySQL/MariaDB _testing database is required.');
}
$tables=['users','job_listings','profiles','applications','saved_jobs','application_status_history'];
foreach ($tables as $table) {
    if (DB::table($table)->count()!==0) {
        throw new RuntimeException('Cursor upgrade verification requires empty recruitment/history tables.');
    }
}
if (DB::table('migrations')->orderByDesc('id')->value('migration')!=='2026_11_17_000400_create_careerhub_cursors') {
    throw new RuntimeException('The test database must be migrated exactly through Step 5.');
}
$company=null;
$seeker=null;
try {
    $company=User::factory()->create(['role'=>'company']);
    $seeker=User::factory()->create(['role'=>'job_seeker']);
    $transactions=app(RecruitmentTransactions::class);
    $fallback=fn()=>throw new LogicException('Target DBMS required.');
    $job=$transactions->execute('ch_create_job',[$company->id,'Existing Step 4 vacancy','Record preservation fixture.','Beirut','Full-Time',null,null,'USD','active'],$fallback)['job_id'];
    $application=$transactions->execute('ch_submit_application',[$seeker->id,$job],$fallback)['application_id'];
    $transactions->execute('ch_update_application_status',[$company->id,$application,'accepted'],$fallback);
    $transactions->execute('ch_save_job',[$seeker->id,$job],$fallback);
    DB::table('job_listings')->where('id',$job)->update(['status'=>'closed']);
    $snapshot=function () use ($tables) {
        $data=[];
        foreach ($tables as $table) {
            $data[$table]=DB::table($table)->orderBy('id')->get()->toJson();
        }
        return $data;
    };
    $before=$snapshot();
    if (Artisan::call('migrate:rollback',['--step'=>1,'--force'=>true])!==0) {
        throw new RuntimeException(Artisan::output());
    }
    $schema=DB::connection()->getDatabaseName();
    $routineCount=fn(array $names)=>DB::table('information_schema.ROUTINES')->where('ROUTINE_SCHEMA',$schema)->whereIn('ROUTINE_NAME',$names)->count();
    $triggers=DB::table('information_schema.TRIGGERS')->where('TRIGGER_SCHEMA',$schema)->whereIn('TRIGGER_NAME',CareerHubTriggers::NAMES)->count();
    if ($routineCount(CareerHubCursors::NAMES)!==0 || $routineCount(CareerHubProcedures::NAMES)!==7
        || $routineCount(CareerHubFunctions::NAMES)!==6 || $triggers!==9 || $snapshot()!==$before) {
        throw new RuntimeException('Cursor rollback changed prior routines, triggers or table data.');
    }
    echo "PASS cursor rollback removes only three report procedures and preserves all previous data and database objects.\n";
    if (Artisan::call('migrate',['--force'=>true])!==0 || $snapshot()!==$before || $routineCount(CareerHubCursors::NAMES)!==3) {
        throw new RuntimeException('Cursor installation changed existing records or failed to restore the reports.');
    }
    $reports=app(RecruitmentCursorReports::class);
    $jobs=$reports->companyJobs($company->id);
    $applications=$reports->userApplications($seeker->id);
    $history=$reports->applicationHistory($company->id,(int)$application);
    if ($jobs['row_count']!==1 || $jobs['rows'][0]['status']!=='closed' || $jobs['rows'][0]['accepted']!==1
        || $applications['row_count']!==1 || $applications['rows'][0]['history_events']!==2
        || $history['row_count']!==2 || $snapshot()!==$before) {
        throw new RuntimeException('New cursor reports do not reflect the existing Step 4 records correctly.');
    }
    echo "PASS cursor installation reports existing closed jobs, accepted applications and their history without modifying records.\n";
    echo 'Server: '.DB::selectOne('SELECT VERSION() AS version')->version."\n";
} finally {
    $company?->delete();
    $seeker?->delete();
}
