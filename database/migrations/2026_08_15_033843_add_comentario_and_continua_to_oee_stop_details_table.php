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
        Schema::table('oee_stop_details', function (Blueprint $table) {
            $table->text('comentario')->nullable()->after('descripcion');
            $table->boolean('continua')->default(false)->after('frecuencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('oee_stop_details', function (Blueprint $table) {
            $table->dropColumn(['comentario', 'continua']);
        });
    }
};
