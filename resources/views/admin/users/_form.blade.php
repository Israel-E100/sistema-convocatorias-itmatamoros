<form action="{{ $action }}" method="POST" class="space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="mb-2 font-semibold">Revisa los datos del formulario:</p>
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="nombre" class="mb-1 block text-sm font-semibold text-gray-700">Nombre(s)</label>
            <input id="nombre" name="nombre" value="{{ old('nombre', $user->nombre ?? '') }}" required maxlength="100"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="apellido_paterno" class="mb-1 block text-sm font-semibold text-gray-700">Apellido paterno</label>
            <input id="apellido_paterno" name="apellido_paterno"
                value="{{ old('apellido_paterno', $user->apellido_paterno ?? '') }}" maxlength="100"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="apellido_materno" class="mb-1 block text-sm font-semibold text-gray-700">Apellido materno</label>
            <input id="apellido_materno" name="apellido_materno"
                value="{{ old('apellido_materno', $user->apellido_materno ?? '') }}" maxlength="100"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="tipo_usuario" class="mb-1 block text-sm font-semibold text-gray-700">Tipo de perfil (aspirantes)</label>
            <select id="tipo_usuario" name="tipo_usuario" data-admin-profile-type
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
                <option value="">No aplica</option>
                @foreach ($applicantTypes as $type)
                    <option value="{{ $type }}" @selected(old('tipo_usuario', $user->tipo_usuario ?? '') === $type)>
                        {{ $type === 'aspirante_docente' ? 'Aspirante docente' : 'Aspirante no docente' }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if ($plazaActual)
        <div data-admin-current-plaza
            class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-900">
            Clave de puesto actual: <strong>{{ $plazaActual->codigo }}</strong>.
            Para cambiar el perfil o el rol, primero debe resolverse esta asignación.
        </div>
    @else
        <div data-admin-plaza-field
            @class(['hidden' => old('role', $user->role ?? \App\Models\User::ROLE_USER) !== \App\Models\User::ROLE_USER
                || old('tipo_usuario', $user->tipo_usuario ?? '') !== 'aspirante_docente'])>
            <label for="plaza_codigo" class="mb-1 block text-sm font-semibold text-gray-700">Clave de puesto actual</label>
            <select id="plaza_codigo" name="plaza_codigo" data-admin-plaza-select
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
                <option value="">Selecciona la clave</option>
                @foreach ($codigosDisponibles as $tipoTiempo => $plazasTiempo)
                    <optgroup label="{{ $tipoTiempo }}">
                        @foreach ($plazasTiempo as $plaza)
                            <option value="{{ $plaza->codigo }}" @selected(old('plaza_codigo') === $plaza->codigo)>
                                {{ $plaza->codigo }}
                            </option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">Obligatoria para cuentas con perfil docente.</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="curp" class="mb-1 block text-sm font-semibold text-gray-700">CURP</label>
            <input id="curp" name="curp" value="{{ old('curp', $user->curp ?? '') }}" maxlength="18"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="rfc" class="mb-1 block text-sm font-semibold text-gray-700">RFC</label>
            <input id="rfc" name="rfc" value="{{ old('rfc', $user->rfc ?? '') }}" maxlength="13"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
    </div>

    <div>
        <label for="email" class="mb-1 block text-sm font-semibold text-gray-700">Correo electrónico</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required maxlength="255"
            autocomplete="email"
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
    </div>

    <div>
        <label for="role" class="mb-1 block text-sm font-semibold text-gray-700">Rol del sistema</label>
        <select id="role" name="role" required data-admin-role
            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(old('role', $user->role ?? 'usuario') === $role)>
                    {{ ucfirst($role) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label for="password" class="mb-1 block text-sm font-semibold text-gray-700">
                Contraseña {{ isset($user) ? '(opcional)' : '' }}
            </label>
            <input id="password" type="password" name="password" @required(! isset($user)) minlength="8"
                autocomplete="new-password"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
        <div>
            <label for="password_confirmation" class="mb-1 block text-sm font-semibold text-gray-700">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" @required(! isset($user)) minlength="8"
                autocomplete="new-password"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">
        </div>
    </div>

    <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.users.index') }}"
            class="rounded-lg border border-gray-300 px-4 py-2.5 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
        <button type="submit"
            class="rounded-lg bg-rose-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">{{ $submitLabel }}</button>
    </div>
</form>
