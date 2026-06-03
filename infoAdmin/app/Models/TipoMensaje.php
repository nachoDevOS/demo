<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoMensaje extends Model
{
    protected $table = 'tipos_mensaje';

    protected $fillable = ['nombre', 'slug', 'color', 'icono', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public static function activos()
    {
        return static::where('activo', true)->orderBy('nombre')->get();
    }

    public static function slugsActivos(): array
    {
        return static::where('activo', true)->pluck('slug')->toArray();
    }
}
