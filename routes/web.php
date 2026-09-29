<?php
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProyectoArchivoController;
use App\Http\Controllers\EvidenciaArchivoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ComunidadController;
use App\Http\Controllers\AccionCompromisoController;
use App\Http\Controllers\ResponsableController;
use App\Http\Controllers\NecesidadController;
use App\Http\Controllers\AccionEjecucionController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SeguimientoReporteController;
use Illuminate\Support\Facades\Route;

Route::pattern('proyecto', '[0-9]+');
Route::pattern('comunidad', '[0-9]+');
Route::pattern('accione', '[0-9]+');
Route::pattern('responsable', '[0-9]+');
Route::pattern('necesidade', '[0-9]+');
Route::pattern('archivo', '[0-9]+');
Route::pattern('user', '[0-9]+');
Route::pattern('role', '[0-9]+');

Route::get('/', [DashboardController::class, 'index'])->name('home');

Route::middleware(['auth','active','verified','password.changed'])->group(function () {
    Route::get('/dashboard', [DashboardController::class,'index'])->middleware('permission:dashboard.view')->name('dashboard');
    Route::get('/reportes/seguimiento', [SeguimientoReporteController::class, 'ejecutivo'])->middleware('permission:dashboard.view')->name('reportes.seguimiento');
    Route::get('/reportes/seguimiento.csv', [SeguimientoReporteController::class, 'csv'])->middleware('permission:dashboard.view')->name('reportes.seguimiento.csv');
    Route::get('/reportes/alertas', [SeguimientoReporteController::class, 'alertas'])->middleware('permission:dashboard.view')->name('reportes.alertas');

    Route::resource('proyectos', ProyectoController::class)->only(['index','show'])->middleware('permission:proyectos.view');
    Route::resource('proyectos', ProyectoController::class)->only(['create','store'])->middleware('permission:proyectos.create');
    Route::resource('proyectos', ProyectoController::class)->only(['edit','update'])->middleware('permission:proyectos.edit');
    Route::resource('proyectos', ProyectoController::class)->only(['destroy'])->middleware('permission:proyectos.delete');

    Route::resource('comunidades', ComunidadController::class)->only(['index','show'])->parameters(['comunidades' => 'comunidad'])->middleware('permission:comunidades.view');
    Route::resource('comunidades', ComunidadController::class)->only(['create','store'])->parameters(['comunidades' => 'comunidad'])->middleware('permission:comunidades.create');
    Route::resource('comunidades', ComunidadController::class)->only(['edit','update'])->parameters(['comunidades' => 'comunidad'])->middleware('permission:comunidades.edit');
    Route::resource('comunidades', ComunidadController::class)->only(['destroy'])->parameters(['comunidades' => 'comunidad'])->middleware('permission:comunidades.delete');

    Route::resource('compromisos', AccionCompromisoController::class)->only(['index','show'])->parameters(['compromisos' => 'accione'])->middleware('permission:compromisos.view');
    Route::resource('compromisos', AccionCompromisoController::class)->only(['create','store'])->parameters(['compromisos' => 'accione'])->middleware('permission:compromisos.create');
    Route::resource('compromisos', AccionCompromisoController::class)->only(['edit','update'])->parameters(['compromisos' => 'accione'])->middleware('permission:compromisos.edit');
    Route::resource('compromisos', AccionCompromisoController::class)->only(['destroy'])->parameters(['compromisos' => 'accione'])->middleware('permission:compromisos.delete');

    Route::resource('responsables', ResponsableController::class)->only(['index','show'])->middleware('permission:responsables.view');
    Route::resource('responsables', ResponsableController::class)->only(['create','store'])->middleware('permission:responsables.create');
    Route::resource('responsables', ResponsableController::class)->only(['edit','update'])->middleware('permission:responsables.edit');
    Route::resource('responsables', ResponsableController::class)->only(['destroy'])->middleware('permission:responsables.delete');
    Route::resource('necesidades', NecesidadController::class)->only(['index','show'])->middleware('permission:necesidades.view');
    Route::resource('necesidades', NecesidadController::class)->only(['create','store'])->middleware('permission:necesidades.create');
    Route::resource('necesidades', NecesidadController::class)->only(['edit','update'])->middleware('permission:necesidades.edit');
    Route::resource('necesidades', NecesidadController::class)->only(['destroy'])->middleware('permission:necesidades.delete');
    Route::resource('acciones', AccionEjecucionController::class)->only(['index'])->parameters(['acciones' => 'accione'])->middleware('permission:acciones.view');
    Route::resource('acciones', AccionEjecucionController::class)->only(['create','store'])->parameters(['acciones' => 'accione'])->middleware('permission:acciones.create');
    Route::resource('acciones', AccionEjecucionController::class)->only(['edit','update'])->parameters(['acciones' => 'accione'])->middleware('permission:acciones.edit');
    Route::resource('acciones', AccionEjecucionController::class)->only(['destroy'])->parameters(['acciones' => 'accione'])->middleware('permission:acciones.delete');

    Route::get('evidencias/proyectos/{archivo}', [EvidenciaArchivoController::class, 'proyecto'])
        ->middleware('permission:proyectos.view')->name('evidencias.proyectos.descargar');
    Route::get('evidencias/acciones/{archivo}', [EvidenciaArchivoController::class, 'accion'])
        ->middleware('permission:acciones.view')->name('evidencias.acciones.descargar');
    Route::middleware('permission:proyectos.edit')->group(function () {
        Route::post('proyectos/{proyecto}/archivos', [ProyectoArchivoController::class,'store'])->name('proyectos.archivos.store');
        Route::delete('archivos/{archivo}', [ProyectoArchivoController::class,'destroy'])->name('archivos.destroy');
    });

    Route::middleware('permission:proyectos.delete')->group(function () {
        Route::delete('proyectos/{proyecto}/force', [ProyectoController::class,'forceDelete'])->name('proyectos.forceDelete');
    });

    Route::prefix('administracion')->name('admin.')->group(function () {
        Route::get('usuarios', [UserManagementController::class, 'index'])->middleware('permission:users.view')->name('users.index');
        Route::get('usuarios/crear', [UserManagementController::class, 'create'])->middleware('permission:users.create')->name('users.create');
        Route::post('usuarios', [UserManagementController::class, 'store'])->middleware('permission:users.create')->name('users.store');
        Route::get('usuarios/{user}/editar', [UserManagementController::class, 'edit'])->middleware('permission:users.edit')->name('users.edit');
        Route::put('usuarios/{user}', [UserManagementController::class, 'update'])->middleware('permission:users.edit')->name('users.update');
        Route::delete('usuarios/{user}', [UserManagementController::class, 'destroy'])->middleware('permission:users.delete')->name('users.destroy');
        Route::post('usuarios/{user}/reenviar-credenciales', [UserManagementController::class, 'resendCredentials'])->middleware('permission:users.edit')->name('users.resend');
        Route::get('roles', [RoleManagementController::class, 'index'])->middleware('permission:roles.view')->name('roles.index');
        Route::get('roles/crear', [RoleManagementController::class, 'create'])->middleware('permission:roles.edit')->name('roles.create');
        Route::post('roles', [RoleManagementController::class, 'store'])->middleware('permission:roles.edit')->name('roles.store');
        Route::get('roles/{role}/editar', [RoleManagementController::class, 'edit'])->middleware('permission:roles.edit')->name('roles.edit');
        Route::put('roles/{role}', [RoleManagementController::class, 'update'])->middleware('permission:roles.edit')->name('roles.update');
    });
});


Route::middleware(['auth','active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
require __DIR__.'/auth.php';
