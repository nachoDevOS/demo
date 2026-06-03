<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['notificacion', 'instructivo', 'urgente', 'reunion']);
            $table->string('titulo', 200);
            $table->text('cuerpo');
            $table->string('remitente', 100);
            $table->boolean('tiene_archivo')->default(false);
            $table->string('archivo_nombre', 255)->nullable();
            $table->string('archivo_ruta', 500)->nullable();
            $table->enum('archivo_tipo', ['pdf', 'imagen'])->nullable();
            $table->integer('archivo_tamanio')->nullable();
            $table->integer('total_pcs_enviado')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
