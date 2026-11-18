<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
set_exception_handler(function (Throwable $error): void {
    // Database errors can contain SQL credentials; never print their query text.
    fwrite(STDERR, 'Database-access verification failed ('.get_class($error).', driver '.($error->errorInfo[1] ?? 'n/a').', '.basename($error->getFile()).':'.$error->getLine().'). Check the isolated test setup.'.PHP_EOL);
    if (get_class($error) === RuntimeException::class) { fwrite(STDERR, $error->getMessage().PHP_EOL); }
    exit(1);
});

use App\Database\CareerHubAccess;
use App\Services\RecruitmentCursorReports;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

$connection = DB::getDefaultConnection();
$original = config("database.connections.$connection");
$database = DB::connection()->getDatabaseName();
if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)
    || !str_ends_with($database, '_testing') || ($original['host'] ?? '') !== '127.0.0.1'
    || !preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $database)) {
    throw new RuntimeException('Use an isolated loopback MySQL/MariaDB _testing database.');
}
$admin = DB::connection()->getPdo();
$check = function (bool $passed, string $message): void {
    if (!$passed) { throw new RuntimeException($message); }
};
$check((int)$admin->query('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->fetchColumn() === 0,
    'Permission verification requires an empty test database.');
$check((int)$admin->query('SELECT COUNT(*) FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA=DATABASE()')->fetchColumn() === 0,
    'Permission verification requires a test database without existing routines.');
$tag = 'ch6_'.bin2hex(random_bytes(6));
$accounts = ['runtime' => $tag.'_app', 'reporter' => $tag.'_report', 'migrator' => $tag.'_migrate'];
// Exercise literal quotes and backslashes under both default SQL modes.
$passwords = array_map(fn ($role) => $role."-'\\-".bin2hex(random_bytes(24)), array_keys($accounts));
$passwords = array_combine(array_keys($accounts), $passwords);
$sql = CareerHubAccess::statements($database, 'localhost', $accounts, $passwords);
$probe = str_replace('_', 'X', $database);
$check(strlen($probe) <= 64 && $probe !== $database, 'Use a test schema name containing underscores.');
$probeCreated = false; $accountsCreated = false; $migrated = false;
$connect = function (string $role) use ($original, $database, $accounts, $passwords): PDO {
    return new PDO('mysql:host=127.0.0.1;port='.$original['port'].';dbname='.$database.';charset=utf8mb4',
        $accounts[$role], $passwords[$role], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
};
$use = function (string $role) use ($connection, $original, $accounts, $passwords): void {
    config(["database.connections.$connection" => array_replace($original, ['username' => $accounts[$role], 'password' => $passwords[$role]])]);
    DB::purge($connection); Auth::forgetGuards();
};
$denied = function (PDO $pdo, string $statement) use ($check): void {
    try { $pdo->exec($statement); }
    catch (PDOException $error) {
        $check(in_array((int)($error->errorInfo[1] ?? 0), [1044, 1142, 1143, 1227, 1370], true), 'Expected a privilege denial, not a SQL error.');
        return;
    }
    throw new RuntimeException('An operation expected to be denied was permitted.');
};
try {
    $exists = $admin->prepare('SELECT COUNT(*) FROM information_schema.SCHEMATA WHERE SCHEMA_NAME=?');
    $exists->execute([$probe]); $check((int)$exists->fetchColumn() === 0, 'Probe schema already exists.');
    $admin->exec("CREATE DATABASE `$probe`"); $probeCreated = true;
    $admin->exec("CREATE TABLE `$probe`.permission_probe (id INT)");
    // First create fresh accounts and give only the deployment account its schema grant.
    foreach (array_slice($sql, 0, 4) as $statement) { $admin->exec($statement); }
    $accountsCreated = true;
    $admin->exec(end($sql));
    $use('migrator');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Migration under deployment account failed.');
    $migrated = true;
    $check(DB::table('migrations')->count() === 17, 'Unexpected migration count.');
    $check(DB::table('information_schema.TRIGGERS')->where('TRIGGER_SCHEMA', $database)->where('DEFINER', $accounts['migrator'].'@localhost')->count() === 9,
        'Triggers must be owned by the deployment account.');
    foreach (CareerHubAccess::statements($database, 'localhost', $accounts) as $statement) { $admin->exec($statement); }
    $runtime = $connect('runtime'); $reporter = $connect('reporter'); $migrator = $connect('migrator');
    foreach (['runtime' => $runtime, 'reporter' => $reporter, 'migrator' => $migrator] as $role => $pdo) {
        $check($pdo->query('SELECT CURRENT_USER()')->fetchColumn() === $accounts[$role].'@localhost', 'Unexpected database identity.');
        $denied($pdo, 'SELECT * FROM mysql.user');
        $denied($pdo, "SELECT * FROM `$probe`.permission_probe");
        $denied($pdo, "CREATE TABLE `$probe`.denied_create (id INT)");
    }
    echo "PASS three separate accounts connect; deployment migration owns nine triggers; schema grants do not match underscore lookalikes.\n";
    $use('runtime');
    config(['app.key' => 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=', 'session.driver' => 'array']);
    $kernel = app(Illuminate\Contracts\Http\Kernel::class);
    $request = function (string $method, string $uri, array $data = [], ?User $actor = null, int $expected = 200) use ($kernel, $check): array {
        Auth::forgetGuards();
        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        // Supply the existing session guard's authenticated user, as Laravel's actingAs does.
        if ($actor !== null) { Auth::guard('web')->setUser($actor); }
        $request = Illuminate\Http\Request::create($uri, $method, [], [], [], $server, json_encode($data, JSON_THROW_ON_ERROR));
        $response = $kernel->handle($request);
        $check($response->getStatusCode() === $expected, "Unexpected HTTP response for $method $uri: ".$response->getStatusCode());
        $result = $response->getContent() === '' ? [] : json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $kernel->terminate($request, $response);
        return $result;
    };
    $registered = [];
    foreach (['company', 'job_seeker'] as $role) {
        $registered[$role] = $request('POST', '/api/register', ['name' => 'Permission '.$role, 'email' => $tag.'-'.$role.'@example.test',
            'password' => 'permission-api-password-2026', 'password_confirmation' => 'permission-api-password-2026', 'role' => $role], null, 201)['id'];
    }
    $company = User::findOrFail($registered['company']); $seeker = User::findOrFail($registered['job_seeker']);
    $check(Auth::guard('web')->attempt(['email' => $company->email, 'password' => 'permission-api-password-2026']), 'Session authentication failed.');
    Auth::guard('web')->logout();
    $companyActor = $company;
    $seekerActor = $seeker;
    $request('PATCH', '/api/profile', ['company_name' => 'Permission Company'], $companyActor);
    $request('PATCH', '/api/profile', ['headline' => 'SQL applicant'], $seekerActor);
    $job = $request('POST', '/api/jobs', ['title' => 'Permission SQL Developer', 'description' => 'Permission test vacancy.',
        'location' => 'Beirut', 'type' => 'Full-Time', 'currency' => 'USD', 'status' => 'active'], $companyActor, 201)['data']['id'];
    $request('POST', "/api/jobs/$job/save", [], $seekerActor, 201);
    $request('POST', "/api/jobs/$job/save", [], $seekerActor);
    $application = $request('POST', "/api/jobs/$job/applications", [], $seekerActor, 201)['application']['id'];
    $request('POST', "/api/jobs/$job/applications", [], $seekerActor, 409);
    $request('PATCH', "/api/applications/$application/status", ['status' => 'accepted'], $companyActor);
    $request('GET', '/api/dashboard/company', [], $companyActor);
    $request('GET', '/api/dashboard/job-seeker', [], $seekerActor);
    $request('GET', '/api/applications', [], $seekerActor);
    $request('POST', '/api/jobs', ['title' => 'Forbidden', 'description' => 'Forbidden vacancy.', 'location' => 'Beirut', 'type' => 'Full-Time', 'currency' => 'USD', 'status' => 'active'], $seekerActor, 403);
    $check(DB::table('application_status_history')->where('application_id', $application)->count() === 2, 'Expected two trigger-generated history entries.');
    $reports = app(RecruitmentCursorReports::class);
    $check($reports->companyJobs($company->id)['row_count'] === 1, 'Runtime cursor failed.');
    $check($reports->userApplications($seeker->id)['row_count'] === 1, 'Runtime application cursor failed.');
    $check($reports->applicationHistory($seeker->id, $application)['row_count'] === 2, 'Runtime history cursor failed.');
    $handler = new Illuminate\Session\DatabaseSessionHandler(DB::connection(), 'sessions', 120, app());
    $sessionId = bin2hex(random_bytes(20)); $handler->write($sessionId, 'permission-session');
    $check($handler->read($sessionId) === 'permission-session', 'Database session failed.'); $handler->destroy($sessionId);
    Cache::store('database')->put($tag, 'permission-cache', 60);
    $check(Cache::store('database')->get($tag) === 'permission-cache', 'Database cache failed.');
    $lock = Cache::store('database')->lock($tag.'-lock', 10); $check($lock->get(), 'Database cache lock failed.'); $lock->release();
    Cache::store('database')->forget($tag);
    echo "PASS runtime account: registration, session authentication, profiles, jobs, bookmarks, applications, dashboards, all cursors, database sessions/cache/locks and automatic history.\n";
    foreach (['INSERT INTO application_status_history (application_id,new_status,event_type) VALUES ('.$application.",'pending','created')",
        'UPDATE application_status_history SET new_status=new_status WHERE 1=0', 'DELETE FROM application_status_history WHERE 1=0',
        'SELECT * FROM migrations', 'CREATE TABLE denied_runtime (id INT)', 'ALTER TABLE job_listings ADD denied_column INT',
        'CREATE USER \'ch6_denied_account\'@\'localhost\' IDENTIFIED BY \'not-created-password-2026\''] as $statement) { $denied($runtime, $statement); }
    foreach (['SELECT password FROM users', 'SELECT email FROM users', 'SELECT remember_token FROM users', 'SELECT resume_path FROM profiles',
        'SELECT * FROM sessions', 'SELECT * FROM password_reset_tokens', 'SELECT * FROM personal_access_tokens', 'SELECT * FROM migrations',
        'CREATE TABLE denied_reporter (id INT)', 'CALL ch_require_transaction()'] as $statement) { $denied($reporter, $statement); }
    foreach (array_merge(CareerHubAccess::RUNTIME_TABLES, ['application_status_history']) as $table) {
        $denied($reporter, "INSERT INTO `$table` () VALUES ()");
        $denied($reporter, "UPDATE `$table` SET ".($table === 'password_reset_tokens' ? 'email=email' : ($table === 'cache' || $table === 'cache_locks' ? '`key`=`key`' : 'id=id')).' WHERE 1=0');
        $denied($reporter, "DELETE FROM `$table` WHERE 1=0");
    }
    $denied($migrator, 'CREATE USER \'ch6_denied_account\'@\'localhost\' IDENTIFIED BY \'not-created-password-2026\'');
    $use('reporter');
    $reports = app(RecruitmentCursorReports::class);
    $check($reports->companyJobs($company->id)['rows'][0]['accepted'] === 1, 'Reporter company cursor failed.');
    $check($reports->userApplications($seeker->id)['row_count'] === 1, 'Reporter application cursor failed.');
    $check($reports->applicationHistory($company->id, $application)['row_count'] === 2, 'Reporter history cursor failed.');
    $counts = DB::selectOne('SELECT ch_user_application_count(?) a,ch_user_saved_job_count(?) s,ch_company_job_count(?) j,ch_company_active_job_count(?) active,ch_company_application_count(?) ca,ch_job_application_count(?) ja',
        [$seeker->id,$seeker->id,$company->id,$company->id,$company->id,$job]);
    $check(array_map('intval', array_values((array)$counts)) === [1,1,1,1,1,1], 'Reporter function totals failed.');
    $admin->exec("REVOKE SELECT ON `$database`.`saved_jobs` FROM '".$accounts['reporter']."'@'localhost'");
    $denied($reporter, 'SELECT * FROM saved_jobs');
    foreach (CareerHubAccess::statements($database, 'localhost', $accounts) as $statement) { $admin->exec($statement); }
    $check((int)$reporter->query('SELECT COUNT(*) FROM saved_jobs')->fetchColumn() === 1, 'Restored reporting grant failed.');
    echo "PASS reporter account: all three cursors and six functions; writes, mutation procedures, credentials, resumes, tokens, sessions and migration ledger denied; REVOKE and re-grant verified.\n";
    echo "PASS runtime cannot edit history, change schema, create accounts or read migration ledger; migrator cannot create accounts or access other schemas.\n";
    // Re-grant routine EXECUTE after recreating routines: MySQL removes specific grants on DROP.
    $use('migrator');
    $check(Artisan::call('migrate:rollback', ['--step' => 1, '--force' => true]) === 0, 'Cursor rollback as deployer failed.');
    $check(Artisan::call('migrate', ['--force' => true]) === 0, 'Cursor reinstall as deployer failed.');
    foreach (CareerHubAccess::statements($database, 'localhost', $accounts) as $statement) { $admin->exec($statement); }
    $use('runtime');
    $check(app(RecruitmentCursorReports::class)->companyJobs($company->id)['row_count'] === 1, 'Runtime routine re-grant failed.');
    $request('DELETE', "/api/jobs/$job/save", [], $seekerActor);
    $request('DELETE', "/api/jobs/$job", [], $companyActor, 204);
    $check(DB::table('application_status_history')->count() === 0, 'History cascade failed without direct runtime DELETE permission.');
    $use('migrator');
    $check(Artisan::call('migrate:reset', ['--force' => true]) === 0, 'Full reset under deployer failed.');
    $migrated = false;
    echo "PASS deployment rollback/reinstall, routine re-grants and runtime cascades; complete migration reset succeeds.\n";
    echo 'Server: '.$admin->query('SELECT VERSION()')->fetchColumn().PHP_EOL;
} finally {
    if ($migrated) {
        $use('migrator');
        Artisan::call('migrate:reset', ['--force' => true]);
    }
    config(["database.connections.$connection" => $original]); DB::purge($connection); Auth::forgetGuards();
    // Remove any table from our initially empty, isolated schema before removing its definers.
    $tables = $admin->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $admin->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach ($tables as $table) { $admin->exec('DROP TABLE `'.str_replace('`', '``', $table).'`'); }
    $admin->exec('SET FOREIGN_KEY_CHECKS=1');
    foreach (array_merge(App\Database\CareerHubProcedures::NAMES, App\Database\CareerHubCursors::NAMES) as $name) {
        $admin->exec("DROP PROCEDURE IF EXISTS `$name`");
    }
    foreach (App\Database\CareerHubFunctions::NAMES as $name) { $admin->exec("DROP FUNCTION IF EXISTS `$name`"); }
    if ($accountsCreated) {
        foreach ($accounts as $name) { $admin->exec("DROP USER '$name'@'localhost'"); }
    }
    if ($probeCreated) { $admin->exec("DROP DATABASE `$probe`"); }
    $admin->exec('SET SESSION sql_mode=@ch_access_old_sql_mode');
}
