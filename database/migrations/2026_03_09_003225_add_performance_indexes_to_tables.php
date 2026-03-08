<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->index('stop_time');
            $table->index('start_time');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('manager_id');
            $table->index('role');
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->index('status');
            $table->index('start_date');
            $table->index('end_date');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['stop_time']);
            $table->dropIndex(['start_time']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropIndex(['manager_id']);
            $table->dropIndex(['role']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['start_date']);
            $table->dropIndex(['end_date']);
            $table->dropIndex(['user_id', 'status']);
        });
    }
};
