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
        Schema::create('cod_stops', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('detalle');
            $table->string('tipo_parada', 8);
            $table->string('categoria')->nullable();
            $table->string('causa')->nullable();
            $table->string('recurso_afectado')->nullable();
            $table->string('familia_oee')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'tipo_parada']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cod_stops');
    }
};
