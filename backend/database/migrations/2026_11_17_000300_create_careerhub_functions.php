<?php

use App\Database\CareerHubFunctions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        $definitions = CareerHubFunctions::definitions();

        // migrate:fresh drops tables but leaves stored functions. Reinstall
        // only these six project-owned names; never change application data.
        $this->down();
        $created = [];
        try {
            foreach ($definitions as $index => $sql) {
                DB::unprepared($sql);
                $created[] = CareerHubFunctions::NAMES[$index];
            }
        } catch (Throwable $error) {
            foreach (array_reverse($created) as $name) {
                DB::unprepared("DROP FUNCTION IF EXISTS `$name`");
            }
            throw $error;
        }
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        foreach (array_reverse(CareerHubFunctions::NAMES) as $name) {
            DB::unprepared("DROP FUNCTION IF EXISTS `$name`");
        }
    }
};
