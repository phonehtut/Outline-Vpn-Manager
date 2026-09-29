<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('access_keys', function (Blueprint $table) {
            $table->unsignedBigInteger('usage_bytes')->nullable()->after('data_limit_bytes');
            $table->timestamp('last_active_at')->nullable()->after('usage_bytes');
            $table->unsignedInteger('peak_device_count')->nullable()->after('last_active_at');
            $table->timestamp('peak_device_at')->nullable()->after('peak_device_count');
            $table->boolean('detailed_metrics_supported')->nullable()->after('peak_device_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_keys', function (Blueprint $table) {
            $table->dropColumn([
                'usage_bytes',
                'last_active_at',
                'peak_device_count',
                'peak_device_at',
                'detailed_metrics_supported',
            ]);
        });
    }
};
