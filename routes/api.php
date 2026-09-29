<?php

use App\Http\Controllers\Api\ProyectoApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('proyectos', [ProyectoApiController::class, 'index'])->middleware('permission:proyectos.view')->name('api.proyectos.index');
    Route::post('proyectos', [ProyectoApiController::class, 'store'])->middleware('permission:proyectos.create')->name('api.proyectos.store');
    Route::get('proyectos/{proyecto}', [ProyectoApiController::class, 'show'])->middleware('permission:proyectos.view')->name('api.proyectos.show');
    Route::match(['put', 'patch'], 'proyectos/{proyecto}', [ProyectoApiController::class, 'update'])->middleware('permission:proyectos.edit')->name('api.proyectos.update');
    Route::delete('proyectos/{proyecto}', [ProyectoApiController::class, 'destroy'])->middleware('permission:proyectos.delete')->name('api.proyectos.destroy');
});