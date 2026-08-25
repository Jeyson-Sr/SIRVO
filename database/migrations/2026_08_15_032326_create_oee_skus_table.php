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
        Schema::create('oee_skus', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 32)->unique();
            $table->string('descripcion');
            $table->string('formato', 32)->nullable();
            $table->string('marca', 64)->nullable();
            $table->string('sabor', 64)->nullable();
            $table->decimal('pallets_por_hora', 10, 2)->default(0);
            $table->decimal('bph', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['activo', 'marca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oee_skus');
    }
};
