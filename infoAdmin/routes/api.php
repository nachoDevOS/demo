<?php

use App\Http\Controllers\Api\RegistroController;
use App\Http\Controllers\Api\ConfirmacionApiController;
use Illuminate\Support\Facades\Route;

// Rutas públicas — llamadas desde los clientes Python MensaDesk
Route::post('/registro', [RegistroController::class, 'store']);
Route::post('/confirmacion', [ConfirmacionApiController::class, 'store']);
Route::get('/pcs', [RegistroController::class, 'count']);
