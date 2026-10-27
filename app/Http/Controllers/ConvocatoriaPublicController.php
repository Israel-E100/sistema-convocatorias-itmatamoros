<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConvocatoriaPublicController extends Controller
{
    public function show(Request $request, Convocatoria $convocatoria): View
    {
        $convocatoria->load('plaza');
        $plazaReservadaPorOtraPersona = $convocatoria->plaza?->ofrecida_para_ascenso
            && $convocatoria->plaza->reservada_por !== $request->user()?->id;
        $postulacionExistente = $request->user()?->role === 'usuario'
            ? $convocatoria->postulaciones()
                ->where('user_id', $request->user()->id)
                ->first()
            : null;

        return view('convocatorias.show', compact(
            'convocatoria',
            'postulacionExistente',
            'plazaReservadaPorOtraPersona',
        ));
    }
}
