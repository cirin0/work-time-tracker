<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->enum('entry_type', ['gps', 'qr', 'gps_qr', 'remote', 'manual'])->default('gps')->after('duration');
            $table->json('location_data')->nullable()->after('entry_type');
        });
    }

    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropColumn('entry_type', 'location_data');
        });
    }
};
