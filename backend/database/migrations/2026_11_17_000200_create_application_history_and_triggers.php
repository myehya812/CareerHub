<?php

use App\Database\CareerHubTriggers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $native = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
        $definitions = $native ? CareerHubTriggers::definitions() : [];
        if (Schema::hasTable('application_status_history')) {
            throw new RuntimeException('Application history already exists; review it before migrating.');
        }
        if ($native) {
            if (DB::table('information_schema.TRIGGERS')
                ->where('TRIGGER_SCHEMA', DB::connection()->getDatabaseName())
                ->whereIn('TRIGGER_NAME', CareerHubTriggers::NAMES)->exists()) {
                throw new RuntimeException('CareerHub trigger names already exist; review them before migrating.');
            }
            // Historical closed-job applications are legitimate; only role
            // mismatches must be resolved before installing these invariants.
            foreach ([
                ['job_listings', 'company'], ['applications', 'job_seeker'], ['saved_jobs', 'job_seeker'],
            ] as [$table, $role]) {
                if (DB::table($table.' as r')->join('users as u', 'u.id', '=', 'r.user_id')
                    ->whereRaw('BINARY u.role <> ?', [$role])->exists()) {
                    throw new RuntimeException("Existing $table rows reference the wrong account role. Correct them before migrating.");
                }
            }
        }

        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->string('event_type', 20);
            $table->timestamp('changed_at', 6)->useCurrent();
            $table->index(['application_id', 'id'], 'idx_history_application_sequence');
        });

        $created = [];
        try {
            if ($native) {
                DB::statement("ALTER TABLE application_status_history ADD CONSTRAINT chk_history_status CHECK ((old_status IS NULL OR BINARY old_status IN ('pending','accepted','rejected')) AND BINARY new_status IN ('pending','accepted','rejected'))");
                DB::statement("ALTER TABLE application_status_history ADD CONSTRAINT chk_history_event CHECK ((BINARY event_type IN ('baseline','created') AND old_status IS NULL) OR (BINARY event_type='status_changed' AND old_status IS NOT NULL AND BINARY old_status <> BINARY new_status))");
            }

            // A baseline records what is known at installation; it does not
            // invent past status changes or pretend this is the creation date.
            DB::table('application_status_history')->insertUsing(
                ['application_id', 'old_status', 'new_status', 'event_type'],
                DB::table('applications')->selectRaw("id, NULL, status, 'baseline'")
            );

            foreach ($definitions as $index => $sql) {
                DB::unprepared($sql);
                $created[] = CareerHubTriggers::NAMES[$index];
            }
        } catch (Throwable $error) {
            foreach (array_reverse($created) as $name) {
                DB::unprepared("DROP TRIGGER IF EXISTS `$name`");
            }
            Schema::dropIfExists('application_status_history');
            throw $error;
        }
    }

    public function down(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            foreach (array_reverse(CareerHubTriggers::NAMES) as $name) {
                DB::unprepared("DROP TRIGGER IF EXISTS `$name`");
            }
        }
        Schema::dropIfExists('application_status_history');
    }
};
