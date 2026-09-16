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
            $table->unsignedSmallInteger('um')->default(1)->after('sabor');
            $table->string('compania', 64)->nullable()->after('bph');
            $table->string('mercado', 64)->nullable()->after('compania');
            $table->unsignedSmallInteger('nivel')->default(1)->after('mercado');
            $table->unsignedSmallInteger('paq_cama')->default(1)->after('nivel');
            $table->unsignedSmallInteger('cartones')->default(0)->after('paq_cama');
            $table->unsignedInteger('paq_pallet')->default(1)->after('cartones');
        });

        Schema::table('oee_skus', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->unique(['sku', 'linea']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oee_skus', function (Blueprint $table) {
            $table->dropUnique(['sku', 'linea']);
            $table->unique('sku');

            $table->dropColumn([
                'um',
                'compania',
                'mercado',
                'nivel',
                'paq_cama',
                'cartones',
                'paq_pallet',
            ]);
        });
    }
};
