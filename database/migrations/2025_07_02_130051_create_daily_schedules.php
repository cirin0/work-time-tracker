<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('daily_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_schedule_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time')->default('09:00:00');
            $table->time('end_time')->default('18:00:00');
            $table->integer('break_duration')->default(60);
            $table->boolean('is_working_day')->default(true);
            $table->timestamps();

            $table->unique(['work_schedule_id', 'day_of_week'], 'unique_work_schedule_day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_schedules');
    }
};
