<?php

namespace App\Http\Controllers;

use App\Models\Plaza;
use App\Models\User;
use App\Services\PlazaAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load('plaza');
        $plazasDisponibles = Plaza::query()
            ->with('ocupante:id,name')
            ->orderBy('tipo_tiempo')
            ->orderBy('orden')
            ->get()
            ->groupBy('tipo_tiempo');

        return view('profile.show', [
            'user' => $user,
            'plazasDisponibles' => $plazasDisponibles,
            'puedeElegirPlaza' => $user->role === User::ROLE_USER,
        ]);
    }

    public function updateCurrentPlaza(Request $request, PlazaAssignmentService $service): RedirectResponse
    {
        $data = $request->validate([
            'plaza_codigo' => [
                'required',
                Rule::in(collect(Plaza::CODES_BY_TIME)->flatten()->all()),
            ],
        ]);

        $service->changeCurrentPlaza($request->user(), $data['plaza_codigo']);

        return redirect()->route('profile.show')
            ->with('status', 'La plaza de tu perfil se actualizó correctamente.');
    }

    public function settings(): View
    {
        return view('profile.settings');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'extensions:jpg,jpeg,png,webp',
                'dimensions:max_width=3000,max_height=3000',
                'max:2048',
            ],
        ]);

        $user = $request->user();
        $disk = Storage::disk('local');
        $newPath = $request->file('photo')->store('profile-photos/'.$user->id, 'local');

        if (! $newPath) {
            throw new RuntimeException('No se pudo guardar la foto de perfil.');
        }

        $previousPath = $user->profile_photo_path;

        try {
            DB::transaction(function () use ($user, $newPath): void {
                $user->profile_photo_path = $newPath;
                $user->save();
            });
        } catch (Throwable $exception) {
            $disk->delete($newPath);

            throw $exception;
        }

        if ($previousPath && $disk->exists($previousPath) && ! $disk->delete($previousPath)) {
            throw new RuntimeException('La foto se actualizó, pero no se pudo eliminar la imagen anterior.');
        }

        if ($user->refresh()->profile_photo_path !== $newPath) {
            throw new RuntimeException('No se pudo confirmar el cambio de la foto de perfil.');
        }

        return redirect()->route('profile.show')->with('status', 'La foto de perfil se actualizó correctamente.');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        $previousPath = $user->profile_photo_path;

        if (! $previousPath) {
            return redirect()->route('profile.show')->with('status', 'Tu cuenta no tiene una foto de perfil.');
        }

        DB::transaction(function () use ($user): void {
            $user->profile_photo_path = null;
            $user->save();
        });

        $disk = Storage::disk('local');

        if ($disk->exists($previousPath) && ! $disk->delete($previousPath)) {
            throw new RuntimeException('La foto se quitó de tu perfil, pero no se pudo eliminar el archivo.');
        }

        return redirect()->route('profile.show')->with('status', 'La foto de perfil se eliminó correctamente.');
    }

    public function photo(Request $request): BinaryFileResponse
    {
        $path = $request->user()->profile_photo_path;
        $disk = Storage::disk('local');

        abort_unless($path && $disk->exists($path), 404);

        return response()->file($disk->path($path), [
            'Content-Type' => $disk->mimeType($path) ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0',
        ])->setPrivate();
    }
}
