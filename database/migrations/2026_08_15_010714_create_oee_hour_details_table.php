<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Attainment status and justified/pending minutes are intentionally absent:
     * they are derived from `estimado`, `producido` and the related stops, so
     * storing them would let the copies drift away from the source figures.
     */
    public function up(): void
    {
        Schema::create('oee_hour_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('oee_production_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('hour_index');
            $table->string('hour_range', 16);
            $table->decimal('estimado', 10, 2)->default(0);
            $table->decimal('producido', 10, 2)->nullable();
            $table->boolean('closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->text('comment_mnf')->nullable();
            $table->text('comment_mantto')->nullable();
            $table->text('comment_calidad')->nullable();
            $table->timestamps();

            $table->unique(['oee_production_id', 'hour_index']);
            $table->index(['oee_production_id', 'closed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oee_hour_details');
    }
};
