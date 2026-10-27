<?php

namespace App\Http\Controllers;

use App\Models\Convocatoria;
use App\Models\Plaza;
use App\Models\User;
use App\Services\PlazaAssignmentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlazaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $plazas = $this->plazaStatuses($user->id);
        $codigosDisponibles = Plaza::query()
            ->whereNull('user_id')
            ->where('ofrecida_para_ascenso', false)
            ->whereNull('reservada_por')
            ->get()
            ->keyBy('codigo');
        $codigosDisponibles = collect(Plaza::CODES_BY_TIME)
            ->map(fn (array $codes) => collect($codes)
                ->map(fn (string $code) => $codigosDisponibles->get($code))
                ->filter()
                ->values());

        return view('plazas.index', [
            'plazas' => $plazas,
            'plazasPorCodigo' => $plazas->keyBy('codigo'),
            'columnas' => Plaza::CODES_BY_TIME,
            'miPlaza' => Plaza::query()->where('user_id', $user->id)->first(),
            'codigosDisponibles' => $codigosDisponibles,
            'esDocente' => $user->role === User::ROLE_USER
                && $user->tipo_usuario === 'aspirante_docente',
        ]);
    }

    public function liveState(Request $request): JsonResponse
    {
        return response()->json([
            'plazas' => $this->plazaStatuses($request->user()->id)
                ->map(fn (Plaza $plaza): array => [
                    'codigo' => $plaza->codigo,
                    'estado' => $plaza->estado,
                    'etiqueta' => $plaza->etiqueta_estado,
                    'ocupante' => $plaza->ocupante?->name,
                    'convocatoria_url' => $plaza->convocatoria_url,
                    'es_mi_plaza' => $plaza->es_mi_plaza,
                ])
                ->values(),
            'actualizado' => now()->toIso8601String(),
        ])->header('Cache-Control', 'no-store');
    }

    public function assignCurrent(Request $request, PlazaAssignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'plaza_codigo' => ['required', Rule::in(collect(Plaza::CODES_BY_TIME)->flatten()->all())],
        ]);

        $service->assignCurrentPlaza($request->user(), $data['plaza_codigo']);

        return redirect()->route('plazas.index')
            ->with('status', 'Tu clave de puesto quedó asignada correctamente.');
    }

    public function togglePromotionOffer(Request $request, PlazaAssignmentService $service): RedirectResponse
    {
        $service->togglePromotionOffer($request->user());

        return redirect()->route('plazas.index')
            ->with('status', 'Se actualizó la oferta de tu plaza para ascenso.');
    }

    public function claimOffered(
        Request $request,
        Plaza $plaza,
        PlazaAssignmentService $service,
    ): RedirectResponse {
        $convocatoria = $service->reserveOfferedPlaza($request->user(), $plaza);

        return redirect()->route('convocatorias.show', $convocatoria)
            ->with('status', 'La plaza quedó reservada para tu simulación. Continúa en la convocatoria.');
    }

    /**
     * @return Collection<int, Plaza>
     */
    private function plazaStatuses(int $currentUserId): Collection
    {
        $plazas = Plaza::query()
            ->with('ocupante:id,name')
            ->orderBy('tipo_tiempo')
            ->orderBy('orden')
            ->get();
        $convocatorias = Convocatoria::query()
            ->whereIn('plaza_id', $plazas->pluck('id'))
            ->where('tipo_personal', 'Docente')
            ->where('estatus', 'Activa')
            ->whereDate('fecha_inicio', '<=', today())
            ->whereDate('fecha_fin', '>=', today())
            ->orderBy('fecha_inicio')
            ->orderBy('id')
            ->get()
            ->keyBy('plaza_id');

        return $plazas->map(function (Plaza $plaza) use ($convocatorias, $currentUserId): Plaza {
            $plaza->es_mi_plaza = $plaza->user_id === $currentUserId;
            $convocatoria = $convocatorias->get($plaza->id);
            $plaza->convocatoria_url = $convocatoria
                ? route('convocatorias.show', $convocatoria)
                : null;

            if ($plaza->reservada_por !== null) {
                $plaza->estado = Plaza::STATUS_RESERVED;
                $plaza->etiqueta_estado = 'Interés reservado';
            } elseif ($plaza->ofrecida_para_ascenso) {
                $plaza->estado = Plaza::STATUS_PROMOTION_PENDING;
                $plaza->etiqueta_estado = 'Disponible por ascenso';
            } elseif ($plaza->user_id !== null) {
                $plaza->estado = Plaza::STATUS_OCCUPIED;
                $plaza->etiqueta_estado = 'Ocupada';
            } else {
                $plaza->estado = Plaza::STATUS_AVAILABLE;
                $plaza->etiqueta_estado = 'Disponible';
            }

            return $plaza;
        });
    }
}
