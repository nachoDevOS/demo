<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('confirmaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mensaje_id')->constrained('mensajes')->onDelete('cascade');
            $table->string('pc_nombre', 100);
            $table->string('pc_ip', 45);
            $table->enum('accion', ['recibido', 'visto', 'descargado']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('confirmaciones');
    }
};
