<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('work_schedule_id')->references('id')->on('work_schedules')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
