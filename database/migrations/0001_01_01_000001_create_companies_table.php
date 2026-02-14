<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('description')->nullable();
            $table->string('logo')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
