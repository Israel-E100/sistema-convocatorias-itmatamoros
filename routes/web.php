<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ConvocatoriaPublicController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dictaminador\ConvocatoriaController as DictaminadorConvocatoriaController;
use App\Http\Controllers\Dictaminador\PostulanteController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlazaController;
use App\Http\Controllers\PostulacionController;
use App\Http\Controllers\ProfileController;
use App\Models\Convocatoria;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

Route::get('/', function (Request $request) {
    $filters = $request->validate([
        'tipo_concurso' => ['nullable', Rule::in(['Abierta', 'Cerrada'])],
        'tipo_personal' => ['nullable', Rule::in(['Docente', 'No docente'])],
    ]);

    $preferredPersonnelType = $filters['tipo_personal']
        ?? match ($request->user()?->tipo_usuario) {
            'aspirante_docente' => 'Docente',
            'aspirante_no_docente' => 'No docente',
            default => null,
        };

    $convocatorias = Convocatoria::query()
        ->where('estatus', 'Activa')
        ->whereDate('fecha_inicio', '<=', today())
        ->whereDate('fecha_fin', '>=', today())
        ->when(
            isset($filters['tipo_concurso']),
            fn ($query) => $query->where('tipo_concurso', $filters['tipo_concurso']),
        )
        ->when(
            isset($filters['tipo_personal']),
            fn ($query) => $query->where('tipo_personal', $filters['tipo_personal']),
        )
        ->when(
            $preferredPersonnelType !== null,
            fn ($query) => $query->orderByRaw(
                'CASE WHEN tipo_personal = ? THEN 0 ELSE 1 END',
                [$preferredPersonnelType],
            ),
        )
        ->orderBy('fecha_fin')
        ->orderByDesc('id')
        ->paginate(10)
        ->withQueryString();

    return view('welcome', [
        'convocatorias' => $convocatorias,
        'filters' => [
            'tipo_concurso' => $filters['tipo_concurso'] ?? '',
            'tipo_personal' => $filters['tipo_personal'] ?? '',
        ],
    ]);
})->name('home');
Route::get('/convocatorias/{convocatoria}', [ConvocatoriaPublicController::class, 'show'])
    ->name('convocatorias.show');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('login');

Route::get('/registro', [RegisteredUserController::class, 'create'])
    ->middleware('guest')
    ->name('register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])
    ->name('login.store');

Route::post('/registro', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register.store');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notificaciones/{notificationId}', [NotificationController::class, 'show'])
        ->name('notifications.show');
    Route::post('/notificaciones/leer-todas', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');
    Route::get('/mi-perfil', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/mi-perfil/plaza', [ProfileController::class, 'updateCurrentPlaza'])
        ->name('profile.plaza.update');
    Route::get('/mi-perfil/foto', [ProfileController::class, 'photo'])->name('profile.photo');
    Route::post('/mi-perfil/foto', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/mi-perfil/foto', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::get('/configuracion', [ProfileController::class, 'settings'])->name('profile.settings');

    Route::middleware('role:'.User::ROLE_USER.','.User::ROLE_REVIEWER)->group(function () {
        Route::get('/plazas', [PlazaController::class, 'index'])->name('plazas.index');
        Route::get('/plazas/estado', [PlazaController::class, 'liveState'])->name('plazas.live-state');
        Route::post('/plazas/mi-clave', [PlazaController::class, 'assignCurrent'])
            ->name('plazas.assign-current');
        Route::post('/plazas/mi-plaza/oferta-ascenso', [PlazaController::class, 'togglePromotionOffer'])
            ->name('plazas.promotion-offer');
        Route::post('/plazas/{plaza}/interes', [PlazaController::class, 'claimOffered'])
            ->name('plazas.claim');
    });

    Route::middleware('role:'.User::ROLE_USER)->group(function () {
        Route::get('/mis-postulaciones', [PostulacionController::class, 'index'])
            ->name('postulaciones.index');
        Route::get('/mis-postulaciones/{postulacion}', [PostulacionController::class, 'show'])
            ->name('postulaciones.show');
        Route::get('/mis-postulaciones/{postulacion}/documento', [PostulacionController::class, 'document'])
            ->name('postulaciones.documento');
        Route::post('/convocatorias/{convocatoria}/postular', [PostulacionController::class, 'store'])
            ->name('postulaciones.store');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:'.User::ROLE_ADMIN)->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
        Route::resource('users', AdminUserController::class)->except('show');
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });

    Route::prefix('dictaminador')->name('dictaminador.')
        ->middleware('role:'.User::ROLE_REVIEWER)
        ->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
            Route::resource('convocatorias', DictaminadorConvocatoriaController::class)
                ->except('show');
            Route::get('/postulantes', [PostulanteController::class, 'index'])
                ->name('postulantes.index');
            Route::get('/postulantes/{postulacion}', [PostulanteController::class, 'show'])
                ->name('postulantes.show');
            Route::patch('/postulantes/{postulacion}/dictamen', [PostulanteController::class, 'review'])
                ->name('postulantes.review');
            Route::get('/postulantes/{postulacion}/documento', [PostulanteController::class, 'document'])
                ->middleware('role:'.User::ROLE_REVIEWER.','.User::ROLE_ADMIN)
                ->name('postulantes.document');
        });
});
