<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\HistorialController;
use App\Http\Controllers\ConfirmacionesController;
use App\Http\Controllers\TipoMensajeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('panel'));
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::post('/panel/enviar', [PanelController::class, 'enviar'])->name('panel.enviar');
    Route::get('/panel/pcs', [PanelController::class, 'pcs'])->name('panel.pcs');
    Route::get('/historial', [HistorialController::class, 'index'])->name('historial');
    Route::get('/historial/data', [HistorialController::class, 'data'])->name('historial.data');
    Route::get('/confirmaciones', [ConfirmacionesController::class, 'index'])->name('confirmaciones');
    Route::get('/confirmaciones/data', [ConfirmacionesController::class, 'data'])->name('confirmaciones.data');
    Route::get('/tipos', [TipoMensajeController::class, 'index'])->name('tipos.index');
    Route::post('/tipos', [TipoMensajeController::class, 'store'])->name('tipos.store');
    Route::put('/tipos/{tipo}', [TipoMensajeController::class, 'update'])->name('tipos.update');
    Route::delete('/tipos/{tipo}', [TipoMensajeController::class, 'destroy'])->name('tipos.destroy');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
