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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
        $table->string('title');
        $table->text('description');
        $table->string('location');
        $table->string('type');

        
        $table->unsignedInteger('salary_min')->nullable();
        $table->unsignedInteger('salary_max')->nullable();

       
        $table->string('currency', 3)->default('USD');

        
        $table->string('status')->default('active');

        
        $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
