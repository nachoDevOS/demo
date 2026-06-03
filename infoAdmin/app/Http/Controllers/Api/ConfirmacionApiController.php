<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Confirmacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfirmacionApiController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'mensaje_id' => ['required', 'exists:mensajes,id'],
            'pc_nombre'  => ['required', 'string', 'max:100'],
            'pc_ip'      => ['nullable', 'string', 'max:45'],
            'accion'     => ['required', 'in:recibido,visto,descargado'],
        ]);

        // No duplicar la misma acción para mismo mensaje+pc
        Confirmacion::firstOrCreate([
            'mensaje_id' => $request->mensaje_id,
            'pc_nombre'  => $request->pc_nombre,
            'accion'     => $request->accion,
        ], [
            'pc_ip' => $request->pc_ip ?? $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }
}
