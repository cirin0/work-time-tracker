<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->integer('lateness_minutes')->nullable()->after('location_data');
            $table->integer('early_leave_minutes')->nullable()->after('lateness_minutes');
            $table->time('scheduled_start_time')->nullable()->after('early_leave_minutes');
            $table->time('scheduled_end_time')->nullable()->after('scheduled_start_time');
            $table->integer('overtime_minutes')->nullable()->after('scheduled_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn([
                'lateness_minutes',
                'early_leave_minutes',
                'scheduled_start_time',
                'scheduled_end_time',
                'overtime_minutes']);
        });
    }
};
