@extends('layouts.app')

@section('title', $convocatoria->titulo.' - Convocatorias ITM')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}#convocatorias" class="text-sm font-semibold text-rose-900 hover:underline">&larr; Convocatorias</a>

        @if (session('status'))
            <p role="status" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <article class="mt-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-900">{{ $convocatoria->tipo_personal }}</span>
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ $convocatoria->estatus }}</span>
            </div>
            <h1 class="mt-4 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $convocatoria->titulo }}</h1>
            <p class="mt-2 text-gray-600">{{ $convocatoria->tipo_concurso }}</p>

            <dl class="mt-6 grid grid-cols-1 gap-4 rounded-lg bg-rose-50 p-4 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold text-gray-500">Categoría / nivel</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $convocatoria->categoria_nivel }}</dd></div>
                <div><dt class="text-xs font-semibold text-gray-500">Plazas disponibles</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $convocatoria->plaza_disponible }}</dd></div>
                @if ($convocatoria->plaza)
                    <div><dt class="text-xs font-semibold text-gray-500">Código de plaza</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $convocatoria->plaza->codigo }}</dd></div>
                @endif
                <div><dt class="text-xs font-semibold text-gray-500">Jornada / horario</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $convocatoria->jornada_horario }}</dd></div>
                <div><dt class="text-xs font-semibold text-gray-500">Remuneración</dt><dd class="mt-1 text-sm font-medium text-emerald-800">{{ $convocatoria->salario ? '$'.number_format((float) $convocatoria->salario, 2).' MXN' : 'No especificada' }}</dd></div>
                <div><dt class="text-xs font-semibold text-gray-500">Apertura</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $convocatoria->fecha_inicio->format('d/m/Y') }}</dd></div>
                <div><dt class="text-xs font-semibold text-gray-500">Cierre</dt><dd class="mt-1 text-sm font-medium text-gray-900">{{ $convocatoria->fecha_fin->format('d/m/Y') }}</dd></div>
            </dl>

            <div class="mt-6">
                <h2 class="font-bold text-gray-900">Requisitos</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-700">{{ $convocatoria->requisitos_especificos ?: 'Consulta las bases con el área correspondiente.' }}</p>
            </div>

            <div class="mt-8 border-t border-gray-100 pt-6">
                @if (! $convocatoria->isOpen())
                    <p class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">Esta convocatoria no está recibiendo postulaciones actualmente.</p>
                @elseif ($plazaReservadaPorOtraPersona)
                    <p class="rounded-lg bg-amber-50 p-4 text-sm text-amber-900">
                        Otra persona ya reservó su interés en la plaza asociada a esta convocatoria.
                    </p>
                @elseif ($postulacionExistente)
                    <p class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800">
                        Ya enviaste una postulación a esta convocatoria.
                        <a href="{{ route('postulaciones.show', $postulacionExistente) }}" class="font-semibold underline">Consultar estatus</a>
                    </p>
                @elseif (auth()->check() && auth()->user()->role === \App\Models\User::ROLE_USER)
                    <h2 class="text-lg font-bold text-gray-900">Postularme a esta convocatoria</h2>
                    @if ($errors->any())
                        <div role="alert" class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>
                    @endif
                    <form action="{{ route('postulaciones.store', $convocatoria) }}" method="POST" enctype="multipart/form-data" class="mt-4 space-y-4">
                        @csrf
                        <div>
                            <label for="informacion" class="mb-1 block text-sm font-semibold text-gray-700">Información adicional</label>
                            <textarea id="informacion" name="informacion" rows="5" maxlength="10000" placeholder="Agrega información que quieras compartir con el comité."
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-rose-900">{{ old('informacion') }}</textarea>
                        </div>
                        <div>
                            <label for="documento" class="mb-1 block text-sm font-semibold text-gray-700">Expediente PDF (opcional, máximo 10 MB)</label>
                            <input id="documento" type="file" name="documento" accept="application/pdf,.pdf"
                                class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        </div>
                        <button class="rounded-lg bg-rose-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Enviar postulación</button>
                    </form>
                @elseif (! auth()->check())
                    <p class="text-sm text-gray-600">
                        <a href="{{ route('login') }}" class="font-semibold text-rose-900 hover:underline">Inicia sesión</a>
                        o <a href="{{ route('register') }}" class="font-semibold text-rose-900 hover:underline">crea una cuenta</a> para postularte.
                    </p>
                @else
                    <p class="text-sm text-gray-600">Las cuentas de dictaminador y administrador no pueden presentar postulaciones.</p>
                @endif
            </div>
        </article>
    </section>
@endsection
