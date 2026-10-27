<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plaza;
use App\Models\User;
use App\Services\PlazaAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::query()
                ->whereIn('role', User::MANAGED_ROLES)
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', [
            'roles' => User::MANAGED_ROLES,
            'applicantTypes' => User::APPLICANT_TYPES,
            'codigosDisponibles' => $this->availablePlazas(),
            'plazaActual' => null,
        ]);
    }

    public function store(Request $request, PlazaAssignmentService $plazaService): RedirectResponse
    {
        $data = $this->validatedData($request);
        $plazaCode = $data['plaza_codigo'] ?? null;
        unset($data['plaza_codigo']);
        $actor = $request->user();

        $user = DB::transaction(function () use ($data, $actor, $plazaCode, $plazaService): User {
            $user = User::create([
                ...$data,
                'name' => $this->fullName($data),
            ]);

            if ($plazaCode !== null) {
                $plazaService->assignCurrentPlaza($user, $plazaCode, $actor);
            }

            AuditLog::record('user.created', $actor, $user, [
                'source' => 'admin',
                'changed_fields' => array_values(array_diff(array_keys($data), ['password'])),
                'account' => $this->auditableAttributes($user),
            ]);

            return $user;
        });

        return redirect()->route('admin.users.index')
            ->with('status', "La cuenta de {$user->name} fue creada.");
    }

    public function edit(User $user): View
    {
        abort_unless(in_array($user->role, User::ROLES, true), 404);
        $plazaActual = $user->plaza;

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $user->role === User::ROLE_ADMIN
                ? User::ROLES
                : User::MANAGED_ROLES,
            'applicantTypes' => User::APPLICANT_TYPES,
            'codigosDisponibles' => $this->availablePlazas($user),
            'plazaActual' => $plazaActual,
        ]);
    }

    public function update(
        Request $request,
        User $user,
        PlazaAssignmentService $plazaService,
    ): RedirectResponse {
        $data = $this->validatedData($request, $user);
        $plazaCode = $data['plaza_codigo'] ?? null;
        unset($data['plaza_codigo']);
        $actor = $request->user();
        $updatedName = $this->fullName($data);

        if ($user->role === User::ROLE_ADMIN
            && ! in_array($data['role'], User::ROLES, true)) {
            abort(404);
        }

        if ($actor->is($user) && ($data['role'] ?? $user->role) !== User::ROLE_ADMIN) {
            return back()->withInput()->withErrors([
                'role' => 'No puedes quitarte tus propios permisos de administrador.',
            ]);
        }

        DB::transaction(function () use ($data, $user, $actor, $plazaCode, $plazaService): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            if ($user->role === User::ROLE_ADMIN
                && ($data['role'] ?? $user->role) !== User::ROLE_ADMIN
                && User::query()->where('role', User::ROLE_ADMIN)->lockForUpdate()->count() <= 1) {
                throw ValidationException::withMessages([
                    'role' => 'No se puede quitar el último administrador del sistema.',
                ]);
            }

            $before = $this->auditableAttributes($user);
            $passwordChanged = filled($data['password'] ?? null);

            $user->fill(collect($data)->except('password')->all());
            $user->name = $this->fullName($data);

            if ($passwordChanged) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();

            if ($plazaCode !== null) {
                $plazaService->assignCurrentPlaza($user, $plazaCode, $actor);
            }

            $after = $this->auditableAttributes($user);
            AuditLog::record('user.updated', $actor, $user, [
                'before' => $before,
                'after' => $after,
                'password_changed' => $passwordChanged,
            ]);
        });

        return redirect()->route('admin.users.index')
            ->with('status', "La cuenta de {$updatedName} fue actualizada.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_unless(in_array($user->role, User::MANAGED_ROLES, true), 404);

        if ($request->user()->is($user)) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta de administrador.');
        }

        DB::transaction(function () use ($request, $user): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);

            AuditLog::record('user.deleted', $request->user(), $user, [
                'deleted_user' => $this->auditableAttributes($user),
            ]);

            $user->delete();
        });

        return redirect()->route('admin.users.index')
            ->with('status', 'La cuenta fue eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedData(Request $request, ?User $user = null): array
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
            'curp' => filled($request->input('curp')) ? Str::upper((string) $request->input('curp')) : null,
            'rfc' => filled($request->input('rfc')) ? Str::upper((string) $request->input('rfc')) : null,
        ]);

        $hasCurrentPlaza = $user?->plaza()->exists() ?? false;
        $requiresPlaza = $request->input('role') === User::ROLE_USER
            && $request->input('tipo_usuario') === 'aspirante_docente'
            && ! $hasCurrentPlaza;
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['nullable', 'string', 'max:100'],
            'apellido_materno' => ['nullable', 'string', 'max:100'],
            'curp' => ['nullable', 'alpha_num', 'size:18', Rule::unique('users', 'curp')->ignore($user?->id)],
            'rfc' => ['nullable', 'alpha_num', 'min:12', 'max:13', Rule::unique('users', 'rfc')->ignore($user?->id)],
            'tipo_usuario' => [
                Rule::requiredIf($request->input('role') === User::ROLE_USER),
                'nullable',
                Rule::in(User::APPLICANT_TYPES),
            ],
            'plaza_codigo' => [
                Rule::requiredIf($requiresPlaza),
                Rule::prohibitedIf(! $requiresPlaza),
                Rule::in(collect(Plaza::CODES_BY_TIME)->flatten()->all()),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'role' => [
                'required',
                Rule::in($user?->role === User::ROLE_ADMIN ? User::ROLES : User::MANAGED_ROLES),
            ],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($hasCurrentPlaza && (
            $data['role'] !== User::ROLE_USER
            || ($data['tipo_usuario'] ?? null) !== 'aspirante_docente'
        )) {
            throw ValidationException::withMessages([
                'tipo_usuario' => 'No puedes cambiar el perfil o rol mientras la cuenta tenga una plaza asignada.',
            ]);
        }

        return $data;
    }

    /**
     * @return Collection<string, Collection<int, Plaza>>
     */
    private function availablePlazas(?User $user = null): Collection
    {
        $plazasDisponibles = Plaza::query()
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id');
                if ($user) {
                    $query->orWhere('user_id', $user->id);
                }
            })
            ->get()
            ->keyBy('codigo');

        return collect(Plaza::CODES_BY_TIME)
            ->map(fn (array $codes): Collection => collect($codes)
                ->map(fn (string $code): ?Plaza => $plazasDisponibles->get($code))
                ->filter()
                ->values());
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fullName(array $data): string
    {
        return trim(implode(' ', array_filter([
            $data['nombre'] ?? null,
            $data['apellido_paterno'] ?? null,
            $data['apellido_materno'] ?? null,
        ])));
    }

    /**
     * @return array<string, mixed>
     */
    private function auditableAttributes(User $user): array
    {
        return [
            ...$user->only([
                'name',
                'email',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'curp',
                'rfc',
                'tipo_usuario',
                'role',
            ]),
            'plaza_codigo' => $user->plaza?->codigo,
        ];
    }
}
