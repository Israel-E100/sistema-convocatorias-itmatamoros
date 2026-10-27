<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Convocatoria;
use App\Models\Postulacion;
use App\Models\User;
use App\Notifications\NuevaPostulacion;
use App\Services\PlazaAssignmentService;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PostulacionController extends Controller
{
    public function index(Request $request): View
    {
        return view('postulaciones.index', [
            'postulaciones' => Postulacion::query()
                ->with('convocatoria:id,titulo,tipo_personal,categoria_nivel')
                ->where('user_id', $request->user()->id)
                ->latest()
                ->paginate(15),
        ]);
    }

    public function show(Request $request, Postulacion $postulacion): View
    {
        abort_unless($postulacion->user_id === $request->user()->id, 404);

        return view('postulaciones.show', [
            'postulacion' => $postulacion->load('convocatoria'),
        ]);
    }

    public function store(
        Request $request,
        Convocatoria $convocatoria,
        PlazaAssignmentService $plazaService,
    ): RedirectResponse {
        abort_unless($request->user()->role === User::ROLE_USER, 403);

        if (! $convocatoria->isOpen()) {
            throw ValidationException::withMessages([
                'convocatoria' => 'Esta convocatoria no se encuentra abierta para recibir postulaciones.',
            ]);
        }

        $data = $request->validate([
            'informacion' => ['nullable', 'string', 'max:10000'],
            'documento' => ['nullable', 'file', 'mimes:pdf', 'extensions:pdf', 'max:10240'],
        ]);

        $path = null;

        try {
            $postulacion = DB::transaction(function () use (
                $request,
                $convocatoria,
                $data,
                $plazaService,
                &$path,
            ): Postulacion {
                $convocatoria = Convocatoria::query()
                    ->lockForUpdate()
                    ->findOrFail($convocatoria->id);

                if (! $convocatoria->isOpen()) {
                    throw ValidationException::withMessages([
                        'convocatoria' => 'Esta convocatoria ya no está recibiendo postulaciones.',
                    ]);
                }

                $plazaService->reserveForConvocatoriaApplication($request->user(), $convocatoria);

                $path = $request->file('documento')?->store('postulaciones', 'local');

                if ($request->hasFile('documento') && ! $path) {
                    throw new \RuntimeException('No se pudo guardar el documento de la postulación.');
                }

                if (Postulacion::query()
                    ->where('convocatoria_id', $convocatoria->id)
                    ->where('user_id', $request->user()->id)
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'convocatoria' => 'Ya enviaste una postulación a esta convocatoria.',
                    ]);
                }

                $postulacion = Postulacion::create([
                    'convocatoria_id' => $convocatoria->id,
                    'user_id' => $request->user()->id,
                    'estatus' => Postulacion::STATUS_PENDING,
                    'informacion' => [
                        'texto' => $data['informacion'] ?? null,
                        'perfil' => $request->user()->only([
                            'nombre',
                            'apellido_paterno',
                            'apellido_materno',
                            'curp',
                            'rfc',
                            'email',
                            'tipo_usuario',
                        ]),
                    ],
                    'documento_path' => $path,
                ]);

                AuditLog::record('postulacion.created', $request->user(), $request->user(), [
                    'postulacion_id' => $postulacion->id,
                    'convocatoria_id' => $convocatoria->id,
                    'convocatoria' => $convocatoria->titulo,
                ]);

                User::query()
                    ->where('role', User::ROLE_REVIEWER)
                    ->get()
                    ->each(fn (User $reviewer) => $reviewer->notify(new NuevaPostulacion($postulacion)));

                return $postulacion;
            });
        } catch (UniqueConstraintViolationException $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw ValidationException::withMessages([
                'convocatoria' => 'Ya enviaste una postulación a esta convocatoria.',
            ]);
        } catch (ValidationException $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        } catch (QueryException $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        return redirect()->route('postulaciones.show', $postulacion)
            ->with('status', 'Tu postulación fue enviada.');
    }

    public function document(Request $request, Postulacion $postulacion): BinaryFileResponse
    {
        abort_unless($postulacion->user_id === $request->user()->id, 404);
        abort_unless($postulacion->documento_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($postulacion->documento_path), 404);

        return response()->file($disk->path($postulacion->documento_path), [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Disposition' => 'inline; filename="expediente-'.$postulacion->id.'.pdf"',
        ]);
    }
}
