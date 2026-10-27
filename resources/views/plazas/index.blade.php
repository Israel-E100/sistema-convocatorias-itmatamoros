@extends('layouts.app')

@section('title', 'Plazas - Sistema de Convocatorias')

@section('content')
    <section class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8"
        data-plazas-board data-live-state-url="{{ route('plazas.live-state') }}"
        data-can-interact="{{ $esDocente ? 'true' : 'false' }}">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Tablero de plazas</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Consulta las claves por jornada y el estado de ocupación, actualizado automáticamente.
                </p>
            </div>
            <p class="text-xs text-gray-500" aria-live="polite">
                <span data-plazas-updated>Actualizando estado...</span>
            </p>
        </div>

        @if (session('status'))
            <div role="status" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($esDocente && ! $miPlaza && ! auth()->user()->plaza_cambio_realizado)
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-5">
                <h2 class="font-semibold text-indigo-950">Asigna tu clave de puesto actual</h2>
                <p class="mt-1 text-sm text-indigo-900">
                    Tu cuenta docente aún no tiene una clave. Selecciona la que corresponde a tu plaza actual.
                </p>
                <form method="POST" action="{{ route('plazas.assign-current') }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                    @csrf
                    <select name="plaza_codigo" required
                        class="min-w-0 flex-1 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                        <option value="">Selecciona tu clave actual</option>
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
                    <button type="submit"
                        class="rounded-lg bg-indigo-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-indigo-600">
                        Guardar clave
                    </button>
                </form>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[600px] w-full border-collapse text-left text-sm">
                    <thead class="bg-slate-100 text-gray-800">
                        <tr>
                            @foreach ($columnas as $tipoTiempo => $codes)
                                <th scope="col" class="border border-gray-300 px-2 py-2 text-center text-xs font-bold sm:px-3 sm:py-2.5 sm:text-sm">
                                    {{ $tipoTiempo }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for ($row = 0; $row < 6; $row++)
                            <tr>
                                @foreach ($columnas as $codes)
                                    @php
                                        $codigo = $codes[$row];
                                        $plaza = $plazasPorCodigo->get($codigo);
                                        $stateClasses = match ($plaza->estado) {
                                            \App\Models\Plaza::STATUS_OCCUPIED => 'border-gray-300 bg-gray-100 text-gray-800',
                                            \App\Models\Plaza::STATUS_AVAILABLE => 'border-emerald-300 bg-emerald-50 text-emerald-950',
                                        };
                                        $isMyPlaza = $miPlaza?->is($plaza) ?? false;
                                    @endphp
                                    <td class="border border-gray-300 p-1 align-top">
                                        <article data-plaza-state data-codigo="{{ $codigo }}"
                                            data-estado="{{ $plaza->estado }}"
                                            data-convocatoria-url="{{ $plaza->convocatoria_url ?? '' }}"
                                            data-is-my-plaza="{{ $isMyPlaza ? 'true' : 'false' }}"
                                            class="rounded-md border p-1.5 transition-colors sm:p-2 {{ $stateClasses }}">
                                            <button type="button" data-plaza-action
                                                @disabled(! $esDocente || $plaza->estado === \App\Models\Plaza::STATUS_OCCUPIED || $plaza->estado === \App\Models\Plaza::STATUS_RESERVED || $isMyPlaza)
                                                class="w-full rounded text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-600 disabled:cursor-default">
                                                <span class="flex items-center justify-between gap-1">
                                                    <span class="text-xs font-bold tracking-wide sm:text-sm">{{ $codigo }}</span>
                                                    @if ($esDocente)
                                                        <span data-plaza-my-badge @if (! $isMyPlaza) hidden @endif
                                                            class="rounded-full bg-white/80 px-1.5 py-0.5 text-[9px] font-bold uppercase">
                                                            Mi plaza
                                                        </span>
                                                    @endif
                                                </span>
                                                <span data-plaza-label class="mt-0.5 block text-[10px] font-medium leading-tight sm:text-xs">
                                                    {{ $plaza->etiqueta_estado }}
                                                </span>
                                                <span data-plaza-occupant class="mt-0.5 block truncate text-[10px] leading-tight sm:text-xs">
                                                    @if ($plaza->ocupante)
                                                        Titular: {{ $plaza->ocupante->name }}
                                                    @endif
                                                </span>
                                            </button>

                                            @if ($esDocente)
                                                <form data-plaza-offer-form action="{{ route('plazas.promotion-offer') }}"
                                                    method="POST" @if (! $isMyPlaza) hidden @endif class="mt-1">
                                                    @csrf
                                                    <button type="submit" data-plaza-offer-button
                                                        @disabled($plaza->estado === \App\Models\Plaza::STATUS_RESERVED)
                                                        class="w-full rounded border border-white/80 bg-white/70 px-1.5 py-1 text-[9px] font-semibold leading-tight hover:bg-white disabled:cursor-not-allowed disabled:opacity-60 sm:text-[10px]">
                                                        @if ($plaza->estado === \App\Models\Plaza::STATUS_RESERVED)
                                                            Interés reservado
                                                        @elseif ($plaza->ofrecida_para_ascenso)
                                                            Retirar oferta
                                                        @else
                                                            Ofrecer para ascenso
                                                        @endif
                                                    </button>
                                                </form>
                                            @endif

                                            @if ($esDocente)
                                                <form data-plaza-claim-form action="{{ route('plazas.claim', $plaza) }}"
                                                    method="POST" hidden>
                                                    @csrf
                                                    <button type="submit">Interesarme en esta plaza</button>
                                                </form>
                                            @endif
                                        </article>
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <div class="grid gap-2 border-t border-gray-200 bg-gray-50 p-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-2 text-xs text-gray-700">
                    <span class="h-4 w-4 rounded border border-gray-300 bg-gray-200"></span>
                    Gris: plaza ocupada.
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-700">
                    <span class="h-4 w-4 rounded border border-emerald-300 bg-emerald-100"></span>
                    Verde: plaza disponible.
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-700">
                    <span class="h-4 w-4 rounded border border-blue-300 bg-blue-100"></span>
                    Azul: plaza ofrecida para ascenso.
                </div>
                <div class="flex items-center gap-2 text-xs text-gray-700">
                    <span class="h-4 w-4 rounded border border-amber-300 bg-amber-100"></span>
                    Ámbar: una persona ya reservó su interés.
                </div>
            </div>
        </div>

        <div data-plaza-notice class="fixed inset-0 z-[60] hidden items-center justify-center bg-slate-950/60 p-4"
            role="dialog" aria-modal="true" aria-labelledby="plaza-notice-title" aria-hidden="true">
            <div class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="plaza-notice-title" class="text-lg font-semibold text-gray-900">Próximas convocatorias</h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Esta plaza está disponible, pero aún no tiene una convocatoria asociada. Vuelve pronto para consultar nuevas convocatorias.
                        </p>
                    </div>
                    <button type="button" data-plaza-notice-close aria-label="Cerrar"
                        class="rounded-lg px-2 py-1 text-gray-500 hover:bg-gray-100">✕</button>
                </div>
                <button type="button" data-plaza-notice-close
                    class="mt-5 w-full rounded-lg bg-rose-900 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-800">
                    Entendido
                </button>
            </div>
        </div>
    </section>
@endsection
