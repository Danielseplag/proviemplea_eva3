<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar el índice UNIQUE antiguo
        Schema::table('personas', function (Blueprint $table) {
            $table->dropUnique(['email']);
        });

        // Crear un índice UNIQUE parcial que solo aplique a registros activos
        // Para SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX idx_email_activo ON personas(email) WHERE activo = 1');
        }
        // Para MySQL
        else {
            DB::statement('ALTER TABLE personas ADD CONSTRAINT unique_email_active UNIQUE (email, activo)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS idx_email_activo');
        } else {
            DB::statement('ALTER TABLE personas DROP CONSTRAINT unique_email_active');
        }

        // Restaurar el índice UNIQUE original
        Schema::table('personas', function (Blueprint $table) {
            $table->unique('email');
        });
    }
};
