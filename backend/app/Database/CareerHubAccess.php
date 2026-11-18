<?php

namespace App\Database;

class CareerHubAccess
{
    public const RUNTIME_TABLES = ['users', 'profiles', 'job_listings', 'applications', 'saved_jobs',
        'personal_access_tokens', 'password_reset_tokens', 'sessions', 'cache', 'cache_locks',
        'jobs', 'job_batches', 'failed_jobs'];

    /** Generate admin-reviewed DCL; this class never connects or applies grants. */
    public static function statements(string $database, string $host, array $accounts, ?array $passwords = null): array
    {
        if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,63}$/D', $database)) {
            throw new \InvalidArgumentException('Use a database name of 1..64 letters, digits or underscores, starting with a letter.');
        }
        if ($host !== 'localhost' && filter_var($host, FILTER_VALIDATE_IP) === false) {
            throw new \InvalidArgumentException('Use localhost or one explicit IP address for the account host.');
        }
        if (array_keys($accounts) !== ['runtime', 'reporter', 'migrator'] || ($passwords !== null && array_keys($passwords) !== array_keys($accounts))) {
            throw new \InvalidArgumentException('Provide runtime, reporter and migrator accounts and passwords in that order.');
        }
        foreach ($accounts as $name) {
            if (! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,31}$/D', $name) || strtolower($name) === 'root') {
                throw new \InvalidArgumentException('Use dedicated account names of 1..32 letters, digits or underscores; root is reserved.');
            }
        }
        if (count(array_unique(array_map('strtolower', $accounts))) !== 3) {
            throw new \InvalidArgumentException('The three account names must differ.');
        }
        foreach ($passwords ?? [] as $password) {
            if (! is_string($password) || ! preg_match('/^[\x20-\x7e]{20,128}$/D', $password)) {
                throw new \InvalidArgumentException('Use distinct passwords of 20..128 printable ASCII characters.');
            }
        }
        if ($passwords !== null && count(array_unique($passwords)) !== 3) {
            throw new \InvalidArgumentException('The three passwords must differ.');
        }
        $literal = fn (string $value) => "'".str_replace("'", "''", $value)."'";
        $users = array_map(fn ($name) => $literal($name).'@'.$literal($host), $accounts);
        $schema = '`'.$database.'`';
        // At database level, GRANT treats underscores as wildcards even inside backticks.
        $grantSchema = '`'.str_replace('_', '\\_', $database).'`';
        $sql = [];
        if ($passwords !== null) {
            $sql = ['SET @ch_access_old_sql_mode=@@SESSION.sql_mode', "SET SESSION sql_mode='NO_BACKSLASH_ESCAPES'"];
            $create = [];
            foreach ($users as $role => $user) {
                $create[] = $user.' IDENTIFIED BY '.$literal($passwords[$role]);
            }
            // No IF NOT EXISTS: never silently add grants to an existing account.
            $sql[] = 'CREATE USER '.implode(', ', $create);
        }
        $sql[] = 'GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, REFERENCES, CREATE ROUTINE, ALTER ROUTINE, EXECUTE, TRIGGER ON '
            .$grantSchema.'.* TO '.$users['migrator'];
        foreach (self::RUNTIME_TABLES as $table) {
            $sql[] = "GRANT SELECT, INSERT, UPDATE, DELETE ON $schema.`$table` TO ".$users['runtime'];
        }
        $sql[] = "GRANT SELECT ON $schema.`application_status_history` TO ".$users['runtime'];
        $sql[] = "GRANT SELECT (id, name, role) ON $schema.`users` TO ".$users['reporter'];
        $sql[] = "GRANT SELECT (user_id, company_name) ON $schema.`profiles` TO ".$users['reporter'];
        foreach (['job_listings', 'applications', 'saved_jobs', 'application_status_history'] as $table) {
            $sql[] = "GRANT SELECT ON $schema.`$table` TO ".$users['reporter'];
        }
        foreach (['PROCEDURE' => array_merge(CareerHubProcedures::NAMES, CareerHubCursors::NAMES),
            'FUNCTION' => CareerHubFunctions::NAMES] as $type => $names) {
            foreach ($names as $name) {
                $sql[] = "GRANT EXECUTE ON $type $schema.`$name` TO ".$users['runtime'];
                if ($type === 'FUNCTION' || in_array($name, CareerHubCursors::NAMES, true)) {
                    $sql[] = "GRANT EXECUTE ON $type $schema.`$name` TO ".$users['reporter'];
                }
            }
        }
        if ($passwords !== null) { $sql[] = 'SET SESSION sql_mode=@ch_access_old_sql_mode'; }

        return $sql;
    }
}
