<?php

namespace App\Http\Controllers;

use App\Models\Mensaje;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistorialController extends Controller
{
    public function index(): View
    {
        return view('historial');
    }

    public function data(Request $request): JsonResponse
    {
        $query = Mensaje::query()->latest();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $mensajes = $query->paginate(20);

        return response()->json($mensajes);
    }
}
