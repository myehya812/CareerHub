<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {

            // Nullable keeps older jobs valid until they are linked to an owner.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete(); // If the user is deleted, delete their jobs too.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {

            
            $table->dropForeign(['user_id']);

        
            $table->dropColumn('user_id');
        });
    }
};