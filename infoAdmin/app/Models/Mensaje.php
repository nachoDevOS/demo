<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mensaje extends Model
{
    protected $table = 'mensajes';

    protected $fillable = [
        'tipo',
        'titulo',
        'cuerpo',
        'remitente',
        'tiene_archivo',
        'archivo_nombre',
        'archivo_ruta',
        'archivo_tipo',
        'archivo_tamanio',
        'total_pcs_enviado',
    ];

    protected $casts = [
        'tiene_archivo' => 'boolean',
        'archivo_tamanio' => 'integer',
        'total_pcs_enviado' => 'integer',
    ];

    public function confirmaciones(): HasMany
    {
        return $this->hasMany(Confirmacion::class);
    }

    public function getArchivoUrlAttribute(): ?string
    {
        if (! $this->archivo_ruta) {
            return null;
        }

        return url('uploads/'.$this->archivo_ruta);
    }
}
