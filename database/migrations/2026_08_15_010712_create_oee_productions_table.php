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
        Schema::create('oee_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');
            $table->string('turno', 16);
            $table->string('linea', 32);
            $table->string('op', 16);
            $table->string('ingeniero')->nullable();
            $table->string('operador')->nullable();
            $table->string('sku', 32)->nullable();
            $table->string('descripcion')->nullable();
            $table->string('formato', 32)->nullable();
            $table->string('marca', 64)->nullable();
            $table->string('sabor', 64)->nullable();
            $table->decimal('pallets_por_hora', 10, 2)->default(0);
            $table->decimal('bph', 12, 2)->default(0);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->unique(['team_id', 'fecha', 'turno', 'linea', 'op']);
            $table->index(['team_id', 'fecha']);
            $table->index(['team_id', 'linea']);
            $table->index(['team_id', 'marca']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oee_productions');
    }
};
