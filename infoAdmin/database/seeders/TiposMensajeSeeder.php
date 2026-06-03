<?php

namespace Database\Seeders;

use App\Models\TipoMensaje;
use Illuminate\Database\Seeder;

class TiposMensajeSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = [
            ['nombre' => 'Notificación', 'slug' => 'notificacion', 'color' => '#1B4F8A', 'icono' => 'fa-bell'],
            ['nombre' => 'Instructivo',  'slug' => 'instructivo',  'color' => '#1D6A3A', 'icono' => 'fa-clipboard-list'],
            ['nombre' => 'Urgente',      'slug' => 'urgente',      'color' => '#B71C1C', 'icono' => 'fa-exclamation-triangle'],
            ['nombre' => 'Reunión',      'slug' => 'reunion',      'color' => '#E65100', 'icono' => 'fa-calendar-alt'],
        ];

        foreach ($tipos as $tipo) {
            TipoMensaje::updateOrCreate(['slug' => $tipo['slug']], $tipo);
        }
    }
}
