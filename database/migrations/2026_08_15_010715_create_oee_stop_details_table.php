<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * `codigo`, `tipo` and `descripcion` are snapshots taken when the stop was
     * recorded. The catalog may be re-categorised later, and historical OEE
     * figures must stay reproducible.
     */
    public function up(): void
    {
        Schema::create('oee_stop_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oee_hour_detail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cod_stop_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('client_uuid');
            $table->string('codigo');
            $table->string('tipo', 8);
            $table->string('descripcion')->nullable();
            $table->decimal('tiempo_minutos', 8, 2);
            $table->unsignedSmallInteger('frecuencia')->default(1);
            $table->timestamp('registered_at');
            $table->timestamps();

            $table->unique(['oee_hour_detail_id', 'client_uuid']);
            $table->index(['oee_hour_detail_id', 'tipo']);
            $table->index('codigo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oee_stop_details');
    }
};
