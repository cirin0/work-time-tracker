<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedInteger('lateness_grace_minutes')->default(0)->after('qr_secret');
            $table->decimal('overtime_threshold_hours', 4, 2)->default(0)->after('lateness_grace_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['lateness_grace_minutes', 'overtime_threshold_hours']);
        });
    }
};
