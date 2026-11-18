<?php

use App\Database\CareerHubProcedures;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $definitions = CareerHubProcedures::definitions();

        // Laravel migrate:fresh drops tables but retains stored routines. These
        // seven names are owned by this migration; reinstall only those names.
        $this->down();

        $created = [];
        try {
            foreach ($definitions as $index => $sql) {
                DB::unprepared($sql);
                $created[] = CareerHubProcedures::NAMES[$index];
            }
        } catch (Throwable $error) {
            // Routine DDL implicitly commits. Undo only this installation's DDL.
            foreach (array_reverse($created) as $name) {
                DB::unprepared("DROP PROCEDURE IF EXISTS `$name`");
            }
            throw $error;
        }
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        foreach (array_reverse(CareerHubProcedures::NAMES) as $name) {
            DB::unprepared("DROP PROCEDURE IF EXISTS `$name`");
        }
    }
};
