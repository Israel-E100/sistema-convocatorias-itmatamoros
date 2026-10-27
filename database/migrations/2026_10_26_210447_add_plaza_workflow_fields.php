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
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('plaza_cambio_realizado')->default(false);
        });

        Schema::table('plazas', function (Blueprint $table): void {
            $table->boolean('ofrecida_para_ascenso')->default(false);
            $table->foreignId('reservada_por')->nullable()->constrained('users')->noActionOnDelete();
        });

        Schema::table('convocatorias', function (Blueprint $table): void {
            $table->foreignId('plaza_id')->nullable()->constrained('plazas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('convocatorias', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('plaza_id');
        });

        Schema::table('plazas', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reservada_por');
            $table->dropColumn('ofrecida_para_ascenso');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('plaza_cambio_realizado');
        });
    }
};
