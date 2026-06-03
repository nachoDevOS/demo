<?php

namespace App\Http\Controllers;

use App\Models\Confirmacion;
use App\Models\Mensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConfirmacionesController extends Controller
{
    public function index(): View
    {
        $mensajes = Mensaje::latest()->limit(50)->get(['id', 'titulo', 'created_at']);

        return view('confirmaciones', compact('mensajes'));
    }

    public function data(Request $request): JsonResponse
    {
        $request->validate(['mensaje_id' => ['required', 'exists:mensajes,id']]);

        $mensaje = Mensaje::findOrFail($request->mensaje_id);

        $confirmaciones = Confirmacion::where('mensaje_id', $mensaje->id)
            ->get()
            ->groupBy('pc_nombre')
            ->map(fn ($group) => [
                'pc_nombre'   => $group->first()->pc_nombre,
                'pc_ip'       => $group->first()->pc_ip,
                'recibido'    => $group->contains('accion', 'recibido'),
                'visto'       => $group->contains('accion', 'visto'),
                'descargado'  => $group->contains('accion', 'descargado'),
            ])
            ->values();

        $total     = $mensaje->total_pcs_enviado;
        $recibidos = $confirmaciones->where('recibido', true)->count();
        $descargas = $confirmaciones->where('descargado', true)->count();

        return response()->json([
            'confirmaciones' => $confirmaciones,
            'stats' => [
                'total'       => $total,
                'recibidos'   => $recibidos,
                'pct_recibido' => $total > 0 ? round($recibidos / $total * 100) : 0,
                'descargas'   => $descargas,
                'pct_descarga' => $total > 0 ? round($descargas / $total * 100) : 0,
            ],
        ]);
    }
}
