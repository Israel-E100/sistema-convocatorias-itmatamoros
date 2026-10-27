<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plazas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 5)->unique();
            $table->string('tipo_tiempo', 30);
            $table->unsignedTinyInteger('orden');
            $table->foreignId('user_id')->nullable()->constrained()->noActionOnDelete();
            $table->timestamps();

            $table->index(['tipo_tiempo', 'orden']);
            $table->index('user_id');
        });

        $plazas = [];
        foreach ([
            'Tiempo completo' => ['E3817', 'E3815', 'E3813', 'E3811', 'E3809', 'E3807'],
            '3/4 tiempo' => ['E3717', 'E3715', 'E3713', 'E3711', 'E3709', 'E3707'],
            '1/2 Tiempo' => ['E3617', 'E3615', 'E3613', 'E3611', 'E3609', 'E3607'],
        ] as $tipoTiempo => $codes) {
            foreach ($codes as $orden => $codigo) {
                $plazas[] = [
                    'codigo' => $codigo,
                    'tipo_tiempo' => $tipoTiempo,
                    'orden' => $orden,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        DB::table('plazas')->insert($plazas);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plazas');
    }
};
