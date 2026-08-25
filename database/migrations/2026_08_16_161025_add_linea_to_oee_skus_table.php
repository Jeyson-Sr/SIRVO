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
        Schema::table('oee_skus', function (Blueprint $table) {
            $table->string('linea', 32)->default('')->after('sku');
            $table->index(['linea', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oee_skus', function (Blueprint $table) {
            $table->dropIndex(['linea', 'activo']);
            $table->dropColumn('linea');
        });
    }
};
