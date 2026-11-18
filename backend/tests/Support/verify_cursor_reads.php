<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR,get_class($error).': '.$error->getMessage().PHP_EOL);
    exit(1);
});

use App\Database\CareerHubCursors;
use App\Models\User;
use App\Services\RecruitmentCursorReports;
use App\Services\RecruitmentTransactions;
use Illuminate\Support\Facades\DB;

if (! in_array(DB::getDriverName(),['mysql','mariadb'],true)
    || ! str_ends_with(DB::connection()->getDatabaseName(),'_testing')) {
    throw new RuntimeException('A disposable MySQL/MariaDB _testing database is required.');
}

function cursorPage(PDO $pdo, string $procedure, array $parameters): array
{
    if (!in_array($procedure,CareerHubCursors::NAMES,true)) {
        throw new InvalidArgumentException('Unknown cursor report.');
    }
    $statement=$pdo->prepare('CALL `'.$procedure.'`('.implode(',',array_fill(0,count($parameters),'?')).')');
    try {
        $statement->execute($parameters);
        $result=$statement->fetch(PDO::FETCH_ASSOC);
        while ($statement->nextRowset()) {
            $statement->fetchAll();
        }
        return ['rows'=>json_decode($result['report_json'],true,512,JSON_THROW_ON_ERROR),
            'row_count'=>(int)$result['row_count']];
    } finally {
        $statement->closeCursor();
    }
}

$config=DB::connection()->getConfig();
$reader=new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
    $config['username'],$config['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$company=null;
$seeker=null;
try {
    $company=User::factory()->create(['role'=>'company']);
    $seeker=User::factory()->create(['role'=>'job_seeker']);
    $transactions=app(RecruitmentTransactions::class);
    $fallback=fn()=>throw new LogicException('Target DBMS required.');
    $create=fn()=>$transactions->execute('ch_create_job',[$company->id,'Cursor snapshot fixture','Disposable test fixture.','Beirut','Full-Time',null,null,'USD','active'],$fallback)['job_id'];
    $firstJob=$create();
    $application=$transactions->execute('ch_submit_application',[$seeker->id,$firstJob],$fallback)['application_id'];
    $transactions->execute('ch_update_application_status',[$company->id,$application,'accepted'],$fallback);
    $reader->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $reader->exec('START TRANSACTION READ ONLY');
    if (cursorPage($reader,'ch_cursor_company_jobs',[$company->id,0,100])['row_count']!==1
        || cursorPage($reader,'ch_cursor_user_applications',[$seeker->id,0,100])['row_count']!==1
        || cursorPage($reader,'ch_cursor_application_history',[$seeker->id,$application,0,100])['row_count']!==2
        || !$reader->inTransaction()) {
        throw new RuntimeException('Cursor reports failed inside a read-only transaction.');
    }
    echo "PASS all three cursor reports execute inside a read-only transaction without ending it.\n";

    $create();
    if (cursorPage($reader,'ch_cursor_company_jobs',[$company->id,0,100])['row_count']!==1
        || app(RecruitmentCursorReports::class)->companyJobs($company->id)['row_count']!==2
        || !$reader->inTransaction()) {
        throw new RuntimeException('The cursor did not preserve the caller transaction snapshot.');
    }
    $reader->rollBack();
    if (cursorPage($reader,'ch_cursor_company_jobs',[$company->id,0,100])['row_count']!==2) {
        throw new RuntimeException('A new snapshot did not observe committed records.');
    }
    echo "PASS existing REPEATABLE READ snapshot remains stable; a new call sees the newly committed vacancy.\n";

    DB::beginTransaction();
    $create();
    if (app(RecruitmentCursorReports::class)->companyJobs($company->id)['row_count']!==3
        || cursorPage($reader,'ch_cursor_company_jobs',[$company->id,0,100])['row_count']!==2) {
        throw new RuntimeException('Uncommitted cursor visibility is incorrect.');
    }
    DB::rollBack();
    if (app(RecruitmentCursorReports::class)->companyJobs($company->id)['row_count']!==2) {
        throw new RuntimeException('Cursor read committed the caller write transaction.');
    }
    echo "PASS cursor reads preserve an outer write transaction and its rollback; other connections cannot see uncommitted vacancies.\n";
    echo 'Server: '.DB::selectOne('SELECT VERSION() AS version')->version."\n";
} finally {
    if ($reader->inTransaction()) {
        $reader->rollBack();
    }
    while (DB::connection()->transactionLevel()>0) {
        DB::rollBack();
    }
    $company?->delete();
    $seeker?->delete();
}
