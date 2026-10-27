<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Convocatoria;
use App\Models\Plaza;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlazaAssignmentService
{
    public function assignCurrentPlaza(User $user, string $code, ?User $actor = null): Plaza
    {
        return DB::transaction(function () use ($user, $code, $actor): Plaza {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $plaza = Plaza::query()->where('codigo', $code)->lockForUpdate()->firstOrFail();

            if ($user->role !== User::ROLE_USER || $user->tipo_usuario !== 'aspirante_docente') {
                throw ValidationException::withMessages([
                    'plaza_codigo' => 'Solo los perfiles docentes pueden asignar una clave de puesto durante el registro.',
                ]);
            }

            if (Plaza::query()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'plaza_codigo' => 'Tu cuenta ya tiene una clave de puesto asignada.',
                ]);
            }

            if ($plaza->user_id !== null) {
                throw ValidationException::withMessages([
                    'plaza_codigo' => 'Esa clave ya está ocupada. Selecciona otra.',
                ]);
            }

            if ($plaza->ofrecida_para_ascenso || $plaza->reservada_por !== null) {
                throw ValidationException::withMessages([
                    'plaza_codigo' => 'Esa clave no está disponible para asignación.',
                ]);
            }

            $plaza->user_id = $user->id;
            $plaza->save();

            AuditLog::record('plaza.assigned', $actor, $user, [
                'plaza_codigo' => $plaza->codigo,
            ]);

            return $plaza;
        });
    }

    public function changeCurrentPlaza(User $user, string $code): Plaza
    {
        return DB::transaction(function () use ($user, $code): Plaza {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->role !== User::ROLE_USER) {
                throw ValidationException::withMessages([
                    'plaza_codigo' => 'Solo las cuentas de usuario pueden elegir una plaza.',
                ]);
            }

            $currentPlaza = Plaza::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();
            $targetPlaza = Plaza::query()
                ->where('codigo', $code)
                ->lockForUpdate()
                ->firstOrFail();

            if ($currentPlaza || $user->plaza_cambio_realizado) {
                throw ValidationException::withMessages([
                    'plaza_codigo' => 'Ya elegiste tu plaza y no se puede volver a modificar.',
                ]);
            }

            if ($targetPlaza->user_id !== null
                || $targetPlaza->ofrecida_para_ascenso
                || $targetPlaza->reservada_por !== null) {
                throw ValidationException::withMessages([
                    'plaza_codigo' => 'Esa clave no está disponible. Selecciona otra.',
                ]);
            }

            $targetPlaza->user_id = $user->id;
            $targetPlaza->save();
            $user->plaza_cambio_realizado = true;
            $user->save();

            AuditLog::record('plaza.assigned', $user, $user, [
                'plaza_codigo' => $targetPlaza->codigo,
                'source' => 'profile',
            ]);

            return $targetPlaza;
        });
    }

    public function togglePromotionOffer(User $user): Plaza
    {
        return DB::transaction(function () use ($user): Plaza {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $plaza = Plaza::query()->where('user_id', $user->id)->lockForUpdate()->first();

            if ($user->role !== User::ROLE_USER || $user->tipo_usuario !== 'aspirante_docente' || ! $plaza) {
                throw ValidationException::withMessages([
                    'plaza' => 'Necesitas una plaza asignada en un perfil docente para ofrecerla.',
                ]);
            }

            if ($plaza->reservada_por !== null) {
                throw ValidationException::withMessages([
                    'plaza' => 'La plaza ya tiene una persona interesada y no se puede modificar.',
                ]);
            }

            $offered = ! $plaza->ofrecida_para_ascenso;

            $plaza->ofrecida_para_ascenso = $offered;
            $plaza->save();

            AuditLog::record(
                $offered ? 'plaza.promotion_offered' : 'plaza.promotion_offer_withdrawn',
                $user,
                $user,
                ['plaza_codigo' => $plaza->codigo],
            );

            return $plaza;
        });
    }

    public function reserveOfferedPlaza(User $user, Plaza $plaza): Convocatoria
    {
        return DB::transaction(function () use ($user, $plaza): Convocatoria {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $plaza = Plaza::query()->lockForUpdate()->findOrFail($plaza->id);

            if ($user->role !== User::ROLE_USER || $user->tipo_usuario !== 'aspirante_docente') {
                throw ValidationException::withMessages([
                    'plaza' => 'Solo los usuarios docentes pueden interesarse en una plaza.',
                ]);
            }

            if ($plaza->user_id === $user->id || ! $plaza->ofrecida_para_ascenso) {
                throw ValidationException::withMessages([
                    'plaza' => 'Esta plaza ya no está disponible para postulación.',
                ]);
            }

            if ($plaza->reservada_por !== null) {
                throw ValidationException::withMessages([
                    'plaza' => 'Otra persona ya se interesó en esta plaza.',
                ]);
            }

            $convocatoria = Convocatoria::query()
                ->where('plaza_id', $plaza->id)
                ->where('tipo_personal', 'Docente')
                ->where('estatus', 'Activa')
                ->whereDate('fecha_inicio', '<=', today())
                ->whereDate('fecha_fin', '>=', today())
                ->orderByDesc('fecha_inicio')
                ->orderByDesc('id')
                ->first();

            if (! $convocatoria) {
                $convocatoria = Convocatoria::query()
                    ->where('tipo_personal', 'Docente')
                    ->where('estatus', 'Activa')
                    ->whereDate('fecha_inicio', '<=', today())
                    ->whereDate('fecha_fin', '>=', today())
                    ->orderByDesc('fecha_inicio')
                    ->orderByDesc('id')
                    ->first();
            }

            if (! $convocatoria) {
                throw ValidationException::withMessages([
                    'plaza' => 'No hay convocatorias docentes activas para continuar con la simulación.',
                ]);
            }

            $plaza->reservada_por = $user->id;
            $plaza->save();

            AuditLog::record('plaza.promotion_reserved', $user, $plaza->ocupante, [
                'plaza_codigo' => $plaza->codigo,
                'convocatoria_id' => $convocatoria->id,
            ]);

            return $convocatoria;
        });
    }

    public function reserveForConvocatoriaApplication(User $user, Convocatoria $convocatoria): void
    {
        if (! $convocatoria->plaza_id) {
            return;
        }

        DB::transaction(function () use ($user, $convocatoria): void {
            $plaza = Plaza::query()->lockForUpdate()->find($convocatoria->plaza_id);

            if (! $plaza || ! $plaza->ofrecida_para_ascenso) {
                return;
            }

            if ($user->tipo_usuario !== 'aspirante_docente') {
                throw ValidationException::withMessages([
                    'convocatoria' => 'Solo los perfiles docentes pueden postular a esta plaza.',
                ]);
            }

            if ($plaza->reservada_por !== null && $plaza->reservada_por !== $user->id) {
                throw ValidationException::withMessages([
                    'convocatoria' => 'Otra persona ya reservó el interés en esta plaza.',
                ]);
            }

            if ($plaza->reservada_por === null) {
                $plaza->reservada_por = $user->id;
                $plaza->save();

                AuditLog::record('plaza.promotion_reserved', $user, $plaza->ocupante, [
                    'plaza_codigo' => $plaza->codigo,
                    'convocatoria_id' => $convocatoria->id,
                ]);
            }
        });
    }
}
