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
            DB::statement("ALTER TABLE email_verification_codes ADD CONSTRAINT email_verification_codes_type_check CHECK (type IN ('registration', 'password_change', 'password_reset', 'email_change'))");
        } else {
            Schema::table('email_verification_codes', function (Blueprint $table) {
                $table->enum('type', ['registration', 'password_change', 'password_reset', 'email_change'])->change();
            });
        }

        Schema::table('email_verification_codes', function (Blueprint $table) {
            $table->string('target')->nullable()->after('type')->comment('Stores target email for email_change type verification codes');
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::table('email_verification_codes')
                ->where('type', 'email_change')
                ->delete();

            DB::statement('ALTER TABLE email_verification_codes DROP CONSTRAINT IF EXISTS email_verification_codes_type_check');
            DB::statement("ALTER TABLE email_verification_codes ADD CONSTRAINT email_verification_codes_type_check CHECK (type IN ('registration', 'password_change', 'password_reset'))");
        } else {
            Schema::table('email_verification_codes', function (Blueprint $table) {
                $table->enum('type', ['registration', 'password_change', 'password_reset'])->change();
            });
        }

        Schema::table('email_verification_codes', function (Blueprint $table) {
            $table->dropColumn('target');
        });
    }
};
