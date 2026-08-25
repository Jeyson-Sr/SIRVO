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
        Schema::table('cod_stops', function (Blueprint $table) {
            $table->boolean('es_tetra_pak')->default(false)->after('familia_oee');
            $table->index(['es_tetra_pak', 'tipo_parada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cod_stops', function (Blueprint $table) {
            $table->dropIndex(['es_tetra_pak', 'tipo_parada']);
            $table->dropColumn('es_tetra_pak');
        });
    }
};
