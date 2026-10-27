@extends('layouts.app')

@section('title', 'Mi perfil - Sistema de Convocatorias')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Mi perfil</h1>
            <p class="mt-1 text-sm text-gray-600">Información registrada en tu cuenta.</p>
        </div>

        @if (session('status'))
            <p role="status" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </p>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center gap-4 border-b border-gray-100 bg-gray-50 px-6 py-5">
                <button type="button" data-photo-modal-open aria-label="Administrar foto de perfil"
                    aria-haspopup="dialog"
                    class="group relative flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-indigo-100 text-indigo-700 focus:outline-none focus:ring-2 focus:ring-rose-700 focus:ring-offset-2">
                    @if ($user->profile_photo_path)
                        <img src="{{ route('profile.photo', ['v' => $user->profile_photo_path]) }}" alt=""
                            class="h-full w-full object-cover">
                    @else
                        <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M20 21a8 8 0 0 0-16 0m8-10a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
                        </svg>
                    @endif
                    <span class="absolute inset-0 flex items-center justify-center bg-black/55 text-white opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M4 7h3l2-3h6l2 3h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Zm8 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" />
                        </svg>
                    </span>
                    <span class="absolute bottom-0 right-0 flex h-6 w-6 items-center justify-center rounded-full bg-rose-900 text-white ring-2 ring-white">
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M4 7h3l2-3h6l2 3h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2Zm8 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z" />
                        </svg>
                    </span>
                    <span class="sr-only">Cambiar o administrar foto de perfil</span>
                </button>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                    <p class="truncate text-sm text-gray-600">{{ $user->email }}</p>
                </div>
            </div>

            <dl class="grid grid-cols-1 gap-x-8 gap-y-5 p-6 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Nombre(s)</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->nombre ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Apellido paterno</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->apellido_paterno ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Apellido materno</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->apellido_materno ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Tipo de perfil</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->tipo_usuario ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">CURP</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->curp ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">RFC</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $user->rfc ?: 'No registrado' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Rol del sistema</dt>
                    <dd class="mt-1 text-sm capitalize text-gray-900">{{ $user->role }}</dd>
                </div>
            </dl>

            @if ($puedeElegirPlaza)
                <section class="border-t border-gray-100 p-6" aria-labelledby="profile-plaza-title">
                    <h2 id="profile-plaza-title" class="text-base font-semibold text-gray-900">Mi plaza</h2>
                    @if ($user->plaza)
                        <p class="mt-2 text-sm text-gray-700">
                            Clave actual: <strong>{{ $user->plaza->codigo }}</strong>
                        </p>
                        <p class="mt-2 text-sm text-gray-600">
                            Tu plaza ya fue elegida y no puede modificarse.
                        </p>
                    @elseif ($user->plaza_cambio_realizado)
                        <p class="mt-2 text-sm text-gray-600">
                            Ya utilizaste tu única elección de plaza.
                        </p>
                    @else
                        <p class="mt-2 text-sm text-gray-600">
                            Elige una clave disponible. Las plazas ocupadas o reservadas aparecen deshabilitadas.
                            Esta elección solo puede hacerse una vez.
                        </p>
                        <form action="{{ route('profile.plaza.update') }}" method="POST"
                            class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                            @csrf
                            @method('PUT')
                            <div class="min-w-0 flex-1">
                                <label for="plaza_codigo" class="mb-1 block text-sm font-semibold text-gray-700">
                                    Clave de plaza
                                </label>
                                <select id="plaza_codigo" name="plaza_codigo" required
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:border-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-200">
                                    <option value="">Selecciona una clave</option>
                                    @foreach ($plazasDisponibles as $tipoTiempo => $plazasTiempo)
                                        <optgroup label="{{ $tipoTiempo }}">
                                            @foreach ($plazasTiempo as $plaza)
                                                @php
                                                    $plazaNoDisponible = $plaza->user_id !== null
                                                        || $plaza->ofrecida_para_ascenso
                                                        || $plaza->reservada_por !== null;
                                                @endphp
                                                <option value="{{ $plaza->codigo }}" @disabled($plazaNoDisponible)
                                                    @selected(old('plaza_codigo') === $plaza->codigo)>
                                                    {{ $plaza->codigo }}
                                                    @if ($plaza->reservada_por !== null)
                                                        — reservada
                                                    @elseif ($plaza->ofrecida_para_ascenso)
                                                        — ofrecida para ascenso
                                                    @elseif ($plaza->user_id !== null)
                                                        — ocupada
                                                    @else
                                                        — disponible
                                                    @endif
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('plaza_codigo')
                                    <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit"
                                class="rounded-lg bg-rose-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-800">
                                Guardar plaza
                            </button>
                        </form>
                    @endif
                </section>
            @endif
        </div>

        <div data-photo-modal
            @if ($errors->has('photo')) data-photo-modal-autopen @endif
            class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            role="dialog" aria-modal="true" aria-labelledby="photo-modal-title" aria-hidden="true">
            <div data-photo-modal-panel
                class="max-h-[min(90vh,48rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 id="photo-modal-title" class="text-lg font-bold text-gray-900">Foto de perfil</h2>
                        <p class="mt-1 text-sm text-gray-600">Consulta, cambia o elimina tu imagen.</p>
                    </div>
                    <button type="button" data-photo-modal-close aria-label="Cerrar ventana"
                        class="rounded-lg p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-rose-700">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18" />
                        </svg>
                    </button>
                </div>

                @if ($user->profile_photo_path)
                    <div data-photo-current class="mt-5 flex flex-col items-center gap-4">
                        <img src="{{ route('profile.photo', ['v' => $user->profile_photo_path]) }}" alt="Foto de perfil actual"
                            class="h-48 w-48 rounded-full border-4 border-gray-100 object-cover shadow-sm">
                        <div class="flex flex-wrap justify-center gap-3">
                            <button type="button" data-photo-choose
                                class="rounded-lg bg-rose-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-800">
                                Cambiar foto
                            </button>
                            <form action="{{ route('profile.photo.destroy') }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="rounded-lg border border-red-200 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-50">
                                    Eliminar foto
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div data-photo-empty class="mt-5 flex flex-col items-center gap-4 py-4">
                        <span class="flex h-40 w-40 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                            <svg class="h-16 w-16" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M20 21a8 8 0 0 0-16 0m8-10a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
                            </svg>
                        </span>
                        <p class="text-sm text-gray-600">Aún no has elegido una foto.</p>
                        <button type="button" data-photo-choose
                            class="rounded-lg bg-rose-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-800">
                            Elegir foto
                        </button>
                    </div>
                @endif

                <div data-photo-editor class="mt-5 hidden">
                    <p class="mb-3 text-center text-sm text-gray-600">
                        Arrastra la imagen para moverla y ajusta el zoom al recorte circular.
                    </p>
                    <div data-photo-crop-stage
                        class="relative mx-auto h-64 w-64 touch-none cursor-grab overflow-hidden rounded-full border-4 border-gray-200 bg-white active:cursor-grabbing"
                        aria-label="Área de recorte circular">
                        <canvas data-photo-crop-preview width="256" height="256"
                            aria-label="Vista previa del recorte de foto"
                            class="pointer-events-none absolute inset-0 h-full w-full select-none"></canvas>
                    </div>
                    <label for="photo-zoom" class="mt-4 block text-sm font-semibold text-gray-700">Zoom</label>
                    <input id="photo-zoom" data-photo-zoom type="range" min="1" max="3" step="0.01" value="1"
                        class="mt-2 w-full accent-rose-900">
                    <p class="mt-1 text-xs text-gray-500">El área dentro del círculo será tu foto.</p>
                    <button type="button" data-photo-choose
                        class="mx-auto mt-3 block text-sm font-semibold text-rose-900 hover:underline">
                        Elegir otra imagen
                    </button>

                    @error('photo')
                        <p role="alert" class="mt-3 text-sm text-red-700">{{ $message }}</p>
                    @enderror

                    <form data-photo-upload-form action="{{ route('profile.photo.update') }}" method="POST"
                        enctype="multipart/form-data" class="mt-5 flex flex-wrap justify-center gap-3">
                        @csrf
                        <input data-photo-input id="photo" name="photo" type="file"
                            accept="image/jpeg,image/png,image/webp" class="sr-only">
                        <button type="button" data-photo-cancel-selection
                            class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit" data-photo-submit disabled
                            class="rounded-lg bg-rose-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-800 disabled:cursor-not-allowed disabled:opacity-50">
                            Guardar foto
                        </button>
                    </form>
                </div>

                <p class="mt-5 text-center text-xs text-gray-500">
                    JPG, PNG o WebP. La imagen final se guarda recortada en formato cuadrado.
                </p>
            </div>
        </div>
    </section>
@endsection
