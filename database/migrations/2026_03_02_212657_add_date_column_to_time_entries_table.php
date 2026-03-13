<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->date('date')->after('user_id')->index();
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['date']);
            $table->dropColumn('date');
        });
    }
};
