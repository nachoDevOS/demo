<?php

namespace App\Http\Controllers;

use App\Events\MensajeEnviado;
use App\Models\Mensaje;
use App\Models\PcActiva;
use App\Models\TipoMensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(): View
    {
        $ultimosMensajes = Mensaje::latest()->limit(5)->get();
        $tipos           = TipoMensaje::activos();

        return view('panel', compact('ultimosMensajes', 'tipos'));
    }

    public function enviar(Request $request): JsonResponse
    {
        try {
            $slugsValidos = implode(',', TipoMensaje::slugsActivos());
            $request->validate([
                'tipo'      => ['required', 'in:'.$slugsValidos],
                'titulo'    => ['required', 'string', 'max:200'],
                'cuerpo'    => ['required', 'string', 'max:20000'],
                'remitente' => ['required', 'string', 'max:100'],
                'archivo'   => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,gif'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'errors' => $e->errors()], 422);
        }

        try {
            $datos = [
                'tipo'      => $request->tipo,
                'titulo'    => $request->titulo,
                'cuerpo'    => $request->cuerpo,
                'remitente' => $request->remitente,
            ];

            if ($request->hasFile('archivo')) {
                $file        = $request->file('archivo');
                $ext         = strtolower($file->getClientOriginalExtension());
                $nombreUnico = uniqid('mensa_', true).'.'.$ext;
                $tamanio     = $file->getSize(); // obtener tamaño ANTES de mover

                $file->move(public_path('uploads'), $nombreUnico);

                $datos['tiene_archivo']   = true;
                $datos['archivo_nombre']  = $file->getClientOriginalName();
                $datos['archivo_ruta']    = $nombreUnico;
                $datos['archivo_tipo']    = ($ext === 'pdf') ? 'pdf' : 'imagen';
                $datos['archivo_tamanio'] = $tamanio;
            }

            $mensaje = Mensaje::create($datos);

            $pcsConectadas = PcActiva::contarActivas();
            $mensaje->update(['total_pcs_enviado' => $pcsConectadas]);

            try {
                event(new MensajeEnviado($mensaje->fresh()));
            } catch (\Throwable $e) {
                // Mensaje guardado aunque Reverb no esté disponible
            }

            return response()->json([
                'ok'         => true,
                'mensaje_id' => $mensaje->id,
                'enviados'   => $pcsConectadas,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error al enviar mensaje: '.$e->getMessage());

            return response()->json(['ok' => false, 'error' => 'Error interno: '.$e->getMessage()], 500);
        }
    }

    public function pcs(): JsonResponse
    {
        return response()->json([
            'count' => PcActiva::contarActivas(),
        ]);
    }
}
