<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE email_verification_codes DROP CONSTRAINT IF EXISTS email_verification_codes_type_check');
            DB::statement("ALTER TABLE email_verification_codes ADD CONSTRAINT email_verification_codes_type_check CHECK (type IN ('registration', 'password_change', 'password_reset'))");

            return;
        }

        Schema::table('email_verification_codes', function (Blueprint $table) {
            $table->enum('type', ['registration', 'password_change', 'password_reset'])->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('email_verification_codes')
                ->where('type', 'password_reset')
                ->delete();

            DB::statement('ALTER TABLE email_verification_codes DROP CONSTRAINT IF EXISTS email_verification_codes_type_check');
            DB::statement("ALTER TABLE email_verification_codes ADD CONSTRAINT email_verification_codes_type_check CHECK (type IN ('registration', 'password_change'))");

            return;
        }

        Schema::table('email_verification_codes', function (Blueprint $table) {
            $table->enum('type', ['registration', 'password_change'])->change();
        });
    }
};
