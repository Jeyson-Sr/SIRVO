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
        Schema::create('oee_sku_bph_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oee_sku_id')->constrained('oee_skus')->cascadeOnDelete();
            $table->string('sku', 32);
            $table->string('linea', 32);
            $table->decimal('bph_anterior', 12, 2)->nullable();
            $table->decimal('bph_nuevo', 12, 2);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['sku', 'linea']);
            $table->index(['oee_sku_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oee_sku_bph_changes');
    }
};
