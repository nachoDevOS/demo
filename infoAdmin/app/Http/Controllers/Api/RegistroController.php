<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PcActiva;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistroController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'ip'     => ['nullable', 'string', 'max:45'],
        ]);

        $ip = $request->ip ?? $request->ip();
        PcActiva::registrar($request->nombre, $ip);

        return response()->json(['ok' => true]);
    }

    public function count(): JsonResponse
    {
        return response()->json(['count' => PcActiva::contarActivas()]);
    }
}
