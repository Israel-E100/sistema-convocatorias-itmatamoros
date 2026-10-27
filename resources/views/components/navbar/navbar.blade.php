<header class="sticky top-0 z-50 bg-slate-900 text-white shadow-lg">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-3">
            <img src="https://dev.matamoros.tecnm.mx/tecnm_itm/sitio/drive/logo-del-tec-guindo-289x300.png"
                alt="Logotipo del Instituto Tecnológico de Matamoros"
                class="navbar-logo h-11 w-auto shrink-0 object-contain sm:h-14">
            <span class="min-w-0">
                <span class="block text-sm font-bold leading-tight sm:text-base">Sistema de Convocatorias</span>
                <span class="hidden text-xs text-slate-300 sm:block">Instituto Tecnológico de Matamoros</span>
            </span>
        </a>

        <div class="flex items-center gap-2">
            <nav aria-label="Navegación principal" class="hidden items-center gap-1 md:flex">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Panel</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.users.index') }}"
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Cuentas</a>
                        <a href="{{ route('admin.audit-logs.index') }}"
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Historial</a>
                    @elseif (auth()->user()->role === \App\Models\User::ROLE_REVIEWER)
                        <a href="{{ route('plazas.index') }}"
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Plazas</a>
                        <a href="{{ route('dictaminador.convocatorias.index') }}"
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Convocatorias</a>
                        <a href="{{ route('dictaminador.postulantes.index') }}"
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Postulantes</a>
                    @else
                        <a href="{{ route('plazas.index') }}"
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Plazas</a>
                        <a href="{{ route('home') }}#convocatorias"
                            data-applicant-convocatorias-link
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Convocatorias</a>
                        <a href="{{ route('postulaciones.index') }}"
                            class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Mis postulaciones</a>
                    @endif
                @else
                    <a href="{{ route('home') }}#convocatorias"
                        data-public-convocatorias-link
                        class="rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Convocatorias</a>
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-950/25 transition hover:bg-indigo-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 4-5-4-5m4 5H3" />
                        </svg>
                        Iniciar sesión
                    </a>
                    <a href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-950/25 transition hover:bg-indigo-400">
                        Registro
                    </a>
                @endauth
            </nav>

            @auth
                <div class="relative" data-notifications-menu>
                    <button type="button" data-notifications-toggle aria-controls="notifications-dropdown"
                        aria-expanded="false" aria-label="Notificaciones, {{ $unreadNotificationsCount }} nuevas"
                        class="relative inline-flex h-10 w-10 items-center justify-center rounded-full text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                        </svg>
                        @if ($unreadNotificationsCount > 0)
                            <span class="absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                                {{ $unreadNotificationsCount > 99 ? '99+' : $unreadNotificationsCount }}
                            </span>
                        @endif
                    </button>
                    <div id="notifications-dropdown" data-notifications-dropdown
                        class="absolute right-0 top-full z-50 mt-2 hidden w-[min(22rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-gray-200 bg-white text-gray-800 shadow-xl">
                        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Notificaciones</p>
                                <p class="text-xs text-gray-500">
                                    {{ $unreadNotificationsCount }} sin leer
                                </p>
                            </div>
                            <a href="{{ route('notifications.index') }}"
                                class="text-xs font-semibold text-rose-900 hover:underline">Ver todas</a>
                        </div>
                        <div class="max-h-80 overflow-y-auto">
                            @forelse ($recentNotifications as $notification)
                                <a href="{{ route('notifications.show', $notification->id) }}"
                                    class="block border-b border-gray-100 px-4 py-3 transition hover:bg-gray-50 {{ $notification->read_at ? '' : 'bg-rose-50/70' }}">
                                    <span class="flex items-start gap-3">
                                        @if (! $notification->read_at)
                                            <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-rose-600"
                                                aria-label="No leída"></span>
                                        @else
                                            <span class="mt-1.5 h-2 w-2 shrink-0"></span>
                                        @endif
                                        <span class="min-w-0 flex-1">
                                            <span class="block text-sm font-semibold text-gray-900">
                                                {{ data_get($notification->data, 'titulo', 'Notificación') }}
                                            </span>
                                            <span class="mt-0.5 line-clamp-2 block text-xs text-gray-600">
                                                {{ data_get($notification->data, 'mensaje', '') }}
                                            </span>
                                            <time datetime="{{ $notification->created_at->toIso8601String() }}"
                                                class="mt-1 block text-[11px] text-gray-500">
                                                {{ $notification->created_at->format('d/m/Y H:i') }}
                                            </time>
                                        </span>
                                    </span>
                                </a>
                            @empty
                                <p class="px-4 py-8 text-center text-sm text-gray-500">
                                    No tienes notificaciones.
                                </p>
                            @endforelse
                        </div>
                        <a href="{{ route('notifications.index') }}"
                            class="block bg-gray-50 px-4 py-3 text-center text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                            Abrir bandeja de notificaciones
                        </a>
                    </div>
                </div>

                <div class="relative" data-profile-menu>
                    <button type="button" data-profile-toggle aria-controls="profile-menu" aria-expanded="false"
                        aria-label="Abrir menú del perfil"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-600 bg-slate-800 text-slate-100 transition hover:border-slate-400 hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        @if (auth()->user()->profile_photo_path)
                            <img src="{{ route('profile.photo', ['v' => auth()->user()->profile_photo_path]) }}" alt=""
                                class="h-full w-full rounded-full object-cover">
                        @else
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"
                                    d="M20 21a8 8 0 0 0-16 0m8-10a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
                            </svg>
                        @endif
                    </button>
                    <div id="profile-menu" data-profile-dropdown
                        class="absolute right-0 top-full z-50 mt-2 hidden w-64 overflow-hidden rounded-xl border border-gray-200 bg-white py-2 text-gray-800 shadow-xl">
                        <div class="border-b border-gray-100 px-4 pb-3 pt-2">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        </div>
                        <div class="space-y-1 p-2">
                            <a href="{{ route('profile.show') }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-gray-50">
                                <svg class="h-5 w-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M20 21a8 8 0 0 0-16 0m8-10a5 5 0 1 0 0-10 5 5 0 0 0 0 10Z" />
                                </svg>
                                Mi perfil
                            </a>
                            <a href="{{ route('profile.settings') }}"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm transition hover:bg-gray-50">
                                <svg class="h-5 w-5 text-gray-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                        d="M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm0-6v2m0 16v2m10-10h-2M4 12H2m17.07-7.07-1.42 1.42M6.35 17.65l-1.42 1.42m14.14 0-1.42-1.42M6.35 6.35 4.93 4.93" />
                                </svg>
                                Configuración
                                <span class="ml-auto text-[10px] text-gray-500">Próximamente</span>
                            </a>
                            <button type="button" data-theme-toggle aria-pressed="false"
                                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm transition hover:bg-gray-50">
                                <svg data-theme-sun class="hidden h-5 w-5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <circle cx="12" cy="12" r="4" stroke-width="1.8" />
                                    <path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
                                </svg>
                                <svg data-theme-moon class="h-5 w-5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z" />
                                </svg>
                                <span data-theme-label>Modo oscuro</span>
                            </button>
                        </div>
                        <div class="border-t border-gray-100 px-2 pt-2">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" aria-label="Cerrar sesión" title="Cerrar sesión"
                                    class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-red-700 transition hover:bg-red-50">
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M10 17l5-5-5-5m5 5H3m9-9h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7" />
                                    </svg>
                                    Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-950/25 transition hover:bg-indigo-400 md:hidden">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                            d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4m-4-4 4-5-4-5m4 5H3" />
                    </svg>
                    Ingresar
                </a>
            @endauth

            <button type="button" data-navbar-toggle aria-controls="mobile-navigation" aria-expanded="false"
                aria-label="Abrir menú de navegación"
                class="inline-flex items-center justify-center rounded-lg p-2 text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-300 md:hidden">
                <svg data-menu-icon class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg data-close-icon class="hidden h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <nav id="mobile-navigation" data-navbar-menu aria-label="Navegación móvil"
        class="hidden border-t border-slate-800 px-4 pb-4 pt-2 md:hidden">
        @auth
            <a href="{{ route('dashboard') }}"
                class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Mi panel</a>
            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.users.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Cuentas</a>
                <a href="{{ route('admin.audit-logs.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Historial</a>
            @elseif (auth()->user()->role === \App\Models\User::ROLE_REVIEWER)
                <a href="{{ route('plazas.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Plazas</a>
                <a href="{{ route('dictaminador.convocatorias.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Convocatorias</a>
                <a href="{{ route('dictaminador.postulantes.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Postulantes</a>
            @else
                <a href="{{ route('plazas.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Plazas</a>
                <a href="{{ route('home') }}#convocatorias"
                    data-applicant-convocatorias-link
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Convocatorias</a>
                <a href="{{ route('postulaciones.index') }}"
                    class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Mis postulaciones</a>
            @endif
        @else
            <a href="{{ route('home') }}#convocatorias"
                data-public-convocatorias-link
                class="block rounded-lg px-3 py-2 text-sm transition hover:bg-slate-800">Convocatorias</a>
            <a href="{{ route('register') }}"
                class="mt-1 inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-3 py-2 text-sm font-semibold text-white shadow-md transition hover:bg-indigo-400">
                Crear cuenta
            </a>
        @endauth
    </nav>
</header>
