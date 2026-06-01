<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar los índices UNIQUE antiguos
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->dropUnique(['rut_empresa']);
        });

        // Crear índices UNIQUE parciales que solo apliquen a registros activos
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX idx_email_empresa_activo ON empresas(email) WHERE activo = 1');
            DB::statement('CREATE UNIQUE INDEX idx_rut_empresa_activo ON empresas(rut_empresa) WHERE activo = 1');
        }
        else {
            DB::statement('ALTER TABLE empresas ADD CONSTRAINT unique_email_empresa_active UNIQUE (email, activo)');
            DB::statement('ALTER TABLE empresas ADD CONSTRAINT unique_rut_empresa_active UNIQUE (rut_empresa, activo)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS idx_email_empresa_activo');
            DB::statement('DROP INDEX IF EXISTS idx_rut_empresa_activo');
        } else {
            DB::statement('ALTER TABLE empresas DROP CONSTRAINT unique_email_empresa_active');
            DB::statement('ALTER TABLE empresas DROP CONSTRAINT unique_rut_empresa_active');
        }

        // Restaurar los índices UNIQUE originales
        Schema::table('empresas', function (Blueprint $table) {
            $table->unique('email');
            $table->unique('rut_empresa');
        });
    }
};
