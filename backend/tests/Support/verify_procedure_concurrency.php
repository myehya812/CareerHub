<?php

// Run only against a disposable, migrated MySQL/MariaDB database ending _testing.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
// Laravel's rendering handler may finish a standalone script without a failure
// exit code. Make unexpected verifier/worker failures unambiguous to automation.
set_exception_handler(function (Throwable $error): void {
    fwrite(STDERR, get_class($error).': '.$error->getMessage().PHP_EOL);
    exit(1);
});

use App\Models\User;
use App\Services\RecruitmentTransactions;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
    || ! str_ends_with(DB::connection()->getDatabaseName(), '_testing')) {
    throw new RuntimeException('Concurrency verification requires an isolated MySQL/MariaDB _testing database.');
}

$service = app(RecruitmentTransactions::class);
$fallback = fn () => throw new LogicException('MySQL/MariaDB required.');

if (($argv[1] ?? '') === 'worker') {
    [, , $mode, $userId, $jobId, $directory, $worker] = $argv;
    touch($directory.'/ready-'.$worker);
    $deadline = microtime(true) + 15;
    while (! file_exists($directory.'/go')) {
        if (microtime(true) > $deadline) {
            throw new RuntimeException('Concurrency barrier timed out.');
        }
        usleep(10000);
    }
    try {
        $result = $service->execute($mode === 'apply' ? 'ch_submit_application' : 'ch_save_job', [(int) $userId, (int) $jobId], $fallback);
        echo json_encode(['status' => 200, 'result' => $result]);
    } catch (HttpException $error) {
        echo json_encode(['status' => $error->getStatusCode()]);
    }
    exit;
}

function race(string $mode, int $userId, int $jobId): array
{
    $directory = storage_path('framework/testing/ch-race-'.bin2hex(random_bytes(6)));
    if (! mkdir($directory, 0700, true)) {
        throw new RuntimeException('Could not create concurrency barrier.');
    }
    $children = [];
    try {
        foreach ([0, 1] as $worker) {
            $pipes = [];
            $process = proc_open([PHP_BINARY, __FILE__, 'worker', $mode, (string) $userId, (string) $jobId, $directory, (string) $worker],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
            if (! is_resource($process)) {
                throw new RuntimeException('Could not start concurrency worker.');
            }
            fclose($pipes[0]);
            $children[] = [$process, $pipes];
        }
        $deadline = microtime(true) + 15;
        while (! file_exists($directory.'/ready-0') || ! file_exists($directory.'/ready-1')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Workers did not reach the barrier.');
            }
            usleep(10000);
        }
        touch($directory.'/go');
        $results = [];
        foreach ($children as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);
            if ($code !== 0) {
                throw new RuntimeException('Concurrency worker failed: '.$errors);
            }
            $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        }
        $children = [];

        return $results;
    } finally {
        foreach ($children as [$process, $pipes]) {
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }
        foreach (['ready-0', 'ready-1', 'go'] as $name) {
            if (file_exists($directory.'/'.$name)) {
                unlink($directory.'/'.$name);
            }
        }
        rmdir($directory);
    }
}

$company = null;
$seeker = null;
try {
    $suffix = bin2hex(random_bytes(6));
    $company = User::factory()->create(['email' => "company-$suffix@example.test", 'role' => 'company']);
    $seeker = User::factory()->create(['email' => "seeker-$suffix@example.test", 'role' => 'job_seeker']);
    $job = (int) $service->execute('ch_create_job', [$company->id, 'Concurrency verification', 'Disposable test fixture.', 'Beirut', 'Full-Time', null, null, 'USD', 'active'], $fallback)['job_id'];
    $applications = race('apply', $seeker->id, $job);
    $statuses = array_column($applications, 'status');
    sort($statuses);
    if ($statuses !== [200, 409] || DB::table('applications')->where('job_listing_id', $job)->count() !== 1) {
        throw new RuntimeException('Concurrent applications were not serialized correctly.');
    }
    echo "PASS simultaneous applications: one committed application and one HTTP 409 conflict.\n";

    $bookmarks = race('save', $seeker->id, $job);
    $created = array_map(fn ($row) => (int) ($row['result']['created'] ?? -1), $bookmarks);
    sort($created);
    if (array_column($bookmarks, 'status') !== [200, 200] || $created !== [0, 1]
        || (int) $bookmarks[0]['result']['saved_job_id'] !== (int) $bookmarks[1]['result']['saved_job_id']
        || DB::table('saved_jobs')->where('job_listing_id', $job)->count() !== 1) {
        throw new RuntimeException('Concurrent bookmarks were not idempotent.');
    }
    echo "PASS simultaneous saves: one committed bookmark, one existing bookmark, same ID.\n";
    if (DB::getSchemaBuilder()->hasTable('application_status_history')) {
        $applicationId = DB::table('applications')->where('job_listing_id', $job)->value('id');
        if (DB::table('application_status_history')->where('application_id', $applicationId)->count() !== 1) {
            throw new RuntimeException('Concurrent duplicate submission wrote duplicate history.');
        }
        echo "PASS concurrent duplicate submissions create exactly one history event.\n";
    }
    if (DB::table('information_schema.ROUTINES')->where('ROUTINE_SCHEMA',DB::connection()->getDatabaseName())
        ->where('ROUTINE_NAME','ch_company_job_count')->exists()) {
        $stats = DB::selectOne('SELECT ch_user_application_count(?) AS user_apps,ch_user_saved_job_count(?) AS bookmarks,ch_company_job_count(?) AS jobs,ch_company_active_job_count(?) AS active_jobs,ch_company_application_count(?) AS company_apps,ch_job_application_count(?) AS job_apps',
            [$seeker->id,$seeker->id,$company->id,$company->id,$company->id,$job]);
        if (array_values(array_map('intval',(array)$stats))!==[1,1,1,1,1,1]) {
            throw new RuntimeException('Reporting functions disagree with the committed concurrent outcomes.');
        }
        echo "PASS all six reporting functions reflect the committed concurrent outcomes.\n";
    }
    echo 'Server: '.DB::selectOne('SELECT VERSION() AS version')->version."\n";
} finally {
    $company?->delete();
    $seeker?->delete();
}
