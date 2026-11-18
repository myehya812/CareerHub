<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // These are the values already accepted by CareerHub's API, not new workflows.
    private function checks(): array
    {
        return [
            ['users', 'chk_users_role', "BINARY role IN ('job_seeker', 'company')"],
            ['job_listings', 'chk_job_type', "BINARY type IN ('Full-Time', 'Part-Time', 'Internship', 'Contract')"],
            ['job_listings', 'chk_job_status', "BINARY status IN ('active', 'draft', 'closed')"],
            ['job_listings', 'chk_job_salary', '(salary_min IS NULL OR salary_min >= 0) AND (salary_max IS NULL OR salary_max >= 0) AND (salary_min IS NULL OR salary_max IS NULL OR salary_min <= salary_max)'],
            ['applications', 'chk_application_status', "BINARY status IN ('pending', 'accepted', 'rejected')"],
        ];
    }

    public function up(): void
    {
        // The application targets MySQL/MariaDB. SQLite remains available for
        // fast API/relationship tests; CHECK enforcement is tested on the target DBMS.
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Check all existing rows before any ALTER; never silently rewrite user data.
        foreach ($this->checks() as [$table, $name, $expression]) {
            if (DB::table($table)->whereRaw("NOT ($expression)")->exists()) {
                throw new RuntimeException("Existing rows violate $name. Correct them before migrating.");
            }
        }

        foreach ($this->checks() as [$table, $name, $expression]) {
            DB::statement("ALTER TABLE `$table` ADD CONSTRAINT `$name` CHECK ($expression)");
        }
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // MySQL and MariaDB use different syntax to remove a CHECK constraint.
        $mariaDb = str_contains(strtolower(DB::selectOne('SELECT VERSION() AS version')->version), 'mariadb');
        $drop = $mariaDb ? 'DROP CONSTRAINT' : 'DROP CHECK';

        foreach (array_reverse($this->checks()) as [$table, $name]) {
            DB::statement("ALTER TABLE `$table` $drop `$name`");
        }
    }
};
