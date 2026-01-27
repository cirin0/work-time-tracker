<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->text('start_comment')->nullable()->after('start_time');
            $table->text('stop_comment')->nullable()->after('stop_time');
            $table->dropColumn('comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('start_comment');
            $table->dropColumn('stop_comment');
            $table->text('comment')->nullable()->after('duration');
        });
    }
};
