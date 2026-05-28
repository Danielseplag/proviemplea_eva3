<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personas', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Contacto privado (no visible en CV ciego)
            $table->string('email')->unique();
            $table->string('telefono', 15)->nullable();

            // Identificador público anónimo
            $table->string('codigo_talento')->unique();

            // Perfil visible
            $table->text('resumen')->nullable();
            $table->string('nivel_educacional')->nullable();
            $table->string('titulo_carrera')->nullable();
            $table->integer('anio_egreso')->nullable();
            $table->integer('anios_experiencia')->default(0);
            $table->json('areas_experiencia')->nullable();
            $table->json('competencias')->nullable();
            $table->string('rango_renta')->nullable();
            $table->string('tipo_jornada')->nullable();
            $table->string('modalidad')->nullable();
            $table->json('cursos')->nullable();
            $table->json('idiomas')->nullable();
            $table->string('portafolio_url')->nullable();
            $table->boolean('persona_discapacidad')->default(false);

            // Estado
            $table->boolean('validado')->default(false);
            $table->boolean('activo')->default(true);
            $table->integer('porcentaje_completitud')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
