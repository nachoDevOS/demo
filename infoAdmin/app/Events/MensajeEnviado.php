<?php

namespace App\Events;

use App\Models\Mensaje;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensajeEnviado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Mensaje $mensaje) {}

    public function broadcastOn(): array
    {
        return [new Channel('mensajes')];
    }

    public function broadcastAs(): string
    {
        return 'mensaje.enviado';
    }

    public function broadcastWith(): array
    {
        $color = \App\Models\TipoMensaje::where('slug', $this->mensaje->tipo)
            ->value('color') ?? '#1B4F8A';

        return [
            'tipo'            => $this->mensaje->tipo,
            'color'           => $color,
            'titulo'          => $this->mensaje->titulo,
            'cuerpo'          => strip_tags($this->mensaje->cuerpo),
            'cuerpo_html'     => $this->mensaje->cuerpo,
            'remitente'       => $this->mensaje->remitente,
            'timestamp'       => $this->mensaje->created_at?->format('Y-m-d H:i:s'),
            'mensaje_id'      => $this->mensaje->id,
            'tiene_archivo'   => $this->mensaje->tiene_archivo,
            'archivo_nombre'  => $this->mensaje->archivo_nombre,
            'archivo_url'     => $this->mensaje->archivo_url,
            'archivo_tipo'    => $this->mensaje->archivo_tipo,
            'archivo_tamanio' => $this->mensaje->archivo_tamanio,
        ];
    }
}
