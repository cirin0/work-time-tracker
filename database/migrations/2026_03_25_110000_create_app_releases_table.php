<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 20)->default('android');
            $table->string('channel', 30)->default('stable');
            $table->unsignedInteger('version_code');
            $table->string('version_name', 50);
            $table->string('apk_path');
            $table->text('changelog')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['platform', 'channel', 'version_code']);
            $table->index(['platform', 'channel', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_releases');
    }
};

