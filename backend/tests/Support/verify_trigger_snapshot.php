<?php

// Checks a role change against data committed AFTER a REPEATABLE READ snapshot.
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

if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
    || ! str_ends_with(DB::connection()->getDatabaseName(), '_testing')) {
    throw new RuntimeException('An isolated MySQL/MariaDB _testing database is required.');
}
$config = DB::connection()->getConfig();
$reader = new PDO("mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
    $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$company = null;
$seeker = null;
try {
    $company = User::factory()->create(['role' => 'company']);
    $seeker = User::factory()->create(['role' => 'job_seeker']);
    $service = app(RecruitmentTransactions::class);
    $fallback = fn () => throw new LogicException('MySQL/MariaDB required.');
    $job = $service->execute('ch_create_job', [$company->id, 'Trigger isolation fixture', 'Disposable test fixture.', 'Beirut', 'Full-Time', null, null, 'USD', 'active'], $fallback)['job_id'];
    $reader->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $reader->beginTransaction();
    $statement = $reader->prepare('SELECT COUNT(*) FROM applications WHERE user_id=?');
    $statement->execute([$seeker->id]);
    if ((int) $statement->fetchColumn() !== 0) {
        throw new RuntimeException('Expected a new seeker with no applications.');
    }
    $statement->closeCursor();

    // Commit an application on another connection after the old snapshot exists.
    $service->execute('ch_submit_application', [$seeker->id, $job], $fallback);
    $statement->execute([$seeker->id]);
    if ((int) $statement->fetchColumn() !== 0) {
        throw new RuntimeException('The test connection did not retain the old snapshot.');
    }
    $statement->closeCursor();

    try {
        $update = $reader->prepare("UPDATE users SET role='company' WHERE id=?");
        $update->execute([$seeker->id]);
        throw new RuntimeException('Role change ignored a newly committed application.');
    } catch (PDOException $error) {
        if ((int) $error->errorInfo[1] !== 30003) {
            throw $error;
        }
    }
    $reader->rollBack();
    if ($seeker->fresh()->role !== 'job_seeker') {
        throw new RuntimeException('The rejected role change modified the user.');
    }
    echo "PASS trigger locking read detects committed applications hidden by the caller's older snapshot.\n";
    echo 'Server: '.DB::selectOne('SELECT VERSION() AS version')->version."\n";
} finally {
    if ($reader->inTransaction()) {
        $reader->rollBack();
    }
    $company?->delete();
    $seeker?->delete();
}
