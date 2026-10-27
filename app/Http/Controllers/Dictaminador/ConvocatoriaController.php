<?php

namespace App\Http\Controllers\Dictaminador;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Convocatoria;
use App\Models\Plaza;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ConvocatoriaController extends Controller
{
    public function index(): View
    {
        return view('dictaminador.convocatorias.index', [
            'convocatorias' => Convocatoria::query()
                ->withCount([
                    'postulaciones',
                    'postulaciones as pendientes_count' => fn ($query) => $query
                        ->where('estatus', 'pendiente'),
                ])
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('dictaminador.convocatorias.create', [
            'plazasPorTiempo' => $this->plazasPorTiempo(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);
        $actor = $request->user();

        DB::transaction(function () use ($data, $actor): void {
            $convocatoria = Convocatoria::create([
                ...$data,
                'created_by' => $actor->id,
            ]);

            AuditLog::record('convocatoria.created', $actor, null, [
                'convocatoria_id' => $convocatoria->id,
                'after' => $this->auditableAttributes($convocatoria),
            ]);
        });

        return redirect()->route('dictaminador.convocatorias.index')
            ->with('status', 'La convocatoria fue creada.');
    }

    public function edit(Convocatoria $convocatoria): View
    {
        return view('dictaminador.convocatorias.edit', [
            'convocatoria' => $convocatoria,
            'plazasPorTiempo' => $this->plazasPorTiempo(),
        ]);
    }

    public function update(Request $request, Convocatoria $convocatoria): RedirectResponse
    {
        $data = $this->validatedData($request);
        $actor = $request->user();

        DB::transaction(function () use ($data, $convocatoria, $actor): void {
            $convocatoria = Convocatoria::query()->lockForUpdate()->findOrFail($convocatoria->id);
            $before = $this->auditableAttributes($convocatoria);
            $convocatoria->update($data);

            AuditLog::record('convocatoria.updated', $actor, null, [
                'convocatoria_id' => $convocatoria->id,
                'before' => $before,
                'after' => $this->auditableAttributes($convocatoria->refresh()),
            ]);
        });

        return redirect()->route('dictaminador.convocatorias.index')
            ->with('status', 'La convocatoria fue actualizada.');
    }

    public function destroy(Request $request, Convocatoria $convocatoria): RedirectResponse
    {
        DB::transaction(function () use ($request, $convocatoria): void {
            $convocatoria = Convocatoria::query()->lockForUpdate()->findOrFail($convocatoria->id);

            if ($convocatoria->postulaciones()->exists()) {
                throw ValidationException::withMessages([
                    'convocatoria' => 'No es posible eliminar una convocatoria con postulaciones; puedes cerrarla en su lugar.',
                ]);
            }

            AuditLog::record('convocatoria.deleted', $request->user(), null, [
                'convocatoria_id' => $convocatoria->id,
                'deleted' => $this->auditableAttributes($convocatoria),
            ]);

            $convocatoria->delete();
        });

        return redirect()->route('dictaminador.convocatorias.index')
            ->with('status', 'La convocatoria fue eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request): array
    {
        return $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'tipo_concurso' => ['required', Rule::in(['Abierta', 'Cerrada'])],
            'tipo_personal' => ['required', Rule::in(['Docente', 'No docente'])],
            'categoria_nivel' => ['required', 'string', 'max:150'],
            'plaza_disponible' => ['required', 'string', 'max:150'],
            'plaza_id' => [
                'nullable',
                Rule::prohibitedIf($request->input('tipo_personal') !== 'Docente'),
                Rule::exists('plazas', 'id'),
            ],
            'jornada_horario' => ['required', 'string', 'max:150'],
            'salario' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'estatus' => ['required', Rule::in(['Activa', 'Borrador', 'Cerrada'])],
            'requisitos_especificos' => ['nullable', 'string', 'max:10000'],
        ]);
    }

    /**
     * @return Collection<int, Collection<int, Plaza>>
     */
    private function plazasPorTiempo(): Collection
    {
        $plazasPorCodigo = Plaza::query()
            ->orderBy('orden')
            ->get()
            ->keyBy('codigo');

        return collect(Plaza::CODES_BY_TIME)
            ->map(fn (array $codes) => collect($codes)
                ->map(fn (string $code) => $plazasPorCodigo->get($code))
                ->filter()
                ->values());
    }

    /**
     * @return array<string, mixed>
     */
    private function auditableAttributes(Convocatoria $convocatoria): array
    {
        return $convocatoria->only([
            'titulo',
            'tipo_concurso',
            'tipo_personal',
            'categoria_nivel',
            'plaza_disponible',
            'plaza_id',
            'jornada_horario',
            'salario',
            'fecha_inicio',
            'fecha_fin',
            'estatus',
            'requisitos_especificos',
        ]);
    }
}
