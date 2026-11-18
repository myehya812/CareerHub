<?php

namespace Tests\Feature;

use App\Database\CareerHubAccess;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DatabaseAccessCommandTest extends TestCase
{
    private function accounts(): array
    {
        return ['runtime' => 'ch_app', 'reporter' => 'ch_report', 'migrator' => 'ch_migrate'];
    }

    private function passwords(): array
    {
        return ['runtime' => "runtime-quoted'\\password-2026", 'reporter' => 'reporter-password-for-2026', 'migrator' => 'migrator-password-for-2026'];
    }

    public function test_grants_separate_runtime_reporting_and_deployment_and_escape_schema_wildcards(): void
    {
        $sql = implode(";\n", CareerHubAccess::statements('careerhub_testing', '127.0.0.1', $this->accounts(), $this->passwords()));
        $this->assertStringContainsString('ON `careerhub\\_testing`.*', $sql);
        $this->assertStringContainsString("runtime-quoted''\\password-2026", $sql);
        $this->assertStringContainsString("GRANT SELECT (id, name, role) ON `careerhub_testing`.`users` TO 'ch_report'", $sql);
        $this->assertStringContainsString("GRANT SELECT ON `careerhub_testing`.`application_status_history` TO 'ch_app'", $sql);
        $this->assertStringNotContainsString('ALL PRIVILEGES', $sql);
        $this->assertStringNotContainsString('GRANT OPTION', $sql);
        $this->assertStringNotContainsString('IF NOT EXISTS', $sql);
        foreach (explode(";\n", $sql) as $statement) {
            if (str_ends_with($statement, "TO 'ch_report'@'127.0.0.1'")) {
                $this->assertMatchesRegularExpression('/^GRANT (SELECT|EXECUTE)/', $statement);
                $this->assertStringNotContainsString('ch_submit_application', $statement);
                $this->assertStringNotContainsString('sessions', $statement);
            }
            if (str_ends_with($statement, "TO 'ch_app'@'127.0.0.1'")) {
                $this->assertStringNotContainsString('CREATE', $statement);
                $this->assertStringNotContainsString('migrations', $statement);
                $this->assertStringNotContainsString('.*', $statement);
            }
        }
    }

    #[DataProvider('invalidInputs')]
    public function test_invalid_scope_or_credentials_are_rejected(string $kind): void
    {
        $database = 'careerhub'; $host = 'localhost';
        $accounts = $this->accounts(); $passwords = $this->passwords();
        match ($kind) {
            'schema injection' => $database = 'careerhub`; GRANT ALL',
            'host wildcard' => $host = '%',
            'reserved root' => $accounts['runtime'] = 'root',
            'duplicate accounts' => $accounts['reporter'] = 'CH_APP',
            'short password' => $passwords['runtime'] = 'short',
            'duplicate passwords' => $passwords['runtime'] = $passwords['reporter'],
            'control characters' => $passwords['runtime'] = "very-long-password\nwith-newline",
        };
        $this->expectException(\InvalidArgumentException::class);
        CareerHubAccess::statements($database, $host, $accounts, $passwords);
    }

    public static function invalidInputs(): array
    {
        return array_map(fn ($kind) => [$kind], ['schema injection', 'host wildcard', 'reserved root', 'duplicate accounts', 'short password', 'duplicate passwords', 'control characters']);
    }

    public function test_command_writes_private_sql_without_connecting_and_never_overwrites_it(): void
    {
        $path = storage_path('app/private/careerhub-access-'.bin2hex(random_bytes(8)).'.sql');
        try {
            $command = $this->artisan('careerhub:database-access', ['--output' => $path]);
            foreach ($this->passwords() as $role => $password) {
                $command->expectsQuestion("New $role database password (20..128 printable characters)", $password);
            }
            $command->expectsOutput('Private SQL generated. No database accounts or grants were changed.')->assertExitCode(0)->run();
            $before = file_get_contents($path);
            $this->assertStringContainsString('CREATE USER', $before);
            $this->artisan('careerhub:database-access', ['--output' => $path])->assertExitCode(2);
            $this->assertSame($before, file_get_contents($path));
        } finally {
            if (file_exists($path)) { unlink($path); }
        }
    }

    public function test_command_requires_a_private_output_destination(): void
    {
        $this->artisan('careerhub:database-access')->assertExitCode(2);
    }

    public function test_grants_only_output_can_refresh_existing_accounts_without_passwords(): void
    {
        $path = storage_path('app/private/careerhub-grants-'.bin2hex(random_bytes(8)).'.sql');
        try {
            $this->artisan('careerhub:database-access', ['--output' => $path, '--grants-only' => true])->assertExitCode(0)->run();
            $body = file_get_contents($path);
            $this->assertStringNotContainsString('CREATE USER', $body);
            $this->assertStringNotContainsString('IDENTIFIED BY', $body);
            $this->assertStringContainsString('GRANT EXECUTE ON PROCEDURE', $body);
        } finally {
            if (file_exists($path)) { unlink($path); }
        }
    }

    public function test_bootstrap_creates_accounts_with_only_the_schema_deployment_grant(): void
    {
        $path = storage_path('app/private/careerhub-bootstrap-'.bin2hex(random_bytes(8)).'.sql');
        try {
            $command = $this->artisan('careerhub:database-access', ['--output' => $path, '--bootstrap' => true]);
            foreach ($this->passwords() as $role => $password) {
                $command->expectsQuestion("New $role database password (20..128 printable characters)", $password);
            }
            $command->assertExitCode(0)->run();
            $body = file_get_contents($path);
            $this->assertStringContainsString('CREATE USER', $body);
            $this->assertSame(1, substr_count($body, 'GRANT '));
            $this->assertStringNotContainsString('ON PROCEDURE', $body);
            $this->artisan('careerhub:database-access', ['--output' => $path, '--bootstrap' => true, '--grants-only' => true])->assertExitCode(2);
        } finally {
            if (file_exists($path)) { unlink($path); }
        }
    }
}
