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
        Schema::table('oee_hour_details', function (Blueprint $table) {
            $table->decimal('duration_minutes', 5, 2)->default(60)->after('hour_range');
            $table->string('sku', 32)->nullable()->after('duration_minutes');
            $table->string('formato', 32)->nullable()->after('sku');
            $table->decimal('pallets_por_hora', 10, 2)->default(0)->after('formato');
            $table->decimal('bph', 12, 2)->default(0)->after('pallets_por_hora');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oee_hour_details', function (Blueprint $table) {
            $table->dropColumn([
                'duration_minutes',
                'sku',
                'formato',
                'pallets_por_hora',
                'bph',
            ]);
        });
    }
};
