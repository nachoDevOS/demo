<?php

namespace App\Http\Controllers;

use App\Models\TipoMensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TipoMensajeController extends Controller
{
    public function index(): View
    {
        $tipos = TipoMensaje::orderBy('nombre')->get();
        return view('tipos', compact('tipos'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug'   => ['required', 'string', 'max:50', 'alpha_dash', 'unique:tipos_mensaje,slug'],
            'color'  => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icono'  => ['required', 'string', 'max:50'],
        ]);

        $tipo = TipoMensaje::create($data + ['activo' => true]);
        return response()->json(['ok' => true, 'tipo' => $tipo]);
    }

    public function update(Request $request, TipoMensaje $tipo): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'color'  => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icono'  => ['required', 'string', 'max:50'],
            'activo' => ['boolean'],
        ]);

        $tipo->update($data);
        return response()->json(['ok' => true, 'tipo' => $tipo]);
    }

    public function destroy(TipoMensaje $tipo): JsonResponse
    {
        $tipo->delete();
        return response()->json(['ok' => true]);
    }
}
