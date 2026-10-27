<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Plaza;
use App\Models\User;
use App\Services\PlazaAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): RedirectResponse|View
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register', [
            'codigosDisponibles' => Plaza::query()
                ->whereNull('user_id')
                ->orderBy('tipo_tiempo')
                ->orderBy('orden')
                ->get()
                ->groupBy('tipo_tiempo'),
        ]);
    }

    public function store(Request $request, PlazaAssignmentService $plazaService): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
            'curp' => Str::upper((string) $request->input('curp')),
            'rfc' => Str::upper((string) $request->input('rfc')),
        ]);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido_paterno' => ['required', 'string', 'max:100'],
            'apellido_materno' => ['required', 'string', 'max:100'],
            'curp' => ['required', 'alpha_num', 'size:18', 'unique:users,curp'],
            'rfc' => ['required', 'alpha_num', 'min:12', 'max:13', 'unique:users,rfc'],
            'tipo_usuario' => ['required', Rule::in(User::APPLICANT_TYPES)],
            'plaza_codigo' => [
                Rule::requiredIf($request->input('tipo_usuario') === 'aspirante_docente'),
                'prohibited_unless:tipo_usuario,aspirante_docente',
                'nullable',
                Rule::in(collect(Plaza::CODES_BY_TIME)->flatten()->all()),
            ],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $plazaCode = $data['plaza_codigo'] ?? null;
        unset($data['plaza_codigo']);

        $user = DB::transaction(function () use ($data, $plazaCode, $plazaService): User {
            $user = User::create([
                ...$data,
                'name' => trim("{$data['nombre']} {$data['apellido_paterno']} {$data['apellido_materno']}"),
                'role' => User::ROLE_USER,
            ]);

            if ($plazaCode !== null) {
                $plazaService->assignCurrentPlaza($user, $plazaCode);
            }

            AuditLog::record('user.created', null, $user, [
                'source' => 'registration',
                'account' => $user->only([
                    'nombre',
                    'apellido_paterno',
                    'apellido_materno',
                    'curp',
                    'rfc',
                    'tipo_usuario',
                ]),
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
