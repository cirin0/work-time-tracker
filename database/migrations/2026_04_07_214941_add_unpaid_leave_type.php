<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE leave_requests DROP CONSTRAINT IF EXISTS leave_requests_type_check');
            DB::statement("ALTER TABLE leave_requests ADD CONSTRAINT leave_requests_type_check CHECK (type IN ('sick', 'vacation', 'personal', 'unpaid', 'business_trip'))");

            return;
        }

        Schema::table('leave_requests', function ($table) {
            $table->enum('type', ['sick', 'vacation', 'personal', 'unpaid', 'business_trip'])->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('leave_requests')
                ->where('type', 'unpaid')
                ->update(['type' => 'personal']);

            DB::statement('ALTER TABLE leave_requests DROP CONSTRAINT IF EXISTS leave_requests_type_check');
            DB::statement("ALTER TABLE leave_requests ADD CONSTRAINT leave_requests_type_check CHECK (type IN ('sick', 'vacation', 'personal', 'business_trip'))");

            return;
        }

        Schema::table('leave_requests', function ($table) {
            $table->enum('type', ['sick', 'vacation', 'personal', 'business_trip'])->change();
        });
    }
};
