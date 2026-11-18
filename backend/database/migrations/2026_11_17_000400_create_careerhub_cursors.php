<?php

use App\Database\CareerHubCursors;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        $definitions = CareerHubCursors::definitions();
        $this->down();
        $created = [];
        try {
            foreach ($definitions as $index => $sql) {
                DB::unprepared($sql);
                $created[] = CareerHubCursors::NAMES[$index];
            }
        } catch (Throwable $error) {
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
        foreach (array_reverse(CareerHubCursors::NAMES) as $name) {
            DB::unprepared("DROP PROCEDURE IF EXISTS `$name`");
        }
    }
};
