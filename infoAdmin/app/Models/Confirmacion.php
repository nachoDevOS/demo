<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Confirmacion extends Model
{
    protected $table = 'confirmaciones';

    protected $fillable = [
        'mensaje_id',
        'pc_nombre',
        'pc_ip',
        'accion',
    ];

    public function mensaje(): BelongsTo
    {
        return $this->belongsTo(Mensaje::class);
    }
}
