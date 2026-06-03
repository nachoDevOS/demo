<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PcActiva extends Model
{
    protected $table = 'pcs_activas';

    protected $fillable = ['nombre', 'ip'];

    public static function registrar(string $nombre, string $ip): void
    {
        $pc = static::updateOrCreate(
            ['nombre' => $nombre],
            ['ip' => $ip]
        );
        $pc->touch(); // Fuerza actualización de updated_at aunque IP no cambie
    }

    public static function contarActivas(): int
    {
        return static::where('updated_at', '>=', now()->subSeconds(180))->count();
    }
}
