@extends('layouts.app')

@section('title', 'Registro de Aspirante - Sistema de Convocatorias')

@section('content')
    <div class="flex min-h-[70vh] flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
        
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center mb-6">
            <div class="inline-block bg-rose-950 text-white font-bold text-2xl p-3 rounded-lg shadow-md mb-2">
                ITM
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900">Registro de Cuenta de Aspirante</h2>
            <p class="text-xs text-gray-600 mt-1">Ingresa tus datos personales para solicitar plazas docentes o no docentes</p>
        </div>

        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl sm:max-w-xl sm:w-full border border-gray-200">
            <form action="{{ route('register.store') }}" method="POST" class="space-y-5">
                @csrf
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

                <div>
                    <label for="nombre" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre(s)</label>
                    <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}" maxlength="100" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="apellido_paterno" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Apellido Paterno</label>
                        <input id="apellido_paterno" type="text" name="apellido_paterno" value="{{ old('apellido_paterno') }}" maxlength="100" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                    </div>
                    <div>
                        <label for="apellido_materno" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Apellido Materno</label>
                        <input id="apellido_materno" type="text" name="apellido_materno" value="{{ old('apellido_materno') }}" maxlength="100" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="curp" class="block text-xs font-semibold text-gray-700 uppercase mb-1">CURP</label>
                        <input id="curp" type="text" name="curp" value="{{ old('curp') }}" maxlength="18" minlength="18" required placeholder="18 caracteres"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-rose-900 focus:outline-none">
                    </div>
                    <div>
                        <label for="rfc" class="block text-xs font-semibold text-gray-700 uppercase mb-1">RFC</label>
                        <input id="rfc" type="text" name="rfc" value="{{ old('rfc') }}" maxlength="13" minlength="12" required placeholder="12 o 13 caracteres"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm uppercase focus:ring-2 focus:ring-rose-900 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label for="tipo_usuario" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo de Perfil</label>
                    <select id="tipo_usuario" name="tipo_usuario" required data-profile-type
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                        <option value="aspirante_docente" @selected(old('tipo_usuario', 'aspirante_docente') === 'aspirante_docente')>Docente con plaza actual</option>
                        <option value="aspirante_no_docente" @selected(old('tipo_usuario') === 'aspirante_no_docente')>Aspirante a Plaza No Docente / Administrativa</option>
                    </select>
                </div>

                <div data-plaza-code-field>
                    <label for="plaza_codigo" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Clave de puesto actual</label>
                    <select id="plaza_codigo" name="plaza_codigo" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                        <option value="">Selecciona tu clave</option>
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
                    <p class="mt-1 text-xs text-gray-500">La clave debe corresponder a tu plaza actual.</p>
                    @error('plaza_codigo')
                        <p class="mt-1 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Correo Electrónico</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="correo@ejemplo.com"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Contraseña</label>
                        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="••••••••"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-semibold text-gray-700 uppercase mb-1">Confirmar Contraseña</label>
                        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="••••••••"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-rose-900 focus:outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full bg-rose-900 hover:bg-rose-800 text-white font-semibold py-2.5 rounded-lg shadow-md transition duration-200 text-sm">
                    Registrar Cuenta e Iniciar Expediente
                </button>
            </form>

            <div class="mt-6 text-center border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-600">
                    ¿Ya tienes una cuenta registrada? 
                    <a href="{{ route('login') }}" class="font-bold text-rose-900 hover:underline">Inicia Sesión aquí</a>
                </p>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="/" class="text-xs text-gray-500 hover:text-gray-800">&larr; Volver al catálogo de convocatorias</a>
        </div>
    </div>
@endsection