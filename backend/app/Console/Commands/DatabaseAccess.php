<?php

namespace App\Console\Commands;

use App\Database\CareerHubAccess;
use Illuminate\Console\Command;

class DatabaseAccess extends Command
{
    protected $signature = 'careerhub:database-access
        {--database=careerhub : Exact existing database name}
        {--host=localhost : localhost or one explicit client IP address}
        {--runtime=careerhub_app : New application database account}
        {--reporter=careerhub_report : New trusted reporting database account}
        {--migrator=careerhub_migrate : New deployment database account}
        {--bootstrap : Create fresh accounts and grant only deployment access before migrations}
        {--grants-only : Reapply grants to existing dedicated accounts without passwords}
        {--output= : New private SQL output file; never overwritten}';

    protected $description = 'Generate private SQL for three CareerHub database accounts without applying it';

    public function handle(): int
    {
        if ($this->option('bootstrap') && $this->option('grants-only')) {
            $this->error('--bootstrap and --grants-only cannot be combined.');

            return self::INVALID;
        }
        $output = $this->option('output');
        if (! is_string($output) || $output === '' || ! is_dir(dirname($output)) || file_exists($output)) {
            $this->error('Choose --output in an existing private directory, using a new filename.');

            return self::INVALID;
        }
        $accounts = [];
        $passwords = $this->option('grants-only') ? null : [];
        foreach (['runtime', 'reporter', 'migrator'] as $role) {
            $accounts[$role] = (string) $this->option($role);
            if ($passwords !== null) {
                $passwords[$role] = $this->secret("New $role database password (20..128 printable characters)", false);
            }
        }
        try {
            $sql = CareerHubAccess::statements((string) $this->option('database'), (string) $this->option('host'), $accounts, $passwords);
            if ($this->option('bootstrap')) {
                $sql = array_merge(array_slice($sql, 0, 4), [end($sql)]);
            }
        } catch (\InvalidArgumentException $error) {
            $this->error($error->getMessage());

            return self::INVALID;
        }
        $file = @fopen($output, 'x');
        if ($file === false) {
            $this->error('Could not create the private output file.');

            return self::FAILURE;
        }
        $body = "-- CareerHub database access, 17 November 2026.\n"
            .($passwords === null ? "-- Grants only: review existing account identities before applying.\n" : "-- PRIVATE: contains passwords. Fresh dedicated accounts only.\n")
            .($this->option('bootstrap') ? "-- Bootstrap before migrations; apply remaining grants after migrations.\n" : "-- Apply after migrations.\n")
            ."-- Use an administrator and a batch client that stops on errors.\n"
            ."-- Account/grant DDL is not transactional. Grants are additive.\n"
            .implode(";\n", $sql).";\n";
        try {
            @chmod($output, 0600);
            $written = fwrite($file, $body);
        } finally {
            fclose($file);
        }
        if ($written !== strlen($body)) {
            @unlink($output);
            $this->error('Writing the private SQL file failed.');

            return self::FAILURE;
        }
        $this->info('Private SQL generated. No database accounts or grants were changed.');
        $this->line('Review and apply with an administrator. Remove generated credential files after use.');

        return self::SUCCESS;
    }
}
